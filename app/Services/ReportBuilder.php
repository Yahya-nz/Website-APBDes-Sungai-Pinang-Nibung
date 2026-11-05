<?php

namespace App\Services;

use App\Models\BudgetLine;
use App\Models\Realization;
use App\Support\Money;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * Menyusun laporan realisasi anggaran untuk satu bulan atau satu semester.
 */
class ReportBuilder
{
    /**
     * @return array<string, mixed>
     */
    public function build(int $year, string $period, int $value): array
    {
        [$start, $end, $label] = $this->range($year, $period, $value);

        $lines = BudgetLine::query()->where('year', $year)->orderBy('id')->get();
        $expense = $lines->where('type', 'expense');
        $income = $lines->where('type', 'income');
        $realizations = Realization::query()->with('budgetLine')->whereIn('budget_line_id', $expense->pluck('id'))->where('spent_on', '<=', $end->toDateString())->orderBy('spent_on')->orderBy('id')->get();
        $inPeriod = $realizations->filter(fn (Realization $item) => $item->spent_on->betweenIncluded($start, $end));

        // Kegiatan yang belum terverifikasi tetap dicantumkan, tetapi realisasinya tidak ikut dihitung.
        $activities = $expense->map(function (BudgetLine $line) use ($realizations, $inPeriod): array {
            $budget = (float) $line->amount;
            $base = ['category' => $line->category, 'description' => $line->description, 'volume' => $line->volume, 'funding_source' => $line->funding_source, 'budget' => $budget, 'counted' => $line->verification_status === 'verified'];
            if (! $base['counted']) {
                return $base + ['period' => null, 'cumulative' => null, 'remaining' => null, 'percent' => null];
            }
            $period = Money::sum($inPeriod->where('budget_line_id', $line->id)->pluck('amount'));
            $cumulative = Money::sum($realizations->where('budget_line_id', $line->id)->pluck('amount'));

            return $base + ['period' => $period, 'cumulative' => $cumulative, 'remaining' => round($budget - $cumulative, 2), 'percent' => $budget > 0 ? round($cumulative / $budget * 100, 1) : 0.0];
        })->values();
        $counted = $activities->where('counted', true);
        $uncounted = $activities->where('counted', false);

        $categories = $activities->groupBy('category')->map(fn (Collection $items, string $name) => $this->totals($items->where('counted', true)) + ['name' => $name, 'uncounted' => $items->where('counted', false)->count()])->values();

        return [
            'year' => $year,
            'period' => $period,
            'value' => $value,
            'label' => $label,
            'start' => $start->toDateString(),
            'end' => $end->toDateString(),
            'income_total' => Money::sum($income->pluck('amount')),
            'income' => $income->map(fn (BudgetLine $line) => ['name' => $line->description, 'amount' => (float) $line->amount])->values()->all(),
            'categories' => $categories->all(),
            'activities' => $activities->groupBy('category')->map(fn (Collection $items) => $items->values()->all())->all(),
            'transactions' => $inPeriod->map(fn (Realization $item) => ['date' => $item->spent_on->toDateString(), 'activity' => $item->budgetLine?->description, 'category' => $item->budgetLine?->category, 'description' => $item->description, 'amount' => (float) $item->amount, 'counted' => $item->budgetLine?->verification_status === 'verified', 'is_demo' => $item->is_demo])->values()->all(),
            'totals' => $this->totals($counted),
            'uncounted' => ['activities' => $uncounted->pluck('description')->values()->all(), 'budget' => Money::sum($uncounted->pluck('budget'))],
            'is_demo' => $realizations->contains('is_demo', true),
            'has_data' => $expense->isNotEmpty(),
            'generated_at' => now()->timezone('Asia/Jakarta')->toIso8601String(),
        ];
    }

    /**
     * Nama berkas tanpa spasi, misalnya laporan-realisasi-2026-semester-1.pdf.
     */
    public function filename(array $report, string $extension): string
    {
        $suffix = $report['period'] === 'month' ? sprintf('%02d', $report['value']) : 'semester-'.$report['value'];

        return 'laporan-realisasi-'.$report['year'].'-'.$suffix.'.'.$extension;
    }

    /**
     * @return array{0: Carbon, 1: Carbon, 2: string}
     */
    private function range(int $year, string $period, int $value): array
    {
        if ($period === 'month' && $value >= 1 && $value <= 12) {
            $start = Carbon::create($year, $value, 1)->startOfDay();

            return [$start, $start->copy()->endOfMonth(), BudgetDashboard::MONTHS[$value].' '.$year];
        }
        if ($period === 'semester' && in_array($value, [1, 2], true)) {
            $start = Carbon::create($year, $value === 1 ? 1 : 7, 1)->startOfDay();

            return [$start, $start->copy()->addMonths(5)->endOfMonth(), 'Semester '.($value === 1 ? 'I' : 'II').' '.$year];
        }

        throw new InvalidArgumentException('Periode laporan tidak dikenal.');
    }

    /**
     * @return array{budget: float, period: float, cumulative: float, remaining: float, percent: float}
     */
    private function totals(Collection $items): array
    {
        $budget = Money::sum($items->pluck('budget'));
        $cumulative = Money::sum($items->pluck('cumulative'));

        return ['budget' => $budget, 'period' => Money::sum($items->pluck('period')), 'cumulative' => $cumulative, 'remaining' => round($budget - $cumulative, 2), 'percent' => $budget > 0 ? round($cumulative / $budget * 100, 1) : 0.0];
    }
}
