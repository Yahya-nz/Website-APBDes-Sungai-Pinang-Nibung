<?php

namespace Tests\Feature;

use App\Models\BudgetLine;
use App\Models\Dataset;
use App\Models\Realization;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class AdminControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_guests_are_redirected_and_readers_cannot_manage_data(): void
    {
        $this->get(route('admin.index'))->assertRedirect(route('login'));
        $reader = User::factory()->create(['is_admin' => false]);

        $this->actingAs($reader)->post(route('admin.budgets.store'), $this->validBudget())->assertForbidden();

        $this->assertDatabaseCount('budget_lines', 0);
        $this->assertDatabaseCount('activity_logs', 0);
    }

    public function test_manage_data_gate_accepts_only_administrators(): void
    {
        $reader = User::factory()->create(['is_admin' => false]);
        $admin = User::factory()->create(['is_admin' => true]);

        $this->assertFalse(Gate::forUser($reader)->allows('manage-data'));
        $this->assertTrue(Gate::forUser($admin)->allows('manage-data'));
    }

    public function test_valid_budget_is_saved_with_an_audit_entry_and_rejects_extra_control_fields(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->post(route('admin.budgets.store'), $this->validBudget() + ['is_demo' => true, 'dataset_id' => 9999]);

        $response->assertRedirect(route('admin.budgets.index', ['year' => 2026]))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('budget_lines', ['description' => 'Pembangunan jalan pengujian', 'amount' => '12345.67', 'is_demo' => false]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'create', 'user_id' => $admin->id, 'year' => 2026]);
        $this->assertDatabaseCount('datasets', 1);
    }

    public function test_negative_budget_is_rejected_without_saving_any_changes(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->post(route('admin.budgets.store'), array_replace($this->validBudget(), ['amount' => '-1.00']));

        $response->assertSessionHasErrors('amount');
        $this->assertDatabaseCount('budget_lines', 0);
        $this->assertDatabaseCount('datasets', 0);
        $this->assertDatabaseCount('activity_logs', 0);
    }

    public function test_update_preserves_demo_provenance_and_records_the_change(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $line = BudgetLine::factory()->create(['is_demo' => true]);

        $response = $this->actingAs($admin)->patch(route('admin.budgets.update', $line), $this->validBudget() + ['is_demo' => false]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('budget_lines', ['id' => $line->id, 'amount' => '12345.67', 'is_demo' => true]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'update', 'user_id' => $admin->id]);
    }

    public function test_delete_removes_the_budget_and_retains_audit_history(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $line = BudgetLine::factory()->create();

        $response = $this->actingAs($admin)->delete(route('admin.budgets.destroy', $line));

        $response->assertRedirect(route('admin.budgets.index', ['year' => 2026]));
        $this->assertModelMissing($line);
        $this->assertDatabaseHas('activity_logs', ['action' => 'delete', 'user_id' => $admin->id]);
    }

    public function test_moving_a_budget_to_another_year_keeps_dataset_provenance_visible(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $line = BudgetLine::factory()->for(Dataset::factory()->create(['year' => 2026, 'is_demo' => true]))->create(['year' => 2026, 'is_demo' => true]);

        $response = $this->actingAs($admin)->patch(route('admin.budgets.update', $line), array_replace($this->validBudget(), ['year' => 2027]));

        $response->assertRedirect(route('admin.budgets.index', ['year' => 2027]))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('budget_lines', ['id' => $line->id, 'year' => 2027, 'is_demo' => true]);
        $this->assertSame(2027, $line->fresh()->dataset->year);
        $this->assertTrue($line->fresh()->dataset->is_demo);
        $this->get(route('admin.budgets.index', ['year' => 2027]))->assertOk()->assertViewHas('payload', fn (array $payload): bool => count($payload['datasets']) === 1 && $payload['datasets'][0]['row_count'] === 1);
    }

    public function test_invalid_csv_row_rolls_back_the_entire_import(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $csv = "year,type,category,description,amount,source,verification_status\n2026,income,Transfer,Valid first row,1000,Testing,unverified\n2026,expense,Pembangunan,Invalid second row,-10,Testing,unverified\n";
        $file = UploadedFile::fake()->createWithContent('invalid.csv', $csv);

        $response = $this->actingAs($admin)->post(route('admin.import'), ['file' => $file] + $this->importDetails());

        $response->assertSessionHasErrors('file');
        $this->assertDatabaseCount('budget_lines', 0);
        $this->assertDatabaseCount('datasets', 0);
        $this->assertDatabaseCount('activity_logs', 0);
    }

    public function test_duplicate_rows_in_csv_are_rejected_before_totals_can_be_doubled(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $csv = "year,type,category,description,amount,source\n2026,income,Transfer,Same record,1000,Testing\n2026,income,Transfer,Same record,1000,Testing\n";
        $file = UploadedFile::fake()->createWithContent('duplicate.csv', $csv);

        $response = $this->actingAs($admin)->post(route('admin.import'), ['file' => $file] + $this->importDetails());

        $response->assertSessionHasErrors('file');
        $this->assertDatabaseCount('budget_lines', 0);
        $this->assertDatabaseCount('datasets', 0);
    }

    public function test_valid_xlsx_import_preserves_decimal_amount_and_activity_details(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $book = new Spreadsheet;
        $book->getActiveSheet()->fromArray([
            ['year', 'type', 'category', 'sub_category', 'description', 'volume', 'amount', 'funding_source', 'source', 'verification_status', 'source_date'],
            [2026, 'expense', 'Pembangunan', 'Kesehatan', 'Imported Excel row', '12 bulan', 12500.75, 'Dana Desa', 'Workbook pengujian', 'verified', '2026-11-03'],
        ], null, 'A1', true);
        $path = tempnam(sys_get_temp_dir(), 'apbdes-test-');
        (new Xlsx($book))->save($path);
        $file = new UploadedFile($path, 'valid.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        try {
            $response = $this->actingAs($admin)->post(route('admin.import'), ['file' => $file] + $this->importDetails());

            $response->assertSessionHasNoErrors()->assertRedirect(route('admin.budgets.index', ['year' => 2026]));
            $this->assertDatabaseHas('budget_lines', ['description' => 'Imported Excel row', 'amount' => '12500.75', 'sub_category' => 'Kesehatan', 'volume' => '12 bulan', 'funding_source' => 'Dana Desa', 'is_demo' => false]);
            $this->assertDatabaseHas('activity_logs', ['action' => 'import', 'user_id' => $admin->id]);
            $this->assertDatabaseCount('datasets', 1);
        } finally {
            $book->disconnectWorksheets();
            @unlink($path);
        }
    }

    public function test_row_year_different_from_selected_import_year_rejects_the_entire_file(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $csv = "year,type,category,description,amount,source\n2026,income,Transfer,Matching first row,1000,Testing\n2025,expense,Pembangunan,Wrong period second row,500,Testing\n";
        $file = UploadedFile::fake()->createWithContent('wrong-period.csv', $csv);

        $response = $this->actingAs($admin)->post(route('admin.import'), ['file' => $file] + $this->importDetails());

        $response->assertSessionHasErrors(['file' => 'Baris 3: tahun anggaran 2025 tidak sesuai dengan tahun yang dipilih pada formulir (2026). Samakan tahun pada berkas atau ubah pilihan tahun sebelum impor.']);
        $this->assertDatabaseCount('budget_lines', 0);
        $this->assertDatabaseCount('datasets', 0);
        $this->assertDatabaseCount('activity_logs', 0);
    }

    public function test_matching_and_blank_row_years_use_the_selected_import_period(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $csv = "year,type,category,description,amount,source\n2026,income,Transfer,Matching period row,1000,Testing\n,expense,Pembangunan,Blank period row,500,Testing\n";
        $file = UploadedFile::fake()->createWithContent('matching-period.csv', $csv);

        $response = $this->actingAs($admin)->post(route('admin.import'), ['file' => $file] + $this->importDetails());

        $response->assertRedirect(route('admin.budgets.index', ['year' => 2026]))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('budget_lines', ['description' => 'Matching period row', 'year' => 2026]);
        $this->assertDatabaseHas('budget_lines', ['description' => 'Blank period row', 'year' => 2026]);
        $this->assertDatabaseCount('budget_lines', 2);
        $this->assertDatabaseHas('datasets', ['year' => 2026]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'import', 'year' => 2026, 'user_id' => $admin->id]);
        $dataset = Dataset::sole();
        $this->assertSame('Dokumen pengujian', $dataset->source);
        $this->assertSame('2026-01-12', $dataset->source_date->toDateString());
    }

    public function test_import_requires_the_period_source_and_update_date(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $file = UploadedFile::fake()->createWithContent('valid.csv', "year,type,category,description,amount\n2026,income,Transfer,Dana pengujian,1000\n");

        $response = $this->actingAs($admin)->post(route('admin.import'), ['file' => $file, 'source_date' => now()->addDay()->toDateString()]);

        $response->assertSessionHasErrors([
            'year' => 'Periode anggaran wajib diisi.',
            'source' => 'Nama / jenis sumber wajib diisi.',
            'source_date' => 'Tanggal pembaruan harus berupa tanggal sebelum atau sama dengan hari ini.',
        ]);
        $this->assertDatabaseCount('budget_lines', 0);
        $this->assertDatabaseCount('datasets', 0);
    }

    public function test_manual_input_rejects_an_existing_activity_and_groups_entries_by_source(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin)->post(route('admin.budgets.store'), $this->validBudget())->assertSessionHasNoErrors();
        $this->actingAs($admin)->post(route('admin.budgets.store'), array_replace($this->validBudget(), ['description' => 'Rehabilitasi saluran pengujian']))->assertSessionHasNoErrors();

        $duplicate = $this->actingAs($admin)->post(route('admin.budgets.store'), $this->validBudget());

        $duplicate->assertSessionHasErrors(['description' => 'Uraian ini sudah ada pada bidang dan tahun yang sama. Ubah data yang sudah ada agar totalnya tidak terhitung dua kali.']);
        $this->assertDatabaseCount('budget_lines', 2);
        $this->assertDatabaseCount('datasets', 1);
        $this->assertSame(2, Dataset::sole()->budgetLines()->count());
        $line = BudgetLine::where('description', 'Pembangunan jalan pengujian')->sole();
        $this->actingAs($admin)->patch(route('admin.budgets.update', $line), array_replace($this->validBudget(), ['amount' => '500.00']))->assertSessionHasNoErrors();
        $this->assertSame('500.00', $line->fresh()->amount);
    }

    public function test_officials_can_set_the_verification_status_of_several_rows_at_once(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $selected = BudgetLine::factory()->count(3)->create(['verification_status' => 'unverified']);
        $untouched = BudgetLine::factory()->create(['verification_status' => 'unverified']);
        $page = route('admin.budgets.index', ['year' => 2026, 'status' => 'unverified']);

        $response = $this->actingAs($admin)->from($page)->patch(route('admin.budgets.verify'), ['ids' => $selected->pluck('id')->all(), 'verification_status' => 'verified']);

        $response->assertRedirect($page)->assertSessionHas('success', 'Status 3 baris anggaran diubah menjadi Terverifikasi.');
        $this->assertSame(3, BudgetLine::where('verification_status', 'verified')->count());
        $this->assertSame('unverified', $untouched->fresh()->verification_status);
        $this->assertDatabaseHas('activity_logs', ['action' => 'verify', 'user_id' => $admin->id, 'year' => 2026, 'description' => 'Menetapkan status Terverifikasi untuk 3 baris anggaran']);
    }

    public function test_bulk_verification_needs_a_selection_of_existing_rows_and_a_known_status(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $line = BudgetLine::factory()->create(['verification_status' => 'unverified']);

        $this->actingAs($admin)->patch(route('admin.budgets.verify'), ['verification_status' => 'verified'])->assertSessionHasErrors(['ids' => 'Pilih minimal satu baris anggaran pada tabel.']);
        $this->actingAs($admin)->patch(route('admin.budgets.verify'), ['ids' => [$line->id], 'verification_status' => 'approved'])->assertSessionHasErrors('verification_status');
        $this->actingAs($admin)->patch(route('admin.budgets.verify'), ['ids' => [999999], 'verification_status' => 'verified'])->assertSessionHasErrors('ids.0');

        $this->assertSame('unverified', $line->fresh()->verification_status);
        $this->assertDatabaseCount('activity_logs', 0);
    }

    public function test_dataset_source_information_can_be_recorded_for_all_of_its_rows(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $dataset = Dataset::factory()->create(['source' => 'Catatan lama', 'source_date' => null]);
        $lines = BudgetLine::factory()->count(2)->for($dataset)->create(['source' => 'Catatan lama', 'source_date' => null]);

        $response = $this->actingAs($admin)->patch(route('admin.datasets.update', $dataset), ['name' => 'APBDes 2026 dari baliho desa', 'source' => 'Baliho APBDes 2026', 'source_date' => '2026-01-12', 'apply_to_lines' => '1']);

        $response->assertRedirect(route('admin.budgets.index', ['year' => 2026]))->assertSessionHasNoErrors();
        $this->assertSame('Baliho APBDes 2026', $dataset->fresh()->source);
        $this->assertSame('2026-01-12', $dataset->fresh()->source_date->toDateString());
        foreach ($lines as $line) {
            $this->assertSame('Baliho APBDes 2026', $line->fresh()->source);
            $this->assertSame('2026-01-12', $line->fresh()->source_date->toDateString());
        }
        $this->assertDatabaseHas('activity_logs', ['action' => 'dataset', 'user_id' => $admin->id, 'year' => 2026]);
    }

    public function test_deleting_a_dataset_cancels_its_import_including_spending_records(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $dataset = Dataset::factory()->create();
        $line = BudgetLine::factory()->for($dataset)->create(['type' => 'expense']);
        $realization = Realization::factory()->create(['budget_line_id' => $line->id]);
        $otherLine = BudgetLine::factory()->create();

        $response = $this->actingAs($admin)->delete(route('admin.datasets.destroy', $dataset));

        $response->assertRedirect(route('admin.budgets.index', ['year' => 2026]));
        $this->assertModelMissing($dataset);
        $this->assertModelMissing($line);
        $this->assertModelMissing($realization);
        $this->assertModelExists($otherLine);
        $this->assertDatabaseHas('activity_logs', ['action' => 'delete', 'user_id' => $admin->id, 'year' => 2026]);
    }

    public function test_management_page_follows_the_proposal_sections_and_flags_the_baliho_mismatch(): void
    {
        $this->seed();
        $admin = User::where('is_admin', true)->firstOrFail();
        $dataset = Dataset::sole();

        $response = $this->actingAs($admin)->get(route('admin.budgets.index', ['year' => 2026]));

        $response->assertOk()
            ->assertSeeTextInOrder(['1. Impor Data APBDes', '2. Informasi Sumber Data', '3. Pemeriksaan dan Status Verifikasi', '4. Riwayat Dataset', 'Data anggaran'])
            ->assertSeeText('APBDes 2026 dari baliho desa')
            ->assertSeeText('Belanja per sumber dana belum sesuai pendapatannya')
            ->assertSeeText('Dana Desa +Rp9.600.000,00, ADD -Rp9.600.000,00');
        $filtered = $this->actingAs($admin)->get(route('admin.budgets.index', ['year' => 2026, 'dataset' => $dataset->id + 1]));
        $this->assertSame([], $filtered->viewData('payload')['rows']);
    }

    /** @return array<string, int|string> */
    private function validBudget(): array
    {
        return [
            'year' => 2026,
            'type' => 'expense',
            'category' => 'Pembangunan',
            'description' => 'Pembangunan jalan pengujian',
            'amount' => '12345.67',
            'source' => 'Dokumen pengujian',
            'source_date' => '2026-11-03',
            'verification_status' => 'unverified',
        ];
    }

    /** @return array{year: int, source: string, source_date: string} */
    private function importDetails(): array
    {
        return ['year' => 2026, 'source' => 'Dokumen pengujian', 'source_date' => '2026-01-12'];
    }
}
