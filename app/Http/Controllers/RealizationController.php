<?php

namespace App\Http\Controllers;

use App\Http\Requests\RealizationRequest;
use App\Models\ActivityLog;
use App\Models\BudgetLine;
use App\Models\Realization;
use App\Services\BudgetDashboard;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RealizationController extends Controller
{
    public function __construct(private BudgetDashboard $dashboard) {}

    /**
     * Halaman publik monitoring realisasi.
     */
    public function index(Request $request): View
    {
        $payload = $this->dashboard->payload($request);
        $month = $request->integer('month');

        return view('realizations.index', [
            'payload' => $payload,
            'month' => $month >= 1 && $month <= 12 ? $month : null,
            'transactions' => $this->transactions($payload['year'], $month)->paginate(20)->withQueryString(),
        ]);
    }

    /**
     * Halaman perangkat desa untuk mencatat pengeluaran.
     */
    public function manage(Request $request): View
    {
        $payload = $this->dashboard->payload($request);

        return view('admin.realizations', [
            'payload' => $payload,
            'activities' => BudgetLine::query()->where('year', $payload['year'])->where('type', 'expense')->orderBy('category')->orderBy('id')->get(['id', 'category', 'description', 'amount']),
            'transactions' => $this->transactions($payload['year'], null)->paginate(15)->withQueryString(),
        ]);
    }

    public function store(RealizationRequest $request): RedirectResponse
    {
        $line = BudgetLine::findOrFail($request->integer('budget_line_id'));
        $realization = DB::transaction(function () use ($request, $line): Realization {
            $realization = Realization::create(array_merge($request->validated(), ['user_id' => $request->user()->id, 'is_demo' => $request->boolean('is_demo')]));
            ActivityLog::create(['user_id' => $request->user()->id, 'year' => $line->year, 'action' => 'realization', 'description' => mb_substr('Mencatat pengeluaran Rp'.number_format((float) $realization->amount, 0, ',', '.').' untuk '.$line->description, 0, 500)]);

            return $realization;
        });

        $spent = Money::sum($line->realizations()->pluck('amount'));
        $message = 'Pengeluaran Rp'.number_format((float) $realization->amount, 0, ',', '.').' tercatat dan langsung tampil di notifikasi warga.';
        if ($spent > (float) $line->amount) {
            $message .= ' Perhatian: realisasi kegiatan ini sudah melebihi anggarannya.';
        }

        return redirect()->route('admin.realizations.index', ['year' => $line->year])->with('success', $message);
    }

    public function destroy(Request $request, Realization $realization): RedirectResponse
    {
        $year = $realization->budgetLine->year;
        DB::transaction(function () use ($request, $realization, $year): void {
            ActivityLog::create(['user_id' => $request->user()->id, 'year' => $year, 'action' => 'delete', 'description' => mb_substr('Menghapus catatan pengeluaran '.$realization->description, 0, 500)]);
            $realization->delete();
        });

        return redirect()->route('admin.realizations.index', ['year' => $year])->with('success', 'Catatan pengeluaran dihapus.');
    }

    private function transactions(int $year, ?int $month): Builder
    {
        return Realization::query()
            ->with('budgetLine:id,category,description')
            ->whereHas('budgetLine', fn ($query) => $query->where('year', $year))
            ->when($month >= 1 && $month <= 12, fn ($query) => $query->whereMonth('spent_on', $month))
            ->latest('spent_on')->latest('id');
    }
}
