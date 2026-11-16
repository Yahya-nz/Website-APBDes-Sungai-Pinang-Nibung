<?php

namespace Tests\Feature;

use App\Models\Complaint;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ComplaintInboxTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_inbox_lists_complaints_in_a_table_with_numbered_pages(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        Complaint::factory()->count(12)->create();

        $first = $this->actingAs($admin)->get(route('admin.complaints.index'))->assertOk()
            ->assertSee('<table class="table stack complaint-table">', false)
            ->assertSeeText('Menampilkan 1–10 dari 12 pengaduan');
        $this->assertCount(10, $first->viewData('complaints'));

        $second = $this->actingAs($admin)->get(route('admin.complaints.index', ['page' => 2]))->assertOk()->assertSeeText('Menampilkan 11–12 dari 12 pengaduan');
        $this->assertCount(2, $second->viewData('complaints'));
    }

    public function test_officials_can_search_by_reporter_name_or_ticket(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $siti = Complaint::factory()->create(['name' => 'Siti Aminah', 'status' => 'new']);
        $sitiResolved = Complaint::factory()->resolved()->create(['name' => 'siti rahma']);
        $rahmat = Complaint::factory()->create(['name' => 'Rahmat']);

        $byName = $this->actingAs($admin)->get(route('admin.complaints.index', ['q' => 'Siti']))->assertOk()->assertSeeText('2 pengaduan cocok dengan “Siti”');
        $this->assertEqualsCanonicalizing([$siti->id, $sitiResolved->id], $byName->viewData('complaints')->pluck('id')->all());
        $this->assertSame(1, (int) $byName->viewData('counts')['resolved']);

        $byNameAndStatus = $this->actingAs($admin)->get(route('admin.complaints.index', ['q' => 'siti', 'status' => 'new']));
        $this->assertSame([$siti->id], $byNameAndStatus->viewData('complaints')->pluck('id')->all());

        $byTicket = $this->actingAs($admin)->get(route('admin.complaints.index', ['q' => $rahmat->ticket]));
        $this->assertSame([$rahmat->id], $byTicket->viewData('complaints')->pluck('id')->all());

        $wildcard = $this->actingAs($admin)->get(route('admin.complaints.index', ['q' => '%']));
        $this->assertCount(0, $wildcard->viewData('complaints'));
    }

    public function test_saving_a_response_returns_to_the_same_search_and_page(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $complaint = Complaint::factory()->create(['name' => 'Siti Aminah']);

        $response = $this->actingAs($admin)->patch(route('admin.complaints.update', $complaint), [
            'status' => 'in_progress', 'response' => 'Sedang kami periksa.', 'status_filter' => 'new', 'q_filter' => 'Siti', 'page_filter' => 2,
        ]);

        $response->assertRedirect(route('admin.complaints.index', ['status' => 'new', 'q' => 'Siti', 'page' => 2]));
        $this->assertSame('in_progress', $complaint->fresh()->status);
    }
}
