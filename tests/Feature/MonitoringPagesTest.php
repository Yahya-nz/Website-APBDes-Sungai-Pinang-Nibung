<?php

namespace Tests\Feature;

use App\Models\BudgetLine;
use App\Models\Complaint;
use App\Models\Realization;
use App\Models\User;
use App\Support\Money;
use Database\Seeders\BalihoApbdes2026;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MonitoringPagesTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_seeded_baliho_data_balances_and_matches_each_sector_total(): void
    {
        $this->seed();

        $this->assertSame(549108437.83, Money::sum(BudgetLine::where('type', 'income')->pluck('amount')));
        $this->assertSame(549108437.83, Money::sum(BudgetLine::where('type', 'expense')->pluck('amount')));
        $totals = BudgetLine::where('type', 'expense')->get()->groupBy('category')->map(fn ($lines) => Money::sum($lines->pluck('amount')));
        $this->assertSame([
            'Penyelenggaraan Pemerintahan Desa' => 290241437.83,
            'Pelaksanaan Pembangunan Desa' => 215867000.0,
            'Pembinaan Kemasyarakatan Desa' => 14000000.0,
            'Pemberdayaan Masyarakat Desa' => 5000000.0,
            'Penanggulangan Bencana' => 24000000.0,
        ], $totals->all());
        $this->assertSame(count(BalihoApbdes2026::expense()), BudgetLine::where('type', 'expense')->count());
        $this->assertTrue(Realization::where('is_demo', false)->doesntExist());
        $this->assertSame(BudgetLine::where('is_demo', true)->count(), 0);
    }

    public function test_public_pages_load_for_citizens(): void
    {
        $this->seed();

        foreach (['dashboard', 'profile', 'financing', 'realizations.index', 'analytics', 'reports.index', 'complaints.create', 'quality', 'about'] as $route) {
            $this->get(route($route))->assertOk();
        }
        $this->get(route('budgets.index', ['type' => 'expense']))->assertOk()->assertSeeText('Pembuatan sumur bor')->assertSeeText('12 unit');
    }

    public function test_quality_page_flags_the_funding_source_mismatch_from_the_baliho(): void
    {
        $this->seed();

        $this->get(route('quality'))->assertOk()->assertSeeText('Perlu dicek')->assertSeeText('Insentif RT');
    }

    public function test_notification_feed_lists_latest_spending_and_new_complaints_only_for_officials(): void
    {
        $line = BudgetLine::factory()->create(['type' => 'expense', 'description' => 'Pembuatan sumur bor']);
        Realization::factory()->create(['budget_line_id' => $line->id, 'spent_on' => '2026-11-03', 'amount' => '29239000.00', 'description' => 'Termin 2']);
        Complaint::factory()->create(['message' => 'Pesan rahasia dari warga untuk perangkat desa.']);

        $public = $this->getJson(route('notifications'))->assertOk()->json();
        $this->assertSame('Pengeluaran baru: Pembuatan sumur bor', $public['items'][0]['title']);
        $this->assertStringContainsString('Rp29.239.000', $public['items'][0]['detail']);
        $this->assertCount(1, $public['items']);

        $official = $this->actingAs(User::factory()->create(['is_admin' => true]))->getJson(route('notifications'))->json();
        $this->assertCount(2, $official['items']);
    }

    public function test_officials_can_change_their_password(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'password' => 'kata-sandi-lama-1']);

        $this->actingAs($admin)->put(route('admin.settings.password'), ['current_password' => 'salah', 'password' => 'kata-sandi-baru-2', 'password_confirmation' => 'kata-sandi-baru-2'])->assertSessionHasErrors('current_password');
        $this->actingAs($admin)->put(route('admin.settings.password'), ['current_password' => 'kata-sandi-lama-1', 'password' => 'kata-sandi-baru-2', 'password_confirmation' => 'kata-sandi-baru-2'])->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('kata-sandi-baru-2', $admin->fresh()->password));
    }
}
