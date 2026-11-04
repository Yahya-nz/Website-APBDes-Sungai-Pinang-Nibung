<?php

namespace App\Http\Controllers;

use App\Http\Requests\BudgetLineRequest;
use App\Http\Requests\ImportBudgetRequest;
use App\Models\ActivityLog;
use App\Models\BudgetLine;
use App\Models\Dataset;
use App\Services\BudgetDashboard;
use App\Services\BudgetImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function index(Request $request, BudgetDashboard $dashboard): View
    {
        return view('admin.index', ['payload' => $dashboard->payload($request)]);
    }

    public function store(BudgetLineRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $data = $request->validated();
            $dataset = Dataset::create(['name' => 'Input manual '.$data['year'], 'year' => $data['year'], 'source' => $data['source'], 'source_date' => $data['source_date'] ?? null, 'is_demo' => false, 'user_id' => $request->user()->id]);
            $line = BudgetLine::create($data + ['dataset_id' => $dataset->id, 'is_demo' => false]);
            $this->audit($request, 'create', 'Menambahkan '.$line->description, $line->year);
        });

        return redirect()->route('admin.index', ['year' => $request->integer('year')])->with('success', 'Data anggaran berhasil ditambahkan.');
    }

    public function update(BudgetLineRequest $request, BudgetLine $budget): RedirectResponse
    {
        DB::transaction(function () use ($request, $budget): void {
            $data = $request->validated();
            if ((int) $data['year'] !== $budget->year) {
                $dataset = Dataset::create(['name' => 'Pembaruan manual '.$data['year'], 'year' => $data['year'], 'source' => $data['source'], 'source_date' => $data['source_date'] ?? null, 'is_demo' => $budget->is_demo, 'user_id' => $request->user()->id]);
                $data['dataset_id'] = $dataset->id;
            }
            $budget->update($data);
            $this->audit($request, 'update', 'Memperbarui '.$budget->description, $budget->year);
        });

        return redirect()->route('admin.index', ['year' => $budget->year])->with('success', 'Perubahan data anggaran berhasil disimpan.');
    }

    public function destroy(Request $request, BudgetLine $budget): RedirectResponse
    {
        $year = $budget->year;
        DB::transaction(function () use ($request, $budget): void {
            $this->audit($request, 'delete', 'Menghapus '.$budget->description, $budget->year);
            $budget->delete();
        });

        return redirect()->route('admin.index', ['year' => $year])->with('success', 'Data anggaran berhasil dihapus.');
    }

    public function import(ImportBudgetRequest $request, BudgetImporter $importer): RedirectResponse
    {
        $count = $importer->import($request->file('file'), $request->safe()->except('file'), $request->user());

        return redirect()->route('admin.index', ['year' => $request->input('year', 2026)])->with('success', "{$count} baris anggaran berhasil diimpor. Sumber dan riwayat impor sudah dicatat.");
    }

    private function audit(Request $request, string $action, string $description, int $year): void
    {
        ActivityLog::create(['user_id' => $request->user()->id, 'year' => $year, 'action' => $action, 'description' => mb_substr($description, 0, 500)]);
    }
}
