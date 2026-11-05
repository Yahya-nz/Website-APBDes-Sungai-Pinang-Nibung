<?php

namespace App\Http\Controllers;

use App\Http\Requests\BudgetLineRequest;
use App\Http\Requests\BudgetStatusRequest;
use App\Http\Requests\ImportBudgetRequest;
use App\Models\ActivityLog;
use App\Models\BudgetLine;
use App\Models\Complaint;
use App\Models\Dataset;
use App\Models\User;
use App\Services\BudgetDashboard;
use App\Services\BudgetImporter;
use App\Services\DataQualityChecks;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminController extends Controller
{
    /**
     * Dashboard pengelola: ringkasan data dan hal yang perlu ditindaklanjuti.
     */
    public function index(Request $request, BudgetDashboard $dashboard, DataQualityChecks $quality): View
    {
        $payload = $dashboard->payload($request);
        $checks = $quality->run($payload);
        $complaintCounts = Complaint::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('admin.index', [
            'payload' => $payload,
            'checks' => $checks,
            'tasks' => $this->tasks($payload, $checks, (int) ($complaintCounts['new'] ?? 0)),
            'complaintCounts' => $complaintCounts,
            'latestComplaints' => Complaint::query()->whereIn('status', ['new', 'in_progress'])->latest('id')->limit(4)->get(),
            'officials' => User::query()->where('is_admin', true)->count(),
        ]);
    }

    /**
     * Pengelolaan data: impor, informasi sumber, pemeriksaan, riwayat dataset, dan tabel anggaran.
     */
    public function budgets(Request $request, BudgetDashboard $dashboard, DataQualityChecks $quality): View
    {
        $payload = $dashboard->payload($request);
        $datasets = Dataset::query()
            ->with('user:id,name')
            ->withCount(['budgetLines', 'budgetLines as verified_lines_count' => fn (Builder $query) => $query->where('verification_status', 'verified'), 'realizations'])
            ->latest('updated_at')->latest('id')->limit(100)->get();

        return view('admin.budgets', [
            'payload' => $payload,
            'checks' => $quality->run($payload),
            'datasets' => $datasets,
            'activeDataset' => $payload['filters']['dataset'] !== '' ? $datasets->firstWhere('id', (int) $payload['filters']['dataset']) : null,
        ]);
    }

    public function store(BudgetLineRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $data = $request->validated();
            $dataset = $this->manualDataset('Input manual', $data, false, $request->user());
            $line = BudgetLine::create($data + ['dataset_id' => $dataset->id, 'is_demo' => false]);
            $this->audit($request, 'create', 'Menambahkan '.$line->description, $line->year);
        });

        return redirect()->route('admin.budgets.index', ['year' => $request->integer('year')])->with('success', 'Data anggaran berhasil ditambahkan.');
    }

    public function update(BudgetLineRequest $request, BudgetLine $budget): RedirectResponse
    {
        DB::transaction(function () use ($request, $budget): void {
            $data = $request->validated();
            if ((int) $data['year'] !== $budget->year) {
                $data['dataset_id'] = $this->manualDataset('Pembaruan manual', $data, $budget->is_demo, $request->user())->id;
            }
            $budget->update($data);
            $this->audit($request, 'update', 'Memperbarui '.$budget->description, $budget->year);
        });

        return redirect()->route('admin.budgets.index', ['year' => $budget->year])->with('success', 'Perubahan data anggaran berhasil disimpan.');
    }

    /**
     * Menetapkan status verifikasi untuk baris-baris yang dipilih pada tabel.
     */
    public function verify(BudgetStatusRequest $request): RedirectResponse
    {
        $status = $request->validated('verification_status');
        $label = BudgetLine::STATUSES[$status];
        $lines = BudgetLine::query()->whereIn('id', $request->validated('ids'))->get(['id', 'year', 'verification_status']);
        $changed = $lines->where('verification_status', '!=', $status);

        if ($changed->isNotEmpty()) {
            DB::transaction(function () use ($request, $changed, $status, $label): void {
                BudgetLine::query()->whereIn('id', $changed->pluck('id'))->update(['verification_status' => $status]);
                $years = $changed->pluck('year')->unique();
                $this->audit($request, 'verify', "Menetapkan status {$label} untuk {$changed->count()} baris anggaran", $years->count() === 1 ? (int) $years->first() : null);
            });
        }

        $message = $changed->isEmpty() ? "Baris yang dipilih sudah berstatus {$label}." : "Status {$changed->count()} baris anggaran diubah menjadi {$label}.";

        return back(fallback: route('admin.budgets.index', ['year' => $lines->first()->year]))->with('success', $message);
    }

    public function destroy(Request $request, BudgetLine $budget): RedirectResponse
    {
        $year = $budget->year;
        DB::transaction(function () use ($request, $budget): void {
            $this->audit($request, 'delete', 'Menghapus '.$budget->description, $budget->year);
            $budget->delete();
        });

        return redirect()->route('admin.budgets.index', ['year' => $year])->with('success', 'Data anggaran berhasil dihapus.');
    }

    public function import(ImportBudgetRequest $request, BudgetImporter $importer): RedirectResponse
    {
        $count = $importer->import($request->file('file'), $request->safe()->except('file'), $request->user());

        return redirect()->route('admin.budgets.index', ['year' => $request->integer('year')])->with('success', "{$count} baris anggaran berhasil diimpor. Sumber dan tanggal pembaruannya tercatat di Riwayat Dataset.");
    }

    /**
     * Pekerjaan yang perlu ditindaklanjuti pengelola, dari yang paling mendesak.
     *
     * @param  array<string, mixed>  $payload
     * @param  list<array{key: string, label: string, problem: string, detail: string, passed: bool}>  $checks
     * @return list<array{tone: string, icon: string, title: string, detail: string, url: string, action: string}>
     */
    private function tasks(array $payload, array $checks, int $newComplaints): array
    {
        $year = $payload['year'];
        $tasks = [];
        if ($newComplaints > 0) {
            $tasks[] = ['tone' => 'red', 'icon' => 'inbox', 'title' => "{$newComplaints} pengaduan baru belum ditanggapi", 'detail' => 'Warga menunggu tanggapan yang bisa mereka baca dengan nomor tiket.', 'url' => route('admin.complaints.index', ['status' => 'new']), 'action' => 'Tanggapi'];
        }

        $overspent = collect($payload['rows'])->filter(fn (array $row): bool => $row['type'] === 'expense' && $row['spent'] - (float) $row['amount'] >= 0.01)->count();
        if ($overspent > 0) {
            $tasks[] = ['tone' => 'red', 'icon' => 'triangle-alert', 'title' => "{$overspent} kegiatan realisasinya melebihi anggaran", 'detail' => 'Periksa kembali catatan pengeluaran kegiatan tersebut.', 'url' => route('admin.realizations.index', ['year' => $year]), 'action' => 'Periksa'];
        }

        foreach ($checks as $check) {
            if ($check['passed']) {
                continue;
            }
            $isVerification = $check['key'] === 'verification';
            $status = $payload['summary']['unverified_count'] > 0 ? 'unverified' : 'unavailable';
            $tasks[] = [
                'tone' => 'amber',
                'icon' => $isVerification ? 'shield-alert' : 'scan-search',
                'title' => $check['problem'],
                'detail' => $check['detail'],
                'url' => $isVerification ? route('admin.budgets.index', ['year' => $year, 'status' => $status]).'#data' : route('admin.budgets.index', ['year' => $year]).'#pemeriksaan',
                'action' => $isVerification ? 'Verifikasi' : 'Lihat',
            ];
        }

        return $tasks;
    }

    /**
     * Baris yang dimasukkan lewat formulir dikelompokkan per tahun dan sumber agar riwayat dataset tetap ringkas.
     *
     * @param  array<string, mixed>  $data
     */
    private function manualDataset(string $prefix, array $data, bool $isDemo, User $user): Dataset
    {
        $dataset = Dataset::firstOrCreate(
            ['name' => $prefix.' '.$data['year'], 'year' => $data['year'], 'source' => $data['source'], 'is_demo' => $isDemo],
            ['source_date' => $data['source_date'] ?? null, 'user_id' => $user->id],
        );
        if (! $dataset->wasRecentlyCreated) {
            $dataset->touch();
        }

        return $dataset;
    }

    private function audit(Request $request, string $action, string $description, ?int $year): void
    {
        ActivityLog::create(['user_id' => $request->user()->id, 'year' => $year, 'action' => $action, 'description' => mb_substr($description, 0, 500)]);
    }
}
