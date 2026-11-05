<?php

namespace Tests\Feature;

use App\Models\BudgetLine;
use App\Models\Realization;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_dashboard_totals_preserve_cents_and_exclude_other_years(): void
    {
        BudgetLine::factory()->create(['amount' => '0.10']);
        BudgetLine::factory()->create(['amount' => '0.20']);
        BudgetLine::factory()->create(['amount' => '0.05', 'type' => 'expense']);
        BudgetLine::factory()->create(['amount' => '999999.99', 'year' => 2025]);

        $response = $this->get(route('dashboard', ['year' => 2026]));

        $response->assertOk();
        $summary = $response->viewData('payload')['summary'];
        $this->assertSame(0.30, $summary['income']);
        $this->assertSame(0.05, $summary['expense']);
        $this->assertSame(0.25, $summary['balance']);
    }

    public function test_combined_search_and_filters_leave_the_year_summary_intact(): void
    {
        $matching = BudgetLine::factory()->create(['description' => 'Jalan kebun', 'type' => 'expense', 'category' => 'Pembangunan', 'amount' => '2000.00', 'verification_status' => 'verified']);
        BudgetLine::factory()->create(['description' => 'Jalan dusun', 'type' => 'expense', 'category' => 'Pembangunan', 'amount' => '500.00', 'verification_status' => 'unverified']);
        BudgetLine::factory()->create(['description' => 'Jalan lain', 'type' => 'income', 'category' => 'Pembangunan', 'amount' => '1000.00']);
        BudgetLine::factory()->create(['description' => 'Jalan tahun lalu', 'type' => 'expense', 'category' => 'Pembangunan', 'verification_status' => 'verified', 'year' => 2025]);

        $response = $this->get(route('budgets.index', ['year' => 2026, 'q' => 'KEBUN', 'type' => 'expense', 'category' => 'Pembangunan', 'status' => 'verified']));

        $response->assertOk();
        $payload = $response->viewData('payload');
        $this->assertSame([$matching->id], array_column($payload['rows'], 'id'));
        $this->assertSame(2500.00, $payload['summary']['expense']);
    }

    public function test_realization_is_unavailable_until_spending_is_recorded(): void
    {
        BudgetLine::factory()->create(['type' => 'expense', 'amount' => '1000.00', 'verification_status' => 'verified']);

        $response = $this->get(route('dashboard', ['year' => 2026]));

        $response->assertOk()->assertSeeText('Belum ada pengeluaran yang dicatat');
        $summary = $response->viewData('payload')['summary'];
        $this->assertNull($summary['realization']);
        $this->assertNull($summary['absorption']);
        $this->assertNull($summary['remaining']);
    }

    public function test_recorded_spending_produces_consistent_indicators(): void
    {
        $first = BudgetLine::factory()->verified()->create(['type' => 'expense', 'amount' => '1000.00']);
        BudgetLine::factory()->verified()->create(['type' => 'expense', 'amount' => '1000.00']);
        Realization::factory()->create(['budget_line_id' => $first->id, 'amount' => '200.00']);
        Realization::factory()->create(['budget_line_id' => $first->id, 'amount' => '300.00']);

        $response = $this->get(route('dashboard', ['year' => 2026]));

        $response->assertOk();
        $payload = $response->viewData('payload');
        $this->assertSame(500.00, $payload['summary']['realization']);
        $this->assertSame(25.00, $payload['summary']['absorption']);
        $this->assertSame(1500.00, $payload['summary']['remaining']);
        $this->assertSame(500.00, $payload['rows'][0]['realization']);
        $this->assertSame(50.0, $payload['rows'][0]['realization_percent']);
        $response->assertSeeText('Sisa anggaran')->assertSeeText('Rp1.500,00');
    }

    public function test_spending_on_unverified_activities_is_left_out_of_realization_and_absorption(): void
    {
        $verified = BudgetLine::factory()->verified()->create(['type' => 'expense', 'category' => 'Pembangunan', 'description' => 'Jalan terverifikasi', 'amount' => '1000.00']);
        $unverified = BudgetLine::factory()->create(['type' => 'expense', 'category' => 'Pembangunan', 'description' => 'Insentif belum dicek', 'amount' => '1000.00']);
        Realization::factory()->create(['budget_line_id' => $verified->id, 'amount' => '500.00', 'spent_on' => '2026-03-15']);
        Realization::factory()->demo()->create(['budget_line_id' => $unverified->id, 'amount' => '200.00', 'spent_on' => '2026-03-20']);

        $response = $this->get(route('dashboard', ['year' => 2026]));

        $response->assertOk()
            ->assertSeeText('1 kegiatan belum diverifikasi')
            ->assertSeeText('tidak dihitung')
            ->assertDontSeeText('contoh');
        $payload = $response->viewData('payload');
        $this->assertSame(500.00, $payload['summary']['realization']);
        $this->assertSame(50.00, $payload['summary']['absorption']);
        $this->assertSame(500.00, $payload['summary']['remaining']);
        $this->assertSame(1000.00, $payload['summary']['counted_budget']);
        $this->assertSame(1, $payload['summary']['transactions']);
        $this->assertSame(200.00, $payload['summary']['uncounted_spent']);
        $this->assertSame(['counted_amount' => 1000.0, 'realization' => 500.0, 'realization_percent' => 50.0], collect($payload['expense'][0])->only(['counted_amount', 'realization', 'realization_percent'])->all());
        $this->assertSame(500.0, $payload['monthly'][2]['realized']);
        $row = collect($payload['rows'])->firstWhere('description', 'Insentif belum dicek');
        $this->assertNull($row['realization']);
        $this->assertNull($row['realization_percent']);
        $this->assertSame(200.0, $row['spent']);
        $this->get(route('budgets.index', ['type' => 'expense', 'year' => 2026]))->assertOk()->assertSeeText('Belum dihitung');
    }

    public function test_budget_descriptions_are_escaped_in_public_html(): void
    {
        BudgetLine::factory()->create(['description' => '<script>alert("budget")</script>']);

        $response = $this->get(route('budgets.index'));

        $response->assertOk()->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert("budget")</script>', false);
    }

    public function test_export_protects_spreadsheet_formula_cells_and_respects_year_filter(): void
    {
        BudgetLine::factory()->create(['description' => '=HYPERLINK("https://example.invalid","test")', 'year' => 2026]);
        BudgetLine::factory()->create(['description' => 'Other year record', 'year' => 2025]);

        $response = $this->get(route('export', ['year' => 2026]));

        $response->assertOk()->assertDownload();
        $csv = $response->streamedContent();
        $this->assertStringContainsString("'=HYPERLINK", $csv);
        $this->assertStringNotContainsString('Other year record', $csv);
    }

    public function test_malformed_filter_values_cannot_crash_the_dashboard(): void
    {
        BudgetLine::factory()->create();

        $response = $this->get(route('dashboard', ['q' => ['unexpected'], 'type' => ['expense'], 'year' => ['2026']]));

        $this->assertLessThan(500, $response->status());
    }

    public function test_empty_selected_year_reports_unavailable_income_and_expense_instead_of_zero(): void
    {
        BudgetLine::factory()->create(['year' => 2026]);

        $response = $this->get(route('dashboard', ['year' => 2027]));

        $response->assertOk()->assertSeeText('Belum tersedia')->assertDontSeeText('0,00')->assertDontSeeText('penyerapan anggaran');
        $payload = $response->viewData('payload');
        $this->assertSame(2027, $payload['year']);
        $this->assertContains(2027, $payload['years']);
        $this->assertSame(0, $payload['summary']['total_count']);
    }

    public function test_empty_budget_listing_does_not_claim_a_zero_rupiah_budget(): void
    {
        BudgetLine::factory()->create(['year' => 2026]);

        $response = $this->get(route('budgets.index', ['year' => 2027]));

        $response->assertOk()->assertSeeText('Belum tersedia')->assertDontSeeText('Rp0,00');
    }

    public function test_explicit_zero_budget_values_remain_zero_instead_of_becoming_unavailable(): void
    {
        BudgetLine::factory()->create(['type' => 'income', 'amount' => '0.00']);
        BudgetLine::factory()->create(['type' => 'expense', 'amount' => '0.00']);

        $response = $this->get(route('dashboard', ['year' => 2026]));

        $response->assertOk();
        $document = new DOMDocument;
        $document->loadHTML($response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new DOMXPath($document);
        foreach (['Total pendapatan', 'Total belanja'] as $label) {
            $metric = $xpath->query('//article[.//span[normalize-space()="'.$label.'"]]')->item(0);
            $this->assertNotNull($metric);
            $this->assertStringContainsString('0,00', $metric->textContent);
            $this->assertStringNotContainsString('Belum tersedia', $metric->textContent);
        }
    }

    public function test_zero_budget_with_spending_keeps_zero_remaining_without_inventing_a_percentage(): void
    {
        $line = BudgetLine::factory()->verified()->create(['type' => 'expense', 'amount' => '0.00']);
        Realization::factory()->create(['budget_line_id' => $line->id, 'amount' => '0.00']);

        $response = $this->get(route('dashboard'));

        $response->assertOk()->assertSeeText('Sisa anggaran')->assertSeeText('Rp0,00')->assertSeeText('Penyerapan belum dapat dihitung')->assertDontSeeText('0,0% penyerapan');
        $this->assertNull($response->viewData('payload')['summary']['absorption']);
    }

    public function test_search_requires_every_word_but_allows_them_in_different_fields(): void
    {
        BudgetLine::factory()->create(['type' => 'expense', 'category' => 'Pembangunan Desa', 'description' => 'Peningkatan jalan lingkungan']);
        BudgetLine::factory()->create(['type' => 'expense', 'category' => 'Pembinaan Kemasyarakatan', 'description' => 'Kegiatan kepemudaan']);

        $response = $this->get(route('budgets.index', ['q' => 'jalan desa']));

        $rows = $response->viewData('payload')['rows'];
        $this->assertSame(['Peningkatan jalan lingkungan'], array_column($rows, 'description'));
    }

    public function test_short_search_words_only_match_the_start_of_a_word(): void
    {
        BudgetLine::factory()->create(['description' => 'Pembangunan jembatan']);
        BudgetLine::factory()->create(['description' => 'Anggaran posyandu']);

        $response = $this->get(route('budgets.index', ['q' => 'an']));

        $this->assertSame(['Anggaran posyandu'], array_column($response->viewData('payload')['rows'], 'description'));
    }

    public function test_search_results_rank_description_matches_above_source_only_matches(): void
    {
        BudgetLine::factory()->create(['description' => 'Belanja alat tulis', 'source' => 'Dokumen posyandu tahunan', 'amount' => '9000000.00']);
        BudgetLine::factory()->create(['description' => 'Pembinaan posyandu balita', 'amount' => '100.00']);

        $response = $this->get(route('budgets.index', ['q' => 'posyandu']));

        $this->assertSame(['Pembinaan posyandu balita', 'Belanja alat tulis'], array_column($response->viewData('payload')['rows'], 'description'));
    }

    public function test_search_highlights_matches_without_unescaping_the_text(): void
    {
        BudgetLine::factory()->create(['description' => 'Posyandu <b>melati</b>']);

        $response = $this->get(route('budgets.index', ['q' => 'posyandu']));

        $response->assertOk()->assertSee('<mark>Posyandu</mark> &lt;b&gt;melati&lt;/b&gt;', false);
    }
}
