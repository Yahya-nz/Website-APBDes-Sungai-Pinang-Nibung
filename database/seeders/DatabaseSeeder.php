<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\BudgetLine;
use App\Models\Complaint;
use App\Models\Dataset;
use App\Models\Program;
use App\Models\ProgramVote;
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
            ActivityLog::create(['user_id' => $admin->id, 'year' => 2026, 'action' => 'seed', 'description' => 'Mencatat transaksi realisasi Januari–November 2026 untuk monitoring anggaran.', 'created_at' => $stamp->copy()->addMinutes(5), 'updated_at' => $stamp->copy()->addMinutes(5)]);

            $this->seedComplaints($admin);
            $this->seedPrograms($admin);
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
            ['ticket' => 'ADU-2609-K7QM', 'name' => 'Siti Aminah', 'contact' => '0812-0000-1111', 'recovery_pin' => '1234', 'topic' => 'question', 'budget_line_id' => $sumurBor, 'message' => 'Sumur bor 12 unit dibangun di dusun mana saja? Di dusun kami belum ada.', 'status' => 'resolved', 'response' => 'Sumur bor dibangun bertahap. Tahap pertama 6 unit sudah selesai di Dusun I dan II, tahap kedua dijadwalkan November untuk Dusun III dan IV.', 'responded_at' => '2026-09-18 14:20:00', 'created_at' => '2026-09-16 08:15:00'],
            ['ticket' => 'ADU-2610-P3WD', 'name' => null, 'topic' => 'suggestion', 'budget_line_id' => $posyandu, 'message' => 'Mohon jadwal posyandu diumumkan juga lewat grup WhatsApp RT supaya ibu-ibu tidak ketinggalan.', 'status' => 'in_progress', 'response' => 'Terima kasih, sedang kami koordinasikan dengan kader posyandu.', 'responded_at' => '2026-10-22 10:05:00', 'created_at' => '2026-10-21 19:40:00'],
            ['ticket' => 'ADU-2611-H8NT', 'name' => 'Rahmat', 'topic' => 'complaint', 'budget_line_id' => null, 'message' => 'Lampu jalan di depan balai desa mati sudah dua minggu. Apakah ada anggaran perbaikannya tahun ini?', 'status' => 'new', 'response' => null, 'responded_at' => null, 'created_at' => '2026-11-03 07:30:00'],
            ['ticket' => 'ADU-2608-B4RX', 'name' => 'Darmawan', 'topic' => 'question', 'budget_line_id' => null, 'message' => 'Berapa anggaran untuk insentif guru ngaji tahun ini dan kapan dibayarkan?', 'status' => 'resolved', 'response' => 'Insentif guru ngaji Rp12.000.000 untuk 12 bulan, dibayarkan setiap awal bulan.', 'responded_at' => '2026-08-12 09:00:00', 'created_at' => '2026-08-10 16:20:00'],
            ['ticket' => 'ADU-2608-M2FZ', 'name' => 'Nurhayati', 'topic' => 'suggestion', 'budget_line_id' => null, 'message' => 'Usul agar laporan APBDes juga ditempel di papan pengumuman masjid supaya warga lansia bisa membaca.', 'status' => 'resolved', 'response' => 'Usulan diterima. Ringkasan APBDes akan ditempel di papan pengumuman masjid setiap semester.', 'responded_at' => '2026-08-25 11:15:00', 'created_at' => '2026-08-23 08:40:00'],
            ['ticket' => 'ADU-2609-C9TL', 'name' => 'Hendra Saputra', 'topic' => 'complaint', 'budget_line_id' => null, 'message' => 'Saluran air di Dusun I tersumbat dan meluap kalau hujan deras. Mohon dicek.', 'status' => 'resolved', 'response' => 'Sudah dibersihkan bersama warga melalui gotong royong pada 14 September.', 'responded_at' => '2026-09-15 15:30:00', 'created_at' => '2026-09-06 18:05:00'],
            ['ticket' => 'ADU-2609-W5KD', 'name' => 'Sari Wulandari', 'topic' => 'question', 'budget_line_id' => null, 'message' => 'Apakah bantuan langsung tunai tahun ini masih untuk penerima yang sama seperti tahun lalu?', 'status' => 'resolved', 'response' => 'Penerima BLT ditetapkan lewat musyawarah desa; daftarnya ditempel di kantor desa.', 'responded_at' => '2026-09-24 10:10:00', 'created_at' => '2026-09-22 07:55:00'],
            ['ticket' => 'ADU-2610-F7HN', 'name' => 'Agus Salim', 'topic' => 'suggestion', 'budget_line_id' => null, 'message' => 'Sebaiknya ada pelatihan komputer untuk karang taruna agar bisa membantu administrasi desa.', 'status' => 'in_progress', 'response' => 'Sedang kami bahas untuk rencana program tahun depan.', 'responded_at' => '2026-10-05 13:00:00', 'created_at' => '2026-10-03 20:15:00'],
            ['ticket' => 'ADU-2610-R3PQ', 'name' => 'Yuliana', 'topic' => 'complaint', 'budget_line_id' => null, 'message' => 'Jalan menuju kebun di Dusun III berlubang dan sulit dilalui motor saat musim hujan.', 'status' => 'in_progress', 'response' => 'Sudah kami catat dan diusulkan dalam rencana program pembangunan jalan 2027.', 'responded_at' => '2026-10-14 09:45:00', 'created_at' => '2026-10-12 06:30:00'],
            ['ticket' => 'ADU-2610-T8GV', 'name' => 'Bambang', 'topic' => 'question', 'budget_line_id' => null, 'message' => 'Kapan musyawarah desa untuk membahas rencana kegiatan tahun 2027 dilaksanakan?', 'status' => 'resolved', 'response' => 'Musyawarah desa dijadwalkan minggu kedua Desember di balai desa.', 'responded_at' => '2026-10-27 08:30:00', 'created_at' => '2026-10-26 17:00:00'],
            ['ticket' => 'ADU-2611-A6JS', 'name' => null, 'topic' => 'suggestion', 'budget_line_id' => null, 'message' => 'Mohon ada tempat sampah di sekitar pasar desa karena sampah sering menumpuk di pinggir jalan.', 'status' => 'new', 'response' => null, 'responded_at' => null, 'created_at' => '2026-11-01 10:20:00'],
            ['ticket' => 'ADU-2611-E2LM', 'name' => 'Fitriani', 'topic' => 'question', 'budget_line_id' => null, 'message' => 'Apakah ada program pelatihan usaha untuk ibu-ibu PKK tahun depan?', 'status' => 'new', 'response' => null, 'responded_at' => null, 'created_at' => '2026-11-02 14:35:00'],
            ['ticket' => 'ADU-2611-K4UB', 'name' => 'Joko Prasetyo', 'topic' => 'complaint', 'budget_line_id' => null, 'message' => 'Pembagian air bersih dari sumur bor di Dusun II belum merata, sebagian rumah belum kebagian.', 'status' => 'new', 'response' => null, 'responded_at' => null, 'created_at' => '2026-11-04 08:10:00'],
            ['ticket' => 'ADU-2611-Q9YC', 'name' => 'Rini Marlina', 'topic' => 'suggestion', 'budget_line_id' => null, 'message' => 'Usul agar posyandu lansia diadakan dua kali sebulan karena pesertanya banyak.', 'status' => 'new', 'response' => null, 'responded_at' => null, 'created_at' => '2026-11-04 19:25:00'],
        ];
        foreach ($complaints as $data) {
            $complaint = new Complaint($data + ['is_demo' => true, 'contact_key' => Complaint::contactKey($data['contact'] ?? null), 'responded_by' => $data['responded_at'] ? $admin->id : null]);
            $complaint->updated_at = $data['responded_at'] ?? $data['created_at'];
            $complaint->save();
        }
    }

    /**
     * Rencana program 2027 beserta suara warga, supaya fitur voting langsung bisa dilihat.
     */
    private function seedPrograms(User $admin): void
    {
        $programs = [
            ['Pembangunan jalan rabat beton Dusun III', 'Membangun jalan rabat beton sepanjang 300 meter menuju kebun warga Dusun III agar bisa dilalui kendaraan saat musim hujan.', 'Pelaksanaan Pembangunan Desa', 'Dusun III', '180000000', 'proposed', true, '2026-12-31', 46, 9],
            ['Lampu penerangan jalan tenaga surya', 'Memasang 20 titik lampu jalan tenaga surya di jalan poros desa supaya jalan aman pada malam hari tanpa menambah biaya listrik.', 'Pelaksanaan Pembangunan Desa', 'Jalan poros desa', '75000000', 'proposed', true, '2026-12-31', 38, 14],
            ['Pelatihan usaha olahan ikan untuk PKK', 'Pelatihan membuat kerupuk dan pempek ikan untuk kelompok PKK, termasuk pengemasan dan pemasaran daring.', 'Pemberdayaan Masyarakat Desa', 'Balai desa', '20000000', 'planned', true, null, 27, 6],
            ['Posyandu remaja dan cek kesehatan gratis', 'Kegiatan posyandu remaja setiap bulan dengan pemeriksaan tekanan darah, gula darah, dan penyuluhan gizi.', 'Pelaksanaan Pembangunan Desa', 'Polindes', '15000000', 'planned', true, null, 31, 3],
            ['Normalisasi saluran air Dusun I', 'Mengeruk dan memperdalam saluran air di Dusun I agar tidak meluap saat hujan deras.', 'Penanggulangan Bencana', 'Dusun I', '60000000', 'proposed', true, null, 12, 11],
            ['Renovasi balai desa', 'Memperbaiki atap dan lantai balai desa yang rusak serta menambah ruang pelayanan warga.', 'Penyelenggaraan Pemerintahan Desa', 'Kantor desa', '120000000', 'proposed', false, '2026-10-31', 15, 22],
        ];
        foreach ($programs as $index => [$title, $description, $category, $location, $budget, $status, $open, $endsAt, $agree, $disagree]) {
            $stamp = Carbon::parse('2026-10-01 09:00:00')->addDays($index * 3);
            $program = new Program(['title' => $title, 'description' => $description, 'category' => $category, 'location' => $location, 'year' => 2027, 'estimated_budget' => $budget, 'status' => $status, 'voting_open' => $open, 'voting_ends_at' => $endsAt, 'user_id' => $admin->id]);
            $program->created_at = $stamp;
            $program->updated_at = $stamp;
            $program->save();

            $votes = [];
            foreach (['agree' => $agree, 'disagree' => $disagree] as $choice => $count) {
                for ($i = 0; $i < $count; $i++) {
                    $votes[] = ['program_id' => $program->id, 'choice' => $choice, 'voter_key' => hash('sha256', "warga-{$program->id}-{$choice}-{$i}"), 'created_at' => $stamp, 'updated_at' => $stamp];
                }
            }
            ProgramVote::insert($votes);
        }
        ActivityLog::create(['user_id' => $admin->id, 'year' => 2027, 'action' => 'program', 'description' => 'Menambahkan '.count($programs).' rencana program 2027 untuk dinilai warga', 'created_at' => '2026-10-01 09:00:00', 'updated_at' => '2026-10-01 09:00:00']);
    }
}
