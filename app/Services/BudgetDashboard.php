<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\BudgetLine;
use App\Models\Dataset;
use App\Models\Realization;
use App\Support\BudgetSearch;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class BudgetDashboard
{
    /** Warna kategori mengikuti rancangan dashboard pada proposal. */
    public const COLORS = ['#2563EB', '#22C55E', '#F59E0B', '#EF4444', '#8B5CF6', '#06B6D4', '#EC4899'];

    public const MONTHS = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

    public function payload(Request $request): array
    {
        $request->validate([
            'year' => ['nullable', 'integer', 'between:2000,2100'],
            'q' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'in:income,expense'],
            'category' => ['nullable', 'string', 'max:150'],
            'source' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'string', 'in:verified,unverified,unavailable'],
            'dataset' => ['nullable', 'integer', 'min:1'],
        ]);
        $years = BudgetLine::query()->distinct()->orderByDesc('year')->pluck('year')->map(fn ($year) => (int) $year)->all();
        $year = (int) $request->input('year', $years[0] ?? 2026);
        if (! in_array($year, $years, true)) {
            $years[] = $year;
            rsort($years, SORT_NUMERIC);
        }

        $all = BudgetLine::query()->where('year', $year)->orderBy('type')->orderBy('id')->get();
        $income = $all->where('type', 'income');
        $expense = $all->where('type', 'expense');
        $incomeTotal = Money::sum($income->pluck('amount'));
        $expenseTotal = Money::sum($expense->pluck('amount'));

        // Realisasi, penyerapan, dan sisa anggaran hanya dihitung dari kegiatan yang sudah terverifikasi.
        $counted = $expense->where('verification_status', 'verified');
        $uncounted = $expense->where('verification_status', '!=', 'verified');
        $countedIds = $counted->pluck('id')->all();
        $countedBudget = Money::sum($counted->pluck('amount'));

        $realizations = Realization::query()->whereIn('budget_line_id', $expense->pluck('id'))->orderBy('spent_on')->orderBy('id')->get();
        $spentByLine = $realizations->groupBy('budget_line_id')->map(fn (Collection $items) => Money::sum($items->pluck('amount')));
        $realizedByLine = $spentByLine->only($countedIds);
        $countedRealizations = $realizations->whereIn('budget_line_id', $countedIds);
        $uncountedRealizations = $realizations->whereNotIn('budget_line_id', $countedIds);
        $realization = $countedRealizations->isEmpty() ? null : Money::sum($countedRealizations->pluck('amount'));

        $filters = [
            'q' => mb_substr((string) $request->input('q', ''), 0, 255),
            'type' => (string) $request->input('type', ''),
            'category' => (string) $request->input('category', ''),
            'source' => (string) $request->input('source', ''),
            'status' => (string) $request->input('status', ''),
            'dataset' => (string) $request->input('dataset', ''),
        ];
        $search = new BudgetSearch($filters['q']);
        $rows = $this->filterRows($all, $filters, $search);

        return [
            'year' => $year,
            'years' => $years,
            'is_demo' => $all->contains('is_demo', true),
            'realization_is_demo' => $realizations->contains('is_demo', true),
            'source' => $all->first()?->source,
            'last_updated' => $all->max('updated_at')?->toIso8601String(),
            'summary' => [
                'income' => $incomeTotal,
                'expense' => $expenseTotal,
                'balance' => ($incomeTotal * 100 - $expenseTotal * 100) / 100,
                'financing' => 0.0,
                'realization' => $realization,
                'absorption' => $realization !== null && $countedBudget > 0 ? round($realization / $countedBudget * 100, 2) : null,
                'remaining' => $realization !== null ? ($countedBudget * 100 - $realization * 100) / 100 : null,
                'counted_budget' => $countedBudget,
                'transactions' => $countedRealizations->count(),
                'uncounted_lines' => $uncounted->count(),
                'uncounted_budget' => Money::sum($uncounted->pluck('amount')),
                'uncounted_spent' => Money::sum($uncountedRealizations->pluck('amount')),
                'uncounted_transactions' => $uncountedRealizations->count(),
                'income_count' => $income->count(),
                'expense_count' => $expense->count(),
                'verified_count' => $all->where('verification_status', 'verified')->count(),
                'unverified_count' => $all->where('verification_status', 'unverified')->count(),
                'unavailable_count' => $all->where('verification_status', 'unavailable')->count(),
                'total_count' => $all->count(),
            ],
            'income' => $this->composition($income, $incomeTotal),
            'expense' => $this->composition($expense, $expenseTotal, $realizedByLine),
            'funding_sources' => $this->composition($expense, $expenseTotal, $realizedByLine, 'funding_source'),
            'monthly' => $this->monthly($countedRealizations, $countedBudget, $year),
            'latest_realizations' => $realizations->sortByDesc(fn (Realization $item) => [$item->spent_on->timestamp, $item->id])->take(8)->map(fn (Realization $item) => $this->transaction($item, $all))->values()->all(),
            'rows' => $rows->map(fn (BudgetLine $line) => $this->row($line, $spentByLine))->values()->all(),
            'filters' => $filters,
            'search' => $search,
            'categories' => $all->pluck('category')->unique()->values()->all(),
            'funding_options' => $expense->pluck('funding_source')->filter()->unique()->sort()->values()->all(),
            'checks' => $this->fundingChecks($income, $expense),
            'activities' => ActivityLog::with('user')->where(fn ($q) => $q->where('year', $year)->orWhereNull('year'))->latest('id')->limit(8)->get()->map(fn (ActivityLog $log) => ['action' => $log->action, 'description' => $log->description, 'user' => $log->user?->name ?? 'Sistem', 'created_at' => $log->created_at->toIso8601String()])->all(),
            'datasets' => Dataset::withCount('budgetLines')->with('budgetLines:id,dataset_id,verification_status')->where('year', $year)->latest('id')->get()->map(fn (Dataset $dataset) => ['name' => $dataset->name, 'source' => $dataset->source, 'year' => $dataset->year, 'source_date' => $dataset->source_date?->toDateString(), 'created_at' => $dataset->created_at->toIso8601String(), 'status' => $dataset->budget_lines_count > 0 && $dataset->budgetLines->every(fn ($line) => $line->verification_status === 'verified') ? 'verified' : 'unverified', 'row_count' => $dataset->budget_lines_count, 'is_demo' => $dataset->is_demo])->all(),
        ];
    }

    /**
     * @param  array{q: string, type: string, category: string, source: string, status: string, dataset: string}  $filters
     */
    private function filterRows(Collection $all, array $filters, BudgetSearch $search): Collection
    {
        $rows = $all->filter(function (BudgetLine $line) use ($filters): bool {
            return ($filters['type'] === '' || $line->type === $filters['type'])
                && ($filters['category'] === '' || $line->category === $filters['category'])
                && ($filters['source'] === '' || $line->funding_source === $filters['source'])
                && ($filters['status'] === '' || $line->verification_status === $filters['status'])
                && ($filters['dataset'] === '' || (int) $line->dataset_id === (int) $filters['dataset']);
        });
        if ($search->isEmpty()) {
            return $rows;
        }

        return $rows
            ->map(fn (BudgetLine $line): array => ['line' => $line, 'score' => $search->score(['description' => $line->description, 'category' => trim($line->category.' '.$line->sub_category), 'source' => trim($line->source.' '.$line->funding_source)])])
            ->filter(fn (array $match): bool => $match['score'] !== null)
            ->sortBy([['score', 'desc'], fn (array $a, array $b): int => $b['line']->amount <=> $a['line']->amount])
            ->pluck('line');
    }

    /**
     * Total per kelompok beserta persentase dan, untuk belanja, realisasinya.
     * Realisasi dibandingkan dengan anggaran kegiatan yang sudah terverifikasi saja.
     *
     * @return list<array{name: string, amount: float, percentage: float, color: string, counted_amount: float, uncounted_lines: int, realization: float, realization_percent: float|null}>
     */
    private function composition(Collection $lines, float $total, ?Collection $realizedByLine = null, string $groupBy = 'category'): array
    {
        return $lines->filter(fn (BudgetLine $line) => filled($line->{$groupBy}))->groupBy($groupBy)->map(function (Collection $group, string $name) use ($total, $realizedByLine): array {
            $amount = Money::sum($group->pluck('amount'));
            $counted = $group->where('verification_status', 'verified');
            $countedAmount = Money::sum($counted->pluck('amount'));
            $realized = $realizedByLine ? Money::sum($counted->map(fn (BudgetLine $line) => $realizedByLine->get($line->id, 0))) : 0.0;

            return [
                'name' => $name,
                'amount' => $amount,
                'percentage' => $total > 0 ? round($amount / $total * 100, 2) : 0,
                'counted_amount' => $countedAmount,
                'uncounted_lines' => $group->count() - $counted->count(),
                'realization' => $realized,
                'realization_percent' => $countedAmount > 0 ? round($realized / $countedAmount * 100, 1) : null,
            ];
        })->sortByDesc('amount')->values()->map(fn ($item, $index) => $item + ['color' => self::COLORS[$index % count(self::COLORS)]])->all();
    }

    /**
     * Realisasi per bulan dan kumulatif, dibandingkan dengan rencana (anggaran terverifikasi) yang dibagi rata 12 bulan.
     *
     * @return list<array{month: int, label: string, realized: float, cumulative: float, planned: float}>
     */
    private function monthly(Collection $realizations, float $plannedTotal, int $year): array
    {
        $byMonth = $realizations->filter(fn (Realization $item) => (int) $item->spent_on->year === $year)->groupBy(fn (Realization $item) => (int) $item->spent_on->month);
        $cumulative = 0.0;
        $months = [];
        foreach (self::MONTHS as $month => $label) {
            $realized = Money::sum(($byMonth->get($month) ?? collect())->pluck('amount'));
            $cumulative = round($cumulative + $realized, 2);
            $months[] = ['month' => $month, 'label' => $label, 'realized' => $realized, 'cumulative' => $cumulative, 'planned' => round($plannedTotal / 12 * $month, 2)];
        }

        return $months;
    }

    /**
     * Membandingkan belanja per sumber dana dengan pendapatan dari sumber yang sama.
     *
     * @return list<array{source: string, income: float, expense: float, difference: float}>
     */
    private function fundingChecks(Collection $income, Collection $expense): array
    {
        $map = ['Dana Desa' => 'Dana Desa', 'ADD' => 'Alokasi Dana Desa', 'BHP' => 'Bagi Hasil Pajak dan Retribusi', 'PAD' => 'Pendapatan Asli Desa', 'Bunga Bank' => 'Bunga Bank'];
        $checks = [];
        foreach ($map as $source => $category) {
            $incomeAmount = Money::sum($income->where('category', $category)->pluck('amount'));
            $expenseAmount = Money::sum($expense->where('funding_source', $source)->pluck('amount'));
            if ($incomeAmount == 0 && $expenseAmount == 0) {
                continue;
            }
            $checks[] = ['source' => $source, 'income' => $incomeAmount, 'expense' => $expenseAmount, 'difference' => ($expenseAmount * 100 - $incomeAmount * 100) / 100];
        }

        return $checks;
    }

    private function transaction(Realization $item, Collection $lines): array
    {
        $line = $lines->firstWhere('id', $item->budget_line_id);

        return ['id' => $item->id, 'date' => $item->spent_on->toDateString(), 'amount' => (float) $item->amount, 'description' => $item->description, 'activity' => $line?->description, 'category' => $line?->category, 'counted' => $line?->verification_status === 'verified', 'is_demo' => $item->is_demo];
    }

    /**
     * Satu baris anggaran. "spent" adalah seluruh pengeluaran yang tercatat; "realization" hanya terisi
     * bila kegiatannya sudah terverifikasi sehingga boleh dihitung sebagai realisasi.
     */
    private function row(BudgetLine $line, Collection $spentByLine): array
    {
        $spent = $line->type === 'expense' ? (float) $spentByLine->get($line->id, 0) : null;
        $counted = $line->type === 'expense' && $line->verification_status === 'verified';
        $realized = $counted ? $spent : null;

        return [
            'id' => $line->id, 'year' => $line->year, 'type' => $line->type, 'category' => $line->category, 'sub_category' => $line->sub_category,
            'description' => $line->description, 'volume' => $line->volume, 'amount' => $line->amount, 'funding_source' => $line->funding_source,
            'spent' => $spent, 'realization_counted' => $counted,
            'realization' => $realized, 'realization_percent' => $realized !== null && (float) $line->amount > 0 ? round($realized / (float) $line->amount * 100, 1) : null,
            'source' => $line->source, 'source_date' => $line->source_date?->toDateString(), 'verification_status' => $line->verification_status,
            'updated_at' => $line->updated_at->toIso8601String(), 'is_demo' => $line->is_demo,
        ];
    }
}
