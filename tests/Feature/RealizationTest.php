<?php

namespace Tests\Feature;

use App\Models\BudgetLine;
use App\Models\Realization;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class RealizationTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->travelTo('2026-11-04 10:00:00');
    }

    public function test_village_officials_record_spending_and_it_is_logged(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $line = BudgetLine::factory()->create(['type' => 'expense', 'amount' => '1000000.00']);

        $response = $this->actingAs($admin)->post(route('admin.realizations.store'), ['budget_line_id' => $line->id, 'spent_on' => '2026-11-03', 'amount' => '250000', 'description' => 'Pembayaran termin 1']);

        $response->assertRedirect(route('admin.realizations.index', ['year' => 2026]))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('realizations', ['budget_line_id' => $line->id, 'amount' => '250000.00', 'user_id' => $admin->id, 'is_demo' => false]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'realization', 'user_id' => $admin->id]);
    }

    public function test_citizens_and_non_admin_accounts_cannot_record_spending(): void
    {
        $line = BudgetLine::factory()->create(['type' => 'expense']);
        $payload = ['budget_line_id' => $line->id, 'spent_on' => '2026-11-03', 'amount' => '1000', 'description' => 'Uji'];

        $this->post(route('admin.realizations.store'), $payload)->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create(['is_admin' => false]))->post(route('admin.realizations.store'), $payload)->assertForbidden();
        $this->assertDatabaseCount('realizations', 0);
    }

    public function test_spending_must_belong_to_an_expense_line_and_cannot_be_dated_in_the_future(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $income = BudgetLine::factory()->create(['type' => 'income']);
        $expense = BudgetLine::factory()->create(['type' => 'expense']);

        $this->actingAs($admin)->post(route('admin.realizations.store'), ['budget_line_id' => $income->id, 'spent_on' => '2026-11-03', 'amount' => '1000', 'description' => 'Uji'])->assertSessionHasErrors('budget_line_id');
        $this->actingAs($admin)->post(route('admin.realizations.store'), ['budget_line_id' => $expense->id, 'spent_on' => '2026-12-01', 'amount' => '1000', 'description' => 'Uji'])->assertSessionHasErrors('spent_on');
        $this->actingAs($admin)->post(route('admin.realizations.store'), ['budget_line_id' => $expense->id, 'spent_on' => '2026-11-01', 'amount' => '0', 'description' => 'Uji'])->assertSessionHasErrors('amount');
        $this->assertDatabaseCount('realizations', 0);
    }

    public function test_overspending_is_saved_with_a_warning(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $line = BudgetLine::factory()->create(['type' => 'expense', 'amount' => '1000.00']);

        $this->actingAs($admin)->post(route('admin.realizations.store'), ['budget_line_id' => $line->id, 'spent_on' => '2026-11-01', 'amount' => '1500', 'description' => 'Melebihi anggaran'])
            ->assertSessionHas('success', fn (string $message): bool => str_contains($message, 'melebihi anggarannya'));
    }

    public function test_public_monitoring_page_shows_progress_and_filters_by_month(): void
    {
        $line = BudgetLine::factory()->verified()->create(['type' => 'expense', 'category' => 'Pembangunan Desa', 'description' => 'Jalan rabat beton', 'amount' => '1000.00']);
        Realization::factory()->create(['budget_line_id' => $line->id, 'spent_on' => '2026-07-08', 'amount' => '400.00', 'description' => 'Termin Juli']);
        Realization::factory()->create(['budget_line_id' => $line->id, 'spent_on' => '2026-08-26', 'amount' => '100.00', 'description' => 'Termin Agustus']);

        $this->get(route('realizations.index', ['year' => 2026]))->assertOk()->assertSeeText('Termin Juli')->assertSeeText('Termin Agustus')->assertSeeText('50,0%');
        $filtered = $this->get(route('realizations.index', ['year' => 2026, 'month' => 8]))->assertOk()->assertSeeText('Bulan Agustus · 1 transaksi');
        $this->assertSame(['Termin Agustus'], $filtered->viewData('transactions')->pluck('description')->all());
    }

    public function test_deleting_a_budget_line_removes_its_spending_records(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $realization = Realization::factory()->create();

        $this->actingAs($admin)->delete(route('admin.budgets.destroy', $realization->budget_line_id))->assertRedirect();

        $this->assertModelMissing($realization);
    }
}
