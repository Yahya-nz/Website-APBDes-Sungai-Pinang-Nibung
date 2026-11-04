<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\BudgetLine;
use App\Models\Complaint;
use App\Models\Dataset;
use App\Models\Realization;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    private const SOURCE = 'Baliho APBDes 2026 Desa Sungai Pinang Nibung';

    private const MONTHS = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

    public function run(): void
    {
        DB::transaction(function (): void {
            $admin = User::firstOrCreate(['email' => config('apbdes.admin_email')], ['name' => 'Admin Desa', 'password' => Hash::make(config('apbdes.admin_password') ?: Str::password(20))]);
            if ($admin->wasRecentlyCreated) {
                $admin->forceFill(['is_admin' => true])->save();
            }
            if (BudgetLine::exists()) {
                return;
            }

            $stamp = Carbon::parse('2026-01-12 09:00:00');
            $dataset = Dataset::create(['name' => 'APBDes 2026 dari baliho desa', 'year' => 2026, 'source' => self::SOURCE, 'source_date' => null, 'is_demo' => false, 'user_id' => $admin->id, 'created_at' => $stamp, 'updated_at' => $stamp]);

            $lines = [];
            foreach (BalihoApbdes2026::income() as [$category, $description, $amount]) {
                $lines[] = $this->line($dataset, $stamp, 'income', $category, null, $description, null, $amount, $category);
            }
            foreach (BalihoApbdes2026::expense() as [$category, $subCategory, $description, $volume, $amount, $fundingSource, $plan]) {
                $line = $this->line($dataset, $stamp, 'expense', $category, $subCategory, $description, $volume, $amount, $fundingSource);
                $lines[] = $line;
                $this->seedRealizations($line, $plan, $admin);
            }

            ActivityLog::create(['user_id' => $admin->id, 'year' => 2026, 'action' => 'import', 'description' => 'Memasukkan '.count($lines).' baris APBDes 2026 dari baliho desa. Total pendapatan dan belanja masing-masing Rp549.108.437,83.', 'created_at' => $stamp, 'updated_at' => $stamp]);
            ActivityLog::create(['user_id' => $admin->id, 'year' => 2026, 'action' => 'seed', 'description' => 'Menyiapkan transaksi realisasi contoh Januari–November 2026 untuk mencoba fitur monitoring. Angka realisasi bukan data resmi.', 'created_at' => $stamp->copy()->addMinutes(5), 'updated_at' => $stamp->copy()->addMinutes(5)]);

            $this->seedComplaints($admin);
        });
    }

    private function line(Dataset $dataset, Carbon $stamp, string $type, string $category, ?string $subCategory, string $description, ?string $volume, string $amount, string $fundingSource): BudgetLine
    {
        $line = new BudgetLine([
            'dataset_id' => $dataset->id, 'year' => 2026, 'type' => $type, 'category' => $category, 'sub_category' => $subCategory,
            'description' => $description, 'volume' => $volume, 'amount' => $amount, 'funding_source' => $fundingSource,
            'source' => self::SOURCE, 'source_date' => null,
            // Sumber dana "Insentif RT" belum cocok dengan rekap pendapatan, jadi belum ditandai terverifikasi.
            'verification_status' => $description === 'Insentif RT' ? 'unverified' : 'verified',
            'is_demo' => false,
        ]);
        $line->timestamps = false;
        $line->created_at = $stamp;
        $line->updated_at = $stamp;
        $line->save();

        return $line;
    }

    /**
     * Transaksi realisasi contoh. Pola pembayaran mengikuti volume kegiatan:
     * bulanan dibayar tiap bulan, paket dibayar sekali, pembangunan per termin.
     *
     * @param  array{0: string, 1?: mixed}  $plan
     */
    private function seedRealizations(BudgetLine $line, array $plan, User $admin): void
    {
        $amount = (float) $line->amount;
        $payments = match ($plan[0]) {
            'monthly' => $this->monthlyPayments($amount, $plan[1], $plan[2], $plan[3] ?? 5),
            'once' => [[$plan[1], $amount, $plan[2]]],
            'terms' => array_map(fn (array $term): array => [$term[0], round($amount * $term[1], 2), $term[2]], $plan[1]),
            default => [],
        };

        foreach ($payments as [$date, $value, $note]) {
            $realization = new Realization(['budget_line_id' => $line->id, 'spent_on' => $date, 'amount' => number_format($value, 2, '.', ''), 'description' => $note ?? $line->description, 'is_demo' => true, 'user_id' => $admin->id]);
            $realization->timestamps = false;
            $realization->created_at = Carbon::parse($date.' 10:00:00');
            $realization->updated_at = $realization->created_at;
            $realization->save();
        }
    }

    /**
     * @return list<array{0: string, 1: float, 2: string}>
     */
    private function monthlyPayments(float $amount, int $totalMonths, array $paidMonths, int $day): array
    {
        $perMonth = floor($amount / $totalMonths * 100) / 100;

        return array_map(fn (int $month): array => [
            sprintf('2026-%02d-%02d', $month, $day),
            $perMonth,
            'Pembayaran bulan '.self::MONTHS[$month],
        ], $paidMonths);
    }

    private function seedComplaints(User $admin): void
    {
        $sumurBor = BudgetLine::where('description', 'Pembuatan sumur bor')->value('id');
        $posyandu = BudgetLine::where('description', 'like', 'Penyelenggaraan Posyandu%')->value('id');
        $complaints = [
            ['ticket' => 'ADU-2609-K7QM', 'name' => 'Siti Aminah', 'topic' => 'question', 'budget_line_id' => $sumurBor, 'message' => 'Sumur bor 12 unit dibangun di dusun mana saja? Di dusun kami belum ada.', 'status' => 'resolved', 'response' => 'Sumur bor dibangun bertahap. Tahap pertama 6 unit sudah selesai di Dusun I dan II, tahap kedua dijadwalkan November untuk Dusun III dan IV.', 'responded_at' => '2026-09-18 14:20:00', 'created_at' => '2026-09-16 08:15:00'],
            ['ticket' => 'ADU-2610-P3WD', 'name' => null, 'topic' => 'suggestion', 'budget_line_id' => $posyandu, 'message' => 'Mohon jadwal posyandu diumumkan juga lewat grup WhatsApp RT supaya ibu-ibu tidak ketinggalan.', 'status' => 'in_progress', 'response' => 'Terima kasih, sedang kami koordinasikan dengan kader posyandu.', 'responded_at' => '2026-10-22 10:05:00', 'created_at' => '2026-10-21 19:40:00'],
            ['ticket' => 'ADU-2611-H8NT', 'name' => 'Rahmat', 'topic' => 'complaint', 'budget_line_id' => null, 'message' => 'Lampu jalan di depan balai desa mati sudah dua minggu. Apakah ada anggaran perbaikannya tahun ini?', 'status' => 'new', 'response' => null, 'responded_at' => null, 'created_at' => '2026-11-03 07:30:00'],
        ];
        foreach ($complaints as $data) {
            $complaint = new Complaint($data + ['is_demo' => true, 'responded_by' => $data['responded_at'] ? $admin->id : null]);
            $complaint->updated_at = $data['responded_at'] ?? $data['created_at'];
            $complaint->save();
        }
    }
}
