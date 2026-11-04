<?php

namespace Tests\Feature;

use App\Models\BudgetLine;
use App\Models\Dataset;
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

        $response->assertRedirect(route('admin.index', ['year' => 2026]))->assertSessionHasNoErrors();
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

        $response->assertRedirect(route('admin.index', ['year' => 2026]));
        $this->assertModelMissing($line);
        $this->assertDatabaseHas('activity_logs', ['action' => 'delete', 'user_id' => $admin->id]);
    }

    public function test_moving_a_budget_to_another_year_keeps_dataset_provenance_visible(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $line = BudgetLine::factory()->for(Dataset::factory()->create(['year' => 2026, 'is_demo' => true]))->create(['year' => 2026, 'is_demo' => true]);

        $response = $this->actingAs($admin)->patch(route('admin.budgets.update', $line), array_replace($this->validBudget(), ['year' => 2027]));

        $response->assertRedirect(route('admin.index', ['year' => 2027]))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('budget_lines', ['id' => $line->id, 'year' => 2027, 'is_demo' => true]);
        $this->assertSame(2027, $line->fresh()->dataset->year);
        $this->assertTrue($line->fresh()->dataset->is_demo);
        $this->get(route('admin.index', ['year' => 2027]))->assertOk()->assertViewHas('payload', fn (array $payload): bool => count($payload['datasets']) === 1 && $payload['datasets'][0]['row_count'] === 1);
    }

    public function test_invalid_csv_row_rolls_back_the_entire_import(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $csv = "year,type,category,description,amount,source,verification_status\n2026,income,Transfer,Valid first row,1000,Testing,unverified\n2026,expense,Pembangunan,Invalid second row,-10,Testing,unverified\n";
        $file = UploadedFile::fake()->createWithContent('invalid.csv', $csv);

        $response = $this->actingAs($admin)->post(route('admin.import'), ['file' => $file]);

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

        $response = $this->actingAs($admin)->post(route('admin.import'), ['file' => $file]);

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
            $response = $this->actingAs($admin)->post(route('admin.import'), ['file' => $file]);

            $response->assertSessionHasNoErrors()->assertRedirect();
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

        $response = $this->actingAs($admin)->post(route('admin.import'), ['file' => $file, 'year' => 2026]);

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

        $response = $this->actingAs($admin)->post(route('admin.import'), ['file' => $file, 'year' => 2026]);

        $response->assertRedirect(route('admin.index', ['year' => 2026]))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('budget_lines', ['description' => 'Matching period row', 'year' => 2026]);
        $this->assertDatabaseHas('budget_lines', ['description' => 'Blank period row', 'year' => 2026]);
        $this->assertDatabaseCount('budget_lines', 2);
        $this->assertDatabaseHas('datasets', ['year' => 2026]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'import', 'year' => 2026, 'user_id' => $admin->id]);
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
}
