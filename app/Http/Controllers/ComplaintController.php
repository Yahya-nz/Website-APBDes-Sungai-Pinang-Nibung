<?php

namespace App\Http\Controllers;

use App\Http\Requests\ComplaintRecoveryRequest;
use App\Http\Requests\ComplaintRequest;
use App\Http\Requests\ComplaintResponseRequest;
use App\Models\ActivityLog;
use App\Models\BudgetLine;
use App\Models\Complaint;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ComplaintController extends Controller
{
    /** Cookie berisi nomor tiket yang pernah dikirim atau dibuka di perangkat ini. */
    private const SAVED_COOKIE = 'tiket_saya';

    private const SAVED_LIMIT = 20;

    public function create(Request $request): View
    {
        return view('complaints.create', [
            'activities' => BudgetLine::query()->where('year', BudgetLine::max('year'))->where('type', 'expense')->orderBy('category')->orderBy('id')->get(['id', 'category', 'description']),
            'selected' => $request->integer('kegiatan') ?: null,
            'payload' => ['year' => $this->latestYear()],
        ]);
    }

    public function store(ComplaintRequest $request): RedirectResponse
    {
        $data = collect($request->validated())->except(['website', 'recovery_pin'])->all();
        $complaint = Complaint::create($data + [
            'ticket' => Complaint::newTicket(),
            'status' => 'new',
            'contact_key' => Complaint::contactKey($data['contact'] ?? null),
            'recovery_pin' => $request->filled('recovery_pin') ? $request->validated('recovery_pin') : null,
        ]);
        $this->remember($request, [$complaint->ticket]);

        $message = 'Pesan terkirim. Simpan nomor tiket '.$complaint->ticket.' untuk melihat tanggapan.';
        if ($complaint->recovery_pin) {
            $message .= ' Bila lupa, tiket bisa dipulihkan dengan kontak dan PIN Anda.';
        }

        return redirect()->route('complaints.show', ['tiket' => $complaint->ticket])->with('success', $message);
    }

    /**
     * Warga mengecek status dengan nomor tiket; tidak menampilkan nama atau kontak.
     * Halaman ini juga menampilkan "Tiket saya" (tiket yang tersimpan di perangkat) dengan filter status dan jenis.
     */
    public function show(Request $request): View
    {
        $ticket = strtoupper(trim(is_string($request->query('tiket')) ? $request->query('tiket') : ''));
        $complaint = $ticket !== '' ? Complaint::with('budgetLine:id,description')->where('ticket', $ticket)->first() : null;
        if ($complaint) {
            $this->remember($request, [$complaint->ticket]);
        }

        $status = in_array($request->query('status'), array_keys(Complaint::STATUSES), true) ? $request->query('status') : null;
        $topic = in_array($request->query('jenis'), array_keys(Complaint::TOPICS), true) ? $request->query('jenis') : null;
        $savedTickets = array_values(array_unique(array_merge($complaint ? [$complaint->ticket] : [], $this->savedTickets($request))));
        $saved = Complaint::query()->whereIn('ticket', $savedTickets)->latest('id')->get(['id', 'ticket', 'topic', 'status', 'response', 'created_at']);

        return view('complaints.show', [
            'ticket' => $ticket,
            'complaint' => $complaint,
            'saved' => $saved->filter(fn (Complaint $item): bool => (! $status || $item->status === $status) && (! $topic || $item->topic === $topic))->values(),
            'savedCounts' => $saved->countBy('status'),
            'savedTotal' => $saved->count(),
            'status' => $status,
            'topic' => $topic,
            'payload' => ['year' => $this->latestYear()],
        ]);
    }

    /**
     * Memulihkan tiket yang hilang dengan kontak dan PIN. Pesan gagal dibuat umum
     * supaya tidak membocorkan apakah sebuah kontak pernah mengirim pengaduan.
     */
    public function recover(ComplaintRecoveryRequest $request): RedirectResponse
    {
        $key = Complaint::contactKey($request->validated('contact'));
        $pin = (string) $request->validated('recovery_pin');
        $tickets = Complaint::query()->where('contact_key', $key)->whereNotNull('recovery_pin')->latest('id')->limit(50)->get(['id', 'ticket', 'recovery_pin'])
            ->filter(fn (Complaint $complaint): bool => Hash::check($pin, $complaint->recovery_pin))
            ->pluck('ticket')->all();

        if ($tickets === []) {
            throw ValidationException::withMessages(['recovery_pin' => 'Tidak ada tiket yang cocok dengan kontak dan PIN tersebut. Periksa kembali penulisannya.'])->errorBag('recovery');
        }
        $this->remember($request, $tickets);

        return redirect()->to(route('complaints.show').'#tiket-saya')->with('success', count($tickets).' tiket ditemukan dan disimpan di daftar Tiket saya: '.implode(', ', $tickets).'.');
    }

    /**
     * Menghapus tiket dari daftar di perangkat ini (misalnya di komputer umum). Pesannya tidak ikut terhapus.
     */
    public function forget(Request $request, string $ticket): RedirectResponse
    {
        $tickets = array_values(array_diff($this->savedTickets($request), [strtoupper($ticket)]));
        Cookie::queue(self::SAVED_COOKIE, json_encode($tickets), 60 * 24 * 365);

        return redirect()->to(route('complaints.show').'#tiket-saya')->with('success', 'Tiket '.strtoupper($ticket).' dihapus dari daftar di perangkat ini.');
    }

    /**
     * Bukti tiket dalam bentuk berkas teks supaya warga bisa menyimpannya di ponsel atau mencetaknya.
     */
    public function receipt(Request $request): Response
    {
        $ticket = strtoupper(trim(is_string($request->query('tiket')) ? $request->query('tiket') : ''));
        $complaint = Complaint::query()->where('ticket', $ticket)->firstOrFail();
        $lines = [
            'BUKTI TIKET PENGADUAN',
            'Pemerintah Desa Sungai Pinang Nibung',
            'Kecamatan Sungai Pinang, Kabupaten Ogan Ilir',
            str_repeat('-', 44),
            'Nomor tiket : '.$complaint->ticket,
            'Jenis pesan : '.Complaint::TOPICS[$complaint->topic],
            'Dikirim     : '.$complaint->created_at->timezone('Asia/Jakarta')->translatedFormat('j F Y, H.i').' WIB',
            str_repeat('-', 44),
            'Cek tanggapan di:',
            route('complaints.show', ['tiket' => $complaint->ticket]),
            '',
            'Simpan berkas ini. Nomor tiket diperlukan untuk melihat tanggapan perangkat desa.',
        ];

        return response(implode("\r\n", $lines)."\r\n", 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="bukti-tiket-'.$complaint->ticket.'.txt"',
        ]);
    }

    public function manage(Request $request): View
    {
        $status = in_array($request->query('status'), array_keys(Complaint::STATUSES), true) ? $request->query('status') : null;

        return view('admin.complaints', [
            'complaints' => Complaint::with('budgetLine:id,description')->when($status, fn ($query) => $query->where('status', $status))->latest('id')->paginate(10)->withQueryString(),
            'counts' => Complaint::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            'status' => $status,
            'payload' => ['year' => $this->latestYear()],
        ]);
    }

    public function update(ComplaintResponseRequest $request, Complaint $complaint): RedirectResponse
    {
        $data = $request->validated();
        $complaint->fill($data);
        if (filled($data['response'] ?? null) && $complaint->isDirty('response')) {
            $complaint->responded_at = now();
            $complaint->responded_by = $request->user()->id;
        }
        $complaint->save();
        ActivityLog::create(['user_id' => $request->user()->id, 'action' => 'complaint', 'description' => 'Menanggapi pengaduan '.$complaint->ticket.' ('.Complaint::STATUSES[$complaint->status].')']);

        return redirect()->route('admin.complaints.index', array_filter(['status' => $request->input('status_filter')]))->with('success', 'Tanggapan untuk '.$complaint->ticket.' disimpan.');
    }

    /**
     * @return list<string>
     */
    private function savedTickets(Request $request): array
    {
        $tickets = json_decode((string) $request->cookie(self::SAVED_COOKIE, '[]'), true);

        return is_array($tickets) ? array_values(array_filter($tickets, fn ($ticket): bool => is_string($ticket) && preg_match('/^ADU-\d{4}-[A-Z0-9]{4}$/', $ticket) === 1)) : [];
    }

    /**
     * @param  list<string>  $tickets
     */
    private function remember(Request $request, array $tickets): void
    {
        $merged = array_slice(array_values(array_unique(array_merge($tickets, $this->savedTickets($request)))), 0, self::SAVED_LIMIT);
        Cookie::queue(self::SAVED_COOKIE, json_encode($merged), 60 * 24 * 365);
    }

    private function latestYear(): int
    {
        return (int) (BudgetLine::max('year') ?? now()->year);
    }
}
