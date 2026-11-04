<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\BudgetLine;
use App\Models\Dataset;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
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
            $stamp = '2026-11-03 14:00:00';
            $source = 'Dataset demonstrasi — bukan data APBDes resmi';
            $dataset = Dataset::create(['name' => 'APBDes 2026 — Dataset demonstrasi', 'year' => 2026, 'source' => $source, 'source_date' => '2026-11-03', 'is_demo' => true, 'user_id' => $admin->id, 'created_at' => $stamp, 'updated_at' => $stamp]);
            $rows = [
                ['income', 'Dana Desa', 'Dana Desa (DD)', 1100000000],
                ['income', 'Alokasi Dana Desa', 'Alokasi Dana Desa (ADD)', 500000000],
                ['income', 'Bagi Hasil Pajak', 'Bagi hasil pajak dan retribusi daerah', 100000000],
                ['income', 'Pendapatan Asli Desa', 'Hasil usaha dan aset desa', 75000000],
                ['income', 'Pendapatan Lain-lain', 'Pendapatan bunga bank', 25000000],
                ['expense', 'Pembangunan Desa', 'Peningkatan jalan lingkungan', 420000000],
                ['expense', 'Pembangunan Desa', 'Penyediaan sarana air bersih', 300000000],
                ['expense', 'Penyelenggaraan Pemerintahan', 'Penghasilan tetap dan tunjangan perangkat', 330000000],
                ['expense', 'Penyelenggaraan Pemerintahan', 'Operasional pelayanan pemerintahan', 210000000],
                ['expense', 'Pemberdayaan Masyarakat', 'Pengembangan usaha mikro desa', 150000000],
                ['expense', 'Pemberdayaan Masyarakat', 'Pelatihan dan sarana pertanian', 120000000],
                ['expense', 'Pembinaan Kemasyarakatan', 'Kegiatan kepemudaan dan olahraga', 110000000],
                ['expense', 'Pembinaan Kemasyarakatan', 'Pembinaan posyandu dan kelembagaan', 70000000],
                ['expense', 'Penanggulangan Bencana', 'Kesiapsiagaan dan penanggulangan bencana', 60000000],
                ['expense', 'Penanggulangan Bencana', 'Penanganan keadaan mendesak', 30000000],
            ];
            foreach ($rows as [$type,$category,$description,$amount]) {
                $line = new BudgetLine(['dataset_id' => $dataset->id, 'year' => 2026, 'type' => $type, 'category' => $category, 'description' => $description, 'amount' => $amount, 'realization' => null, 'source' => $source, 'source_date' => '2026-11-03', 'verification_status' => 'unverified', 'is_demo' => true]);
                $line->timestamps = false;
                $line->created_at = $stamp;
                $line->updated_at = $stamp;
                $line->save();
            }
            $log = new ActivityLog(['user_id' => $admin->id, 'year' => 2026, 'action' => 'seed', 'description' => 'Menyiapkan 15 baris dataset demonstrasi. Seluruh angka adalah contoh, bukan anggaran resmi.']);
            $log->timestamps = false;
            $log->created_at = $stamp;
            $log->updated_at = $stamp;
            $log->save();
        });
    }
}
