<?php

namespace Tests\Feature;

use App\Models\Complaint;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
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

    public function test_recovery_pin_is_stored_hashed_and_needs_a_contact(): void
    {
        $this->post(route('complaints.store'), ['topic' => 'question', 'message' => 'Kapan jalan rabat beton dikerjakan?', 'recovery_pin' => '2468'])
            ->assertSessionHasErrors(['contact' => 'Isi nomor HP atau email agar PIN pemulihan bisa dipakai.']);

        $this->post(route('complaints.store'), ['topic' => 'question', 'message' => 'Kapan jalan rabat beton dikerjakan?', 'contact' => '+62 812-3456-7890', 'recovery_pin' => '2468'])->assertRedirect();

        $complaint = Complaint::sole();
        $this->assertSame('081234567890', $complaint->contact_key);
        $this->assertNotSame('2468', $complaint->recovery_pin);
        $this->assertTrue(Hash::check('2468', $complaint->recovery_pin));
        $this->assertArrayNotHasKey('recovery_pin', $complaint->toArray());
    }

    public function test_lost_tickets_are_recovered_with_contact_and_pin_only(): void
    {
        $this->post(route('complaints.store'), ['topic' => 'complaint', 'message' => 'Lampu jalan di depan balai desa mati.', 'contact' => '+62 812-3456-7890', 'recovery_pin' => '2468']);
        $mine = Complaint::sole();
        $other = Complaint::factory()->create(['contact' => '0899-1111', 'contact_key' => '08991111', 'recovery_pin' => '2468']);

        $response = $this->from(route('complaints.show'))->post(route('complaints.recover'), ['contact' => '0812 3456 7890', 'recovery_pin' => '2468']);

        $response->assertRedirect(route('complaints.show').'#tiket-saya')->assertSessionHas('success', fn (string $message): bool => str_contains($message, $mine->ticket) && ! str_contains($message, $other->ticket));
        $this->assertContains($mine->ticket, json_decode($response->getCookie('tiket_saya')->getValue(), true));

        $this->from(route('complaints.show'))->post(route('complaints.recover'), ['contact' => '081234567890', 'recovery_pin' => '1357'])
            ->assertRedirect(route('complaints.show'))->assertSessionHasErrorsIn('recovery', 'recovery_pin');
    }

    public function test_recovery_attempts_are_rate_limited(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('complaints.recover'), ['contact' => '081234567890', 'recovery_pin' => '0000']);
        }

        $this->post(route('complaints.recover'), ['contact' => '081234567890', 'recovery_pin' => '0000'])->assertTooManyRequests();
    }

    public function test_saved_tickets_can_be_filtered_by_status_and_topic_and_removed(): void
    {
        $question = Complaint::factory()->create(['topic' => 'question', 'status' => 'new']);
        $suggestion = Complaint::factory()->resolved()->create(['topic' => 'suggestion']);
        $complaint = Complaint::factory()->resolved()->create(['topic' => 'complaint']);
        $stranger = Complaint::factory()->resolved()->create(['topic' => 'complaint']);
        $cookie = json_encode([$question->ticket, $suggestion->ticket, $complaint->ticket]);

        $all = $this->withCookie('tiket_saya', $cookie)->get(route('complaints.show'))->assertOk()->assertSeeText('Tiket saya');
        $this->assertEqualsCanonicalizing([$question->ticket, $suggestion->ticket, $complaint->ticket], $all->viewData('saved')->pluck('ticket')->all());
        $this->assertNotContains($stranger->ticket, $all->viewData('saved')->pluck('ticket')->all());

        $resolved = $this->withCookie('tiket_saya', $cookie)->get(route('complaints.show', ['status' => 'resolved']));
        $this->assertEqualsCanonicalizing([$suggestion->ticket, $complaint->ticket], $resolved->viewData('saved')->pluck('ticket')->all());

        $resolvedComplaints = $this->withCookie('tiket_saya', $cookie)->get(route('complaints.show', ['status' => 'resolved', 'jenis' => 'complaint']));
        $this->assertSame([$complaint->ticket], $resolvedComplaints->viewData('saved')->pluck('ticket')->all());

        $forget = $this->withCookie('tiket_saya', $cookie)->delete(route('complaints.forget', $question->ticket));
        $this->assertNotContains($question->ticket, json_decode($forget->getCookie('tiket_saya')->getValue(), true));
        $this->assertModelExists($question);
    }

    public function test_opening_a_ticket_saves_it_and_offers_a_receipt_without_personal_details(): void
    {
        $complaint = Complaint::factory()->create(['name' => 'Rahmat Rahasia', 'contact' => '0812-9999']);

        $page = $this->get(route('complaints.show', ['tiket' => $complaint->ticket]))->assertOk()->assertSeeText('Unduh bukti tiket');
        $this->assertContains($complaint->ticket, json_decode($page->getCookie('tiket_saya')->getValue(), true));

        $receipt = $this->get(route('complaints.receipt', ['tiket' => $complaint->ticket]));
        $receipt->assertOk()->assertDownload('bukti-tiket-'.$complaint->ticket.'.txt');
        $this->assertStringContainsString($complaint->ticket, $receipt->getContent());
        $this->assertStringNotContainsString('Rahmat Rahasia', $receipt->getContent());
        $this->get(route('complaints.receipt', ['tiket' => 'ADU-0000-XXXX']))->assertNotFound();
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
