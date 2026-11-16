<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Dataset;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_dashboard_lists_follow_up_work_found_in_the_seeded_data(): void
    {
        $this->seed();
        $admin = User::where('is_admin', true)->firstOrFail();

        $response = $this->actingAs($admin)->get(route('admin.index'));

        $response->assertOk()
            ->assertSeeText('Halo, '.$admin->name)
            ->assertSeeText('5 pengaduan baru belum ditanggapi')
            ->assertSeeText('Masih ada baris yang belum diverifikasi')
            ->assertSeeText('Belanja per sumber dana belum sesuai pendapatannya')
            ->assertSeeText('Tanggal pembaruan sumber belum dicatat')
            ->assertSeeText('1 kegiatan belum diverifikasi')
            ->assertDontSeeText('contoh');
    }

    public function test_management_pages_and_actions_require_an_official_account(): void
    {
        $dataset = Dataset::factory()->create();
        $routes = ['admin.index', 'admin.budgets.index', 'admin.users.index', 'admin.activities.index'];
        foreach ($routes as $route) {
            $this->get(route($route))->assertRedirect(route('login'));
        }

        $this->actingAs(User::factory()->create(['is_admin' => false]));
        foreach ($routes as $route) {
            $this->get(route($route))->assertForbidden();
        }
        $this->delete(route('admin.datasets.destroy', $dataset))->assertForbidden();
        $this->post(route('admin.users.store'), ['name' => 'Penyusup', 'email' => 'penyusup@example.test', 'password' => 'rahasia-sekali-1', 'password_confirmation' => 'rahasia-sekali-1'])->assertForbidden();

        $this->assertModelExists($dataset);
        $this->assertDatabaseMissing('users', ['email' => 'penyusup@example.test']);
    }

    public function test_activity_history_lists_every_change_and_filters_by_type(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'name' => 'Sekretaris Desa']);
        ActivityLog::create(['user_id' => $admin->id, 'year' => 2026, 'action' => 'import', 'description' => 'Mengimpor 44 baris data']);
        ActivityLog::create(['user_id' => $admin->id, 'year' => 2026, 'action' => 'verify', 'description' => 'Menetapkan status Terverifikasi untuk 3 baris anggaran']);

        $this->actingAs($admin)->get(route('admin.activities.index'))->assertOk()
            ->assertSeeText('Mengimpor 44 baris data')
            ->assertSeeText('Menetapkan status Terverifikasi untuk 3 baris anggaran')
            ->assertSeeText('Sekretaris Desa');
        $filtered = $this->actingAs($admin)->get(route('admin.activities.index', ['aksi' => 'verify']))->assertOk();
        $this->assertSame(['verify'], $filtered->viewData('logs')->pluck('action')->all());
        $this->actingAs($admin)->get(route('admin.activities.index', ['aksi' => ['verify']]))->assertOk();
    }
}
