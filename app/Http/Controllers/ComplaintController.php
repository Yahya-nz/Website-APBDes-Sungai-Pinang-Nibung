<?php

namespace App\Http\Controllers;

use App\Http\Requests\ComplaintRequest;
use App\Http\Requests\ComplaintResponseRequest;
use App\Models\ActivityLog;
use App\Models\BudgetLine;
use App\Models\Complaint;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ComplaintController extends Controller
{
    public function create(Request $request): View
    {
        return view('complaints.create', [
            'activities' => BudgetLine::query()->where('year', BudgetLine::max('year'))->where('type', 'expense')->orderBy('category')->orderBy('id')->get(['id', 'category', 'description']),
            'selected' => $request->integer('kegiatan') ?: null,
            'payload' => ['year' => (int) (BudgetLine::max('year') ?? now()->year)],
        ]);
    }

    public function store(ComplaintRequest $request): RedirectResponse
    {
        $complaint = Complaint::create(collect($request->validated())->except('website')->all() + ['ticket' => Complaint::newTicket(), 'status' => 'new']);

        return redirect()->route('complaints.show', ['tiket' => $complaint->ticket])->with('success', 'Pesan terkirim. Simpan nomor tiket '.$complaint->ticket.' untuk melihat tanggapan.');
    }

    /**
     * Warga mengecek status dengan nomor tiket; tidak menampilkan nama atau kontak.
     */
    public function show(Request $request): View
    {
        $ticket = strtoupper(trim((string) $request->query('tiket', '')));
        $complaint = $ticket !== '' ? Complaint::with('budgetLine:id,description')->where('ticket', $ticket)->first() : null;

        return view('complaints.show', ['ticket' => $ticket, 'complaint' => $complaint, 'payload' => ['year' => (int) (BudgetLine::max('year') ?? now()->year)]]);
    }

    public function manage(Request $request): View
    {
        $status = in_array($request->query('status'), array_keys(Complaint::STATUSES), true) ? $request->query('status') : null;

        return view('admin.complaints', [
            'complaints' => Complaint::with('budgetLine:id,description')->when($status, fn ($query) => $query->where('status', $status))->latest('id')->paginate(10)->withQueryString(),
            'counts' => Complaint::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            'status' => $status,
            'payload' => ['year' => (int) (BudgetLine::max('year') ?? now()->year)],
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
}
