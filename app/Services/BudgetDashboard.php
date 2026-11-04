<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\BudgetLine;
use App\Models\Dataset;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class BudgetDashboard
{
    public function payload(Request $request): array
    {
        $request->validate(['year' => ['nullable', 'integer', 'between:2000,2100'], 'q' => ['nullable', 'string', 'max:255'], 'type' => ['nullable', 'string', 'in:income,expense'], 'category' => ['nullable', 'string', 'max:150'], 'status' => ['nullable', 'string', 'in:verified,unverified,unavailable']]);
        $years = BudgetLine::query()->distinct()->orderByDesc('year')->pluck('year')->map(fn ($year) => (int) $year)->all();
        $year = (int) $request->input('year', $years[0] ?? 2026);
        if ($year < 2000 || $year > 2100) {
            $year = $years[0] ?? 2026;
        }
        if (! in_array($year, $years, true)) {
            $years[] = $year;
            rsort($years, SORT_NUMERIC);
        }
        $all = BudgetLine::query()->where('year', $year)->orderBy('type')->orderBy('id')->get();
        $income = $all->where('type', 'income');
        $expense = $all->where('type', 'expense');
        $incomeTotal = Money::sum($income->pluck('amount'));
        $expenseTotal = Money::sum($expense->pluck('amount'));
        $canRealize = $expense->isNotEmpty() && $expense->every(fn (BudgetLine $line) => $line->verification_status === 'verified' && $line->realization !== null && ! $line->is_demo);
        $realization = $canRealize ? Money::sum($expense->pluck('realization')) : null;
        $filters = ['q' => mb_substr((string) $request->input('q', ''), 0, 255), 'type' => (string) $request->input('type', ''), 'category' => (string) $request->input('category', ''), 'status' => (string) $request->input('status', '')];
        $rows = $all->filter(function (BudgetLine $line) use ($filters): bool {
            if ($filters['type'] !== '' && $line->type !== $filters['type']) {
                return false;
            }
            if ($filters['category'] !== '' && $line->category !== $filters['category']) {
                return false;
            }
            if ($filters['status'] !== '' && $line->verification_status !== $filters['status']) {
                return false;
            }

            return $filters['q'] === '' || str_contains(mb_strtolower($line->description.' '.$line->category.' '.$line->source), mb_strtolower($filters['q']));
        });

        return ['year' => $year, 'years' => $years, 'is_demo' => $all->contains('is_demo', true), 'source' => $all->contains('is_demo', true) ? 'Dataset demonstrasi — bukan data APBDes resmi' : 'Dataset APBDes yang dikelola administrator',
            'last_updated' => $all->max('updated_at')?->toIso8601String(),
            'summary' => ['income' => $incomeTotal, 'expense' => $expenseTotal, 'balance' => ($incomeTotal * 100 - $expenseTotal * 100) / 100, 'realization' => $realization, 'absorption' => $canRealize && $expenseTotal > 0 ? round($realization / $expenseTotal * 100, 2) : null, 'remaining' => $canRealize ? ($expenseTotal * 100 - $realization * 100) / 100 : null, 'income_count' => $income->count(), 'expense_count' => $expense->count(), 'verified_count' => $all->where('verification_status', 'verified')->count(), 'unverified_count' => $all->where('verification_status', 'unverified')->count(), 'unavailable_count' => $all->where('verification_status', 'unavailable')->count(), 'total_count' => $all->count()],
            'income' => $this->composition($income, $incomeTotal), 'expense' => $this->composition($expense, $expenseTotal),
            'rows' => $rows->map(fn (BudgetLine $line) => $this->row($line))->values()->all(),
            'filters' => $filters, 'categories' => $all->pluck('category')->unique()->values()->all(),
            'activities' => ActivityLog::with('user')->where(fn ($q) => $q->where('year', $year)->orWhereNull('year'))->latest('id')->limit(8)->get()->map(fn (ActivityLog $log) => ['action' => $log->action, 'description' => $log->description, 'user' => $log->user?->name ?? 'Sistem', 'created_at' => $log->created_at->toIso8601String()])->all(),
            'datasets' => Dataset::withCount('budgetLines')->with('budgetLines:id,dataset_id,verification_status')->where('year', $year)->latest('id')->get()->map(fn (Dataset $dataset) => ['name' => $dataset->name, 'source' => $dataset->source, 'year' => $dataset->year, 'source_date' => $dataset->source_date?->toDateString(), 'created_at' => $dataset->created_at->toIso8601String(), 'status' => $dataset->budget_lines_count > 0 && $dataset->budgetLines->every(fn ($line) => $line->verification_status === 'verified') ? 'verified' : 'unverified', 'row_count' => $dataset->budget_lines_count, 'is_demo' => $dataset->is_demo])->all(),
        ];
    }

    private function composition(Collection $lines, float $total): array
    {
        $colors = ['#0D5163', '#2E8199', '#D49A2A', '#8FBFCC', '#C8DDE2'];

        return $lines->groupBy('category')->map(function (Collection $group, string $name) use ($total): array {
            $amount = Money::sum($group->pluck('amount'));

            return ['name' => $name, 'amount' => $amount, 'percentage' => $total > 0 ? round($amount / $total * 100, 2) : 0];
        })->sortByDesc('amount')->values()->map(fn ($item, $index) => $item + ['color' => $colors[$index % count($colors)]])->all();
    }

    private function row(BudgetLine $line): array
    {
        return ['id' => $line->id, 'year' => $line->year, 'type' => $line->type, 'category' => $line->category, 'description' => $line->description, 'amount' => $line->amount, 'realization' => $line->realization, 'source' => $line->source, 'source_date' => $line->source_date?->toDateString(), 'verification_status' => $line->verification_status, 'updated_at' => $line->updated_at->toIso8601String(), 'is_demo' => $line->is_demo];
    }
}
