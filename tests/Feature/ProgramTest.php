<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Program;
use App\Models\ProgramVote;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ProgramTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_citizens_see_village_plans_with_vote_counts_and_percentages(): void
    {
        $program = Program::factory()->create(['title' => 'Lampu jalan tenaga surya']);
        foreach (['agree', 'agree', 'agree', 'disagree'] as $index => $choice) {
            ProgramVote::create(['program_id' => $program->id, 'choice' => $choice, 'voter_key' => hash('sha256', "uji-{$index}")]);
        }

        $this->get(route('programs.index'))->assertOk()
            ->assertSeeText('Lampu jalan tenaga surya')
            ->assertSeeText('3 suara · 75,0%')
            ->assertSeeText('1 suara · 25,0%')
            ->assertSeeText('Total 4 suara warga.');
        $this->assertSame(['agree' => 3, 'disagree' => 1, 'total' => 4, 'agree_percent' => 75.0, 'disagree_percent' => 25.0], $program->fresh()->results());
    }

    public function test_one_device_has_one_vote_per_program_and_can_change_it(): void
    {
        $program = Program::factory()->create();

        $first = $this->post(route('programs.vote', $program), ['choice' => 'agree']);
        $first->assertRedirect(route('programs.index').'#program-'.$program->id)->assertSessionHas('success');
        $token = $first->getCookie('pemilih_program')->getValue();

        $this->withCookie('pemilih_program', $token)->post(route('programs.vote', $program), ['choice' => 'disagree'])
            ->assertSessionHas('success', 'Pilihan Anda diubah menjadi Tidak setuju.');
        $this->assertSame(1, ProgramVote::count());
        $this->assertSame('disagree', ProgramVote::sole()->choice);
        $this->get(route('programs.index'))->assertSeeText('Pilihan Anda: Tidak setuju');

        $this->defaultCookies = [];
        $this->post(route('programs.vote', $program), ['choice' => 'agree'])->assertSessionHas('success');
        $this->assertSame(['agree' => 1, 'disagree' => 1, 'total' => 2, 'agree_percent' => 50.0, 'disagree_percent' => 50.0], $program->fresh()->results());
    }

    public function test_closed_or_expired_voting_and_unknown_choices_are_rejected(): void
    {
        $closed = Program::factory()->closed()->create();
        $expired = Program::factory()->create(['voting_ends_at' => now()->subDay()->toDateString()]);
        $open = Program::factory()->create(['voting_ends_at' => now()->toDateString()]);

        $this->post(route('programs.vote', $closed), ['choice' => 'agree'])->assertSessionHasErrors(['choice' => 'Voting untuk program ini sudah ditutup.']);
        $this->post(route('programs.vote', $expired), ['choice' => 'agree'])->assertSessionHasErrors('choice');
        $this->post(route('programs.vote', $open), ['choice' => 'maybe'])->assertSessionHasErrors('choice');
        $this->assertSame(0, ProgramVote::count());

        $this->post(route('programs.vote', $open), ['choice' => 'agree'])->assertSessionHasNoErrors();
        $this->assertSame(1, ProgramVote::count());
    }

    public function test_officials_add_update_and_delete_plans_with_an_audit_trail(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $data = ['title' => 'Pelatihan usaha olahan ikan', 'description' => 'Pelatihan membuat kerupuk ikan untuk kelompok PKK.', 'category' => 'Pemberdayaan Masyarakat Desa', 'location' => 'Balai desa', 'year' => 2027, 'estimated_budget' => '20000000', 'status' => 'proposed', 'voting_open' => '1'];

        $this->actingAs($admin)->get(route('admin.programs.create'))->assertOk();
        $this->actingAs($admin)->post(route('admin.programs.store'), $data)->assertRedirect(route('admin.programs.index'))->assertSessionHasNoErrors();
        $program = Program::sole();
        $this->assertTrue($program->voting_open);
        $this->assertSame($admin->id, $program->user_id);

        $unchecked = array_diff_key(array_replace($data, ['status' => 'planned']), ['voting_open' => true]);
        $this->actingAs($admin)->patch(route('admin.programs.update', $program), $unchecked)->assertSessionHasNoErrors();
        $this->assertSame('planned', $program->fresh()->status);
        $this->assertFalse($program->fresh()->voting_open);

        ProgramVote::create(['program_id' => $program->id, 'choice' => 'agree', 'voter_key' => hash('sha256', 'uji')]);
        $this->actingAs($admin)->get(route('admin.programs.index'))->assertOk()->assertSeeText('1 setuju (100,0%)');
        $this->actingAs($admin)->delete(route('admin.programs.destroy', $program))->assertRedirect(route('admin.programs.index'));
        $this->assertModelMissing($program);
        $this->assertSame(0, ProgramVote::count());
        $this->assertSame(3, ActivityLog::where('action', 'program')->where('user_id', $admin->id)->count());

        $this->actingAs($admin)->post(route('admin.programs.store'), array_replace($data, ['title' => '', 'status' => 'unknown']))->assertSessionHasErrors(['title', 'status']);
    }

    public function test_only_officials_can_manage_plans(): void
    {
        $program = Program::factory()->create();
        $this->get(route('admin.programs.index'))->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create(['is_admin' => false]));
        $this->get(route('admin.programs.index'))->assertForbidden();
        $this->post(route('admin.programs.store'), ['title' => 'Program titipan'])->assertForbidden();
        $this->delete(route('admin.programs.destroy', $program))->assertForbidden();
        $this->assertModelExists($program);
    }
}
