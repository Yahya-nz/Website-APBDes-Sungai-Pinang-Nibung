<?php

namespace Tests\Feature;

use App\Models\Complaint;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ComplaintTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_citizens_send_a_complaint_without_an_account_and_receive_a_ticket(): void
    {
        $response = $this->post(route('complaints.store'), ['topic' => 'question', 'message' => 'Kapan pembangunan sumur bor di dusun kami dimulai?', 'name' => 'Siti']);

        $complaint = Complaint::sole();
        $response->assertRedirect(route('complaints.show', ['tiket' => $complaint->ticket]));
        $this->assertMatchesRegularExpression('/^ADU-\d{4}-[A-Z2-9]{4}$/', $complaint->ticket);
        $this->assertSame('new', $complaint->status);
    }

    public function test_ticket_page_shows_the_response_but_not_the_contact_details(): void
    {
        $complaint = Complaint::factory()->resolved()->create(['name' => 'Rahmat Rahasia', 'contact' => '0812-9999', 'message' => 'Lampu jalan mati.']);

        $this->get(route('complaints.show', ['tiket' => strtolower($complaint->ticket)]))->assertOk()
            ->assertSeeText('Lampu jalan mati.')->assertSeeText('Terima kasih, sudah kami tindak lanjuti.')
            ->assertDontSeeText('Rahmat Rahasia')->assertDontSeeText('0812-9999');
        $this->get(route('complaints.show', ['tiket' => 'ADU-0000-XXXX']))->assertOk()->assertSeeText('tidak ditemukan');
    }

    public function test_spam_bots_and_short_messages_are_rejected(): void
    {
        $this->post(route('complaints.store'), ['topic' => 'question', 'message' => 'Pesan yang cukup panjang untuk lolos.', 'website' => 'http://spam.test'])->assertSessionHasErrors('website');
        $this->post(route('complaints.store'), ['topic' => 'question', 'message' => 'Pendek'])->assertSessionHasErrors('message');
        $this->assertDatabaseCount('complaints', 0);
    }

    public function test_complaints_are_rate_limited_per_visitor(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('complaints.store'), ['topic' => 'suggestion', 'message' => "Saran nomor {$i} untuk pemerintah desa."])->assertRedirect();
        }

        $this->post(route('complaints.store'), ['topic' => 'suggestion', 'message' => 'Saran keenam untuk pemerintah desa.'])->assertTooManyRequests();
    }

    public function test_only_village_officials_can_respond(): void
    {
        $complaint = Complaint::factory()->create();
        $admin = User::factory()->create(['is_admin' => true]);

        $this->patch(route('admin.complaints.update', $complaint), ['status' => 'resolved', 'response' => 'Selesai'])->assertRedirect(route('login'));
        $this->actingAs($admin)->patch(route('admin.complaints.update', $complaint), ['status' => 'resolved'])->assertSessionHasErrors('response');
        $this->actingAs($admin)->patch(route('admin.complaints.update', $complaint), ['status' => 'resolved', 'response' => 'Sudah diperbaiki minggu ini.'])->assertSessionHasNoErrors();

        $complaint->refresh();
        $this->assertSame('resolved', $complaint->status);
        $this->assertSame($admin->id, $complaint->responded_by);
        $this->assertNotNull($complaint->responded_at);
    }
}
