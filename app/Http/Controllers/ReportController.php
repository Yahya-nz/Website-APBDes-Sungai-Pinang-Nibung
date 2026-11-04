<?php

namespace App\Http\Controllers;

use App\Models\BudgetLine;
use App\Services\ReportBuilder;
use App\Services\ReportSpreadsheet;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(private ReportBuilder $reports) {}

    public function index(Request $request): View
    {
        [$year, $period, $value] = $this->period($request);
        $years = BudgetLine::query()->distinct()->orderByDesc('year')->pluck('year')->map(fn ($item) => (int) $item)->all() ?: [$year];

        return view('reports.index', ['report' => $this->reports->build($year, $period, $value), 'years' => $years, 'payload' => ['year' => $year]]);
    }

    public function download(Request $request, string $format): Response|StreamedResponse
    {
        abort_unless(in_array($format, ['pdf', 'xlsx'], true), 404);
        [$year, $period, $value] = $this->period($request);
        $report = $this->reports->build($year, $period, $value);

        if ($format === 'pdf') {
            return Pdf::loadView('reports.pdf', ['report' => $report])->setPaper('a4', 'landscape')->download($this->reports->filename($report, 'pdf'));
        }

        $book = (new ReportSpreadsheet)->make($report);

        return response()->streamDownload(function () use ($book): void {
            (new Xlsx($book))->save('php://output');
            $book->disconnectWorksheets();
        }, $this->reports->filename($report, 'xlsx'), ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    /**
     * @return array{0: int, 1: string, 2: int}
     */
    private function period(Request $request): array
    {
        $validated = $request->validate([
            'year' => ['nullable', 'integer', 'between:2000,2100'],
            'period' => ['nullable', 'in:month,semester'],
            'value' => ['nullable', 'integer', 'between:1,12'],
        ]);
        $year = (int) ($validated['year'] ?? BudgetLine::max('year') ?? now()->year);
        $period = $validated['period'] ?? 'month';
        $default = $period === 'month' ? (int) now()->subMonth()->month : (now()->month <= 6 ? 1 : 2);
        $value = (int) ($validated['value'] ?? $default);
        if ($period === 'semester' && ! in_array($value, [1, 2], true)) {
            $value = 1;
        }

        return [$year, $period, $value];
    }
}
