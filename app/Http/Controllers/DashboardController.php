<?php

namespace App\Http\Controllers;

use App\Services\BudgetDashboard;
use App\Services\BudgetImporter;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DashboardController extends Controller
{
    public function __construct(private BudgetDashboard $dashboard) {}

    public function index(Request $request): View
    {
        return view('dashboard', ['payload' => $this->dashboard->payload($request)]);
    }

    public function budgets(Request $request): View
    {
        return view('budgets.index', ['payload' => $this->dashboard->payload($request)]);
    }

    public function quality(Request $request): View
    {
        return view('quality', ['payload' => $this->dashboard->payload($request)]);
    }

    public function about(Request $request): View
    {
        return view('about', ['payload' => $this->dashboard->payload($request)]);
    }

    public function export(Request $request): StreamedResponse
    {
        $payload = $this->dashboard->payload($request);

        return response()->streamDownload(function () use ($payload): void {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, ['year', 'type', 'category', 'description', 'amount', 'source', 'verification_status', 'source_date', 'realization', 'is_demo'], ',', '"', '');
            foreach ($payload['rows'] as $row) {
                $cells = [];
                foreach (['year', 'type', 'category', 'description', 'amount', 'source', 'verification_status', 'source_date', 'realization', 'is_demo'] as $key) {
                    $value = (string) ($row[$key] ?? '');
                    $cells[] = preg_match('/^[=+@\-\t\r\n]/', $value) ? "'".$value : $value;
                } fputcsv($stream, $cells, ',', '"', '');
            }
            fclose($stream);
        }, 'apbdes-'.$payload['year'].($payload['is_demo'] ? '-DEMO' : '').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'no-store']);
    }

    public function template(): StreamedResponse
    {
        return response()->streamDownload(function (): void {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, BudgetImporter::HEADERS, ',', '"', '');
            fputcsv($stream, [2026, 'income', 'Kategori contoh', 'Uraian contoh — ganti dengan data Anda', 1000000, 'Dokumen sumber Anda', 'unverified', '2026-11-03', ''], ',', '"', '');
            fclose($stream);
        }, 'template-apbdes.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
