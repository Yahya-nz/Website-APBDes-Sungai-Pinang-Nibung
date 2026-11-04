<?php

namespace Tests\Feature;

use App\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_public_information_pages_and_login_are_accessible_without_an_account(): void
    {
        $this->get(route('quality'))->assertOk();
        $this->get(route('about'))->assertOk();
        $this->get(route('login'))->assertOk()->assertSee('name="password"', false);
    }

    public function test_valid_admin_credentials_start_a_session_and_open_the_management_page(): void
    {
        $admin = User::factory()->create(['email' => 'admin-test@example.test', 'password' => 'secret-for-testing', 'is_admin' => true]);

        $response = $this->post(route('login.store'), ['email' => $admin->email, 'password' => 'secret-for-testing']);

        $response->assertRedirect(route('admin.index'));
        $this->assertAuthenticatedAs($admin);
        $this->get(route('admin.index'))->assertOk();
    }

    public function test_reader_credentials_cannot_sign_into_management(): void
    {
        $reader = User::factory()->create(['email' => 'reader-test@example.test', 'password' => 'secret-for-testing', 'is_admin' => false]);

        $response = $this->post(route('login.store'), ['email' => $reader->email, 'password' => 'secret-for-testing', 'is_admin' => true]);

        $response->assertSessionHasErrors(['email' => 'Email atau kata sandi tidak sesuai.']);
        $this->assertGuest();
        $this->assertFalse($reader->fresh()->is_admin);
    }

    public function test_repeated_failed_logins_are_rate_limited(): void
    {
        $this->freezeTime();
        $credentials = ['email' => 'rate-limit-test@example.test', 'password' => 'incorrect-password'];
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('login.store'), $credentials)->assertSessionHasErrors('email');
        }

        $response = $this->post(route('login.store'), $credentials);

        $response->assertTooManyRequests();
        $this->assertGuest();
    }

    public function test_malformed_email_input_is_rejected_without_an_internal_error(): void
    {
        $response = $this->post(route('login.store'), ['email' => ['unexpected'], 'password' => 'anything']);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_logout_ends_the_admin_session_and_protects_the_management_page(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->post(route('logout'));

        $response->assertRedirect(route('dashboard'));
        $this->assertGuest();
        $this->get(route('admin.index'))->assertRedirect(route('login'));
    }

    public function test_guests_receive_sign_in_access_without_authenticated_logout_controls(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertOk()->assertSee('Masuk pengelola')->assertDontSee('Keluar akun');
        $document = new DOMDocument;
        $document->loadHTML($response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new DOMXPath($document);
        $this->assertSame(1, $xpath->query('//header//a[@href="'.route('login').'"]')->length);
        $this->assertSame(0, $xpath->query('//form[@action="'.route('logout').'"]')->length);
    }

    public function test_authenticated_profile_exposes_a_safe_logout_action_and_escapes_account_names(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'name' => '<script>alert("account")</script>', 'email' => 'profile-test@example.test']);

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk()->assertSee($admin->name)->assertSee($admin->email)->assertDontSee($admin->name, false)->assertDontSee('Masuk pengelola');
        $document = new DOMDocument;
        $document->loadHTML($response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new DOMXPath($document);
        $logoutForms = $xpath->query('//header//form[@action="'.route('logout').'"]');
        $this->assertSame(1, $logoutForms->length);
        $this->assertSame('POST', strtoupper($logoutForms->item(0)->getAttribute('method')));
        $this->assertSame(1, $xpath->query('.//input[@name="_token" and @value!=""]', $logoutForms->item(0))->length);
        $this->assertSame(0, $xpath->query('//aside//form[@action="'.route('logout').'"]')->length);
    }
}
