<?php

namespace Tests\Feature;

use App\Models\BudgetLine;
use App\Models\Realization;
use App\Services\ReportBuilder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $line = BudgetLine::factory()->verified()->create(['type' => 'expense', 'category' => 'Pembangunan Desa', 'description' => 'Pembuatan sumur bor', 'amount' => '1000.00']);
        Realization::factory()->create(['budget_line_id' => $line->id, 'spent_on' => '2026-02-10', 'amount' => '100.00', 'description' => 'Pembayaran Februari']);
        Realization::factory()->create(['budget_line_id' => $line->id, 'spent_on' => '2026-08-12', 'amount' => '300.00', 'description' => 'Pembayaran Agustus']);
    }

    public function test_monthly_report_separates_period_and_cumulative_spending(): void
    {
        $report = app(ReportBuilder::class)->build(2026, 'month', 8);

        $this->assertSame('Agustus 2026', $report['label']);
        $this->assertSame(300.0, $report['totals']['period']);
        $this->assertSame(400.0, $report['totals']['cumulative']);
        $this->assertSame(600.0, $report['totals']['remaining']);
        $this->assertSame(40.0, $report['totals']['percent']);
        $this->assertSame(['Pembayaran Agustus'], array_column($report['transactions'], 'description'));
    }

    public function test_semester_report_covers_six_months(): void
    {
        $report = app(ReportBuilder::class)->build(2026, 'semester', 1);

        $this->assertSame('Semester I 2026', $report['label']);
        $this->assertSame(['2026-01-01', '2026-06-30'], [$report['start'], $report['end']]);
        $this->assertSame(100.0, $report['totals']['period']);
    }

    public function test_citizens_can_preview_and_download_reports_without_logging_in(): void
    {
        $this->get(route('reports.index', ['year' => 2026, 'period' => 'semester', 'value' => 2]))->assertOk()->assertSeeText('Semester II 2026')->assertSeeText('Pembayaran Agustus');

        $pdf = $this->get(route('reports.download', ['format' => 'pdf', 'year' => 2026, 'period' => 'month', 'value' => 8]));
        $pdf->assertOk()->assertDownload('laporan-realisasi-2026-08.pdf');
        $this->assertStringStartsWith('%PDF', $pdf->getContent());
    }

    public function test_excel_report_has_summary_activity_and_transaction_sheets(): void
    {
        $response = $this->get(route('reports.download', ['format' => 'xlsx', 'year' => 2026, 'period' => 'semester', 'value' => 2]));

        $response->assertOk()->assertDownload('laporan-realisasi-2026-semester-2.xlsx');
        $path = tempnam(sys_get_temp_dir(), 'report-');
        file_put_contents($path, $response->streamedContent());
        try {
            $book = IOFactory::load($path);
            $this->assertSame(['Rekap', 'Kegiatan', 'Transaksi'], $book->getSheetNames());
            $this->assertSame('Pembangunan Desa', $book->getSheetByName('Rekap')->getCell('A7')->getValue());
            $this->assertEquals(300, $book->getSheetByName('Rekap')->getCell('C7')->getValue());
            $book->disconnectWorksheets();
        } finally {
            @unlink($path);
        }
    }

    public function test_trial_transactions_do_not_change_the_report_filename(): void
    {
        Realization::factory()->demo()->create(['budget_line_id' => BudgetLine::first()->id, 'spent_on' => '2026-08-20']);

        $this->get(route('reports.download', ['format' => 'pdf', 'year' => 2026, 'period' => 'month', 'value' => 8]))->assertDownload('laporan-realisasi-2026-08.pdf');
    }

    public function test_unverified_activities_are_listed_but_left_out_of_report_totals(): void
    {
        $unverified = BudgetLine::factory()->create(['type' => 'expense', 'category' => 'Pembangunan Desa', 'description' => 'Insentif belum dicek', 'amount' => '500.00']);
        Realization::factory()->create(['budget_line_id' => $unverified->id, 'spent_on' => '2026-08-05', 'amount' => '250.00', 'description' => 'Pembayaran insentif']);

        $report = app(ReportBuilder::class)->build(2026, 'month', 8);

        $this->assertSame(1000.0, $report['totals']['budget']);
        $this->assertSame(400.0, $report['totals']['cumulative']);
        $this->assertSame(40.0, $report['totals']['percent']);
        $this->assertSame(['Insentif belum dicek'], $report['uncounted']['activities']);
        $this->assertNull(collect($report['activities']['Pembangunan Desa'])->firstWhere('description', 'Insentif belum dicek')['cumulative']);
        $this->get(route('reports.index', ['year' => 2026, 'period' => 'month', 'value' => 8]))->assertOk()->assertSeeText('1 kegiatan belum diverifikasi')->assertSeeText('tidak dihitung');
        $this->get(route('reports.download', ['format' => 'xlsx', 'year' => 2026, 'period' => 'month', 'value' => 8]))->assertOk();
        $this->get(route('reports.download', ['format' => 'pdf', 'year' => 2026, 'period' => 'month', 'value' => 8]))->assertOk();
    }

    public function test_unknown_formats_and_periods_are_rejected(): void
    {
        $this->get(route('reports.download', ['format' => 'docx']))->assertNotFound();
        $this->get(route('reports.download', ['format' => 'pdf', 'period' => 'quarter']))->assertSessionHasErrors('period');
    }
}
