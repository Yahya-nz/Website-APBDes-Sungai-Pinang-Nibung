<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_officials_can_add_a_colleague_who_can_then_sign_in(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->post(route('admin.users.store'), ['name' => 'Kaur Keuangan', 'email' => 'Kaur.Keuangan@Desa.test', 'password' => 'kata-sandi-awal-1', 'password_confirmation' => 'kata-sandi-awal-1']);

        $response->assertRedirect(route('admin.users.index'))->assertSessionHasNoErrors();
        $colleague = User::where('email', 'kaur.keuangan@desa.test')->sole();
        $this->assertTrue($colleague->is_admin);
        $this->assertTrue(Hash::check('kata-sandi-awal-1', $colleague->password));
        $this->assertDatabaseHas('activity_logs', ['action' => 'user', 'user_id' => $admin->id]);

        $this->post(route('logout'));
        $this->post(route('login.store'), ['email' => 'kaur.keuangan@desa.test', 'password' => 'kata-sandi-awal-1'])->assertRedirect(route('admin.index'));
        $this->assertAuthenticatedAs($colleague);
    }

    public function test_new_accounts_need_a_unique_email_and_a_strong_password(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'email' => 'admin@desa.test']);

        $this->actingAs($admin)->post(route('admin.users.store'), ['name' => 'Akun ganda', 'email' => 'ADMIN@desa.test', 'password' => 'kata-sandi-awal-1', 'password_confirmation' => 'kata-sandi-awal-1'])
            ->assertSessionHasErrors(['email' => 'Email sudah digunakan.']);
        $this->actingAs($admin)->post(route('admin.users.store'), ['name' => 'Sandi lemah', 'email' => 'lemah@desa.test', 'password' => 'pendek', 'password_confirmation' => 'pendek'])
            ->assertSessionHasErrors('password');

        $this->assertDatabaseCount('users', 1);
    }

    public function test_revoking_access_blocks_management_but_keeps_the_account_and_its_history(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $colleague = User::factory()->create(['is_admin' => true, 'name' => 'Operator Desa']);
        ActivityLog::create(['user_id' => $colleague->id, 'action' => 'import', 'description' => 'Mengimpor data uji']);

        $this->actingAs($admin)->patch(route('admin.users.access', $colleague), ['access' => '0'])->assertRedirect(route('admin.users.index'))->assertSessionHasNoErrors();

        $this->assertFalse($colleague->fresh()->is_admin);
        $this->actingAs($admin)->get(route('admin.users.index'))->assertOk()->assertSeeText('Operator Desa')->assertSeeText('Akses dicabut');
        $this->actingAs($colleague->fresh())->get(route('admin.index'))->assertForbidden();
        $this->actingAs($colleague->fresh())->get(route('dashboard'))->assertOk()->assertDontSeeText('Dashboard Pengelola');
        $this->assertDatabaseHas('activity_logs', ['user_id' => $colleague->id, 'action' => 'import']);

        $this->actingAs($admin)->patch(route('admin.users.access', $colleague), ['access' => '1'])->assertSessionHasNoErrors();
        $this->assertTrue($colleague->fresh()->is_admin);
        $this->assertSame(2, ActivityLog::where('action', 'user')->count());
    }

    public function test_officials_cannot_revoke_their_own_access(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->patch(route('admin.users.access', $admin), ['access' => '0'])
            ->assertSessionHasErrors(['access' => 'Anda tidak dapat mencabut akses akun Anda sendiri.']);

        $this->assertTrue($admin->fresh()->is_admin);
        $this->assertDatabaseCount('activity_logs', 0);
    }
}
