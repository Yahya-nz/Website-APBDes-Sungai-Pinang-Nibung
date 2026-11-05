<?php

namespace App\Services;

use App\Models\BudgetLine;
use App\Support\Money;
use Illuminate\Support\Collection;

/**
 * Pemeriksaan otomatis kelengkapan dan konsistensi data anggaran satu tahun,
 * mengikuti langkah "Pemeriksaan dan Status Verifikasi" pada rancangan halaman pengelola.
 */
class DataQualityChecks
{
    /**
     * @param  array{year: int, summary: array<string, mixed>, checks: list<array{source: string, income: float, expense: float, difference: float}>}  $payload  Hasil BudgetDashboard::payload().
     * @return list<array{key: string, label: string, problem: string, detail: string, passed: bool}>
     */
    public function run(array $payload): array
    {
        $lines = BudgetLine::query()->where('year', $payload['year'])->get(['id', 'type', 'category', 'description', 'funding_source', 'source_date']);
        if ($lines->isEmpty()) {
            return [];
        }

        $summary = $payload['summary'];

        return [
            $this->balance((float) $summary['income'], (float) $summary['expense']),
            $this->fundingSources(collect($payload['checks'])),
            $this->missingFundingSource($lines),
            $this->missingSourceDate($lines),
            $this->duplicates($lines),
            $this->verification((int) $summary['total_count'], (int) $summary['unverified_count'], (int) $summary['unavailable_count']),
        ];
    }

    /**
     * @return array{key: string, label: string, problem: string, detail: string, passed: bool}
     */
    private function balance(float $income, float $expense): array
    {
        $difference = Money::sum([$income, -$expense]);
        $passed = abs($difference) < 0.01;

        return [
            'key' => 'balance',
            'label' => 'Pendapatan dan belanja seimbang',
            'problem' => 'Pendapatan dan belanja belum seimbang',
            'detail' => $passed
                ? 'Pendapatan dan belanja sama-sama '.Money::format($income).'.'
                : 'Pendapatan '.Money::format($income).' dan belanja '.Money::format($expense).' berselisih '.Money::format(abs($difference)).'. Pastikan selisihnya dijelaskan oleh pembiayaan.',
            'passed' => $passed,
        ];
    }

    /**
     * @param  Collection<int, array{source: string, income: float, expense: float, difference: float}>  $checks
     * @return array{key: string, label: string, problem: string, detail: string, passed: bool}
     */
    private function fundingSources(Collection $checks): array
    {
        $mismatches = $checks->filter(fn (array $check): bool => abs($check['difference']) >= 0.01);

        return [
            'key' => 'funding',
            'label' => 'Belanja per sumber dana sesuai pendapatannya',
            'problem' => 'Belanja per sumber dana belum sesuai pendapatannya',
            'detail' => $mismatches->isEmpty()
                ? 'Belanja setiap sumber dana sama dengan pendapatan dari sumber yang sama.'
                : 'Selisih belanja terhadap pendapatan: '.$mismatches->map(fn (array $check): string => $check['source'].' '.($check['difference'] > 0 ? '+' : '').Money::format($check['difference']))->join(', ').'.',
            'passed' => $mismatches->isEmpty(),
        ];
    }

    /**
     * @param  Collection<int, BudgetLine>  $lines
     * @return array{key: string, label: string, problem: string, detail: string, passed: bool}
     */
    private function missingFundingSource(Collection $lines): array
    {
        $missing = $lines->where('type', 'expense')->filter(fn (BudgetLine $line): bool => blank($line->funding_source))->count();

        return [
            'key' => 'funding_source',
            'label' => 'Setiap kegiatan belanja punya sumber dana',
            'problem' => 'Ada kegiatan belanja tanpa sumber dana',
            'detail' => $missing === 0 ? 'Semua kegiatan belanja mencantumkan sumber dananya.' : "{$missing} kegiatan belanja belum mencantumkan sumber dana.",
            'passed' => $missing === 0,
        ];
    }

    /**
     * @param  Collection<int, BudgetLine>  $lines
     * @return array{key: string, label: string, problem: string, detail: string, passed: bool}
     */
    private function missingSourceDate(Collection $lines): array
    {
        $missing = $lines->whereNull('source_date')->count();

        return [
            'key' => 'source_date',
            'label' => 'Tanggal pembaruan sumber sudah dicatat',
            'problem' => 'Tanggal pembaruan sumber belum dicatat',
            'detail' => $missing === 0
                ? 'Semua baris mencatat tanggal dokumen sumbernya.'
                : "{$missing} dari {$lines->count()} baris belum mencatat tanggal dokumen sumber. Lengkapi lewat tombol Ubah pada Riwayat Dataset.",
            'passed' => $missing === 0,
        ];
    }

    /**
     * @param  Collection<int, BudgetLine>  $lines
     * @return array{key: string, label: string, problem: string, detail: string, passed: bool}
     */
    private function duplicates(Collection $lines): array
    {
        $duplicates = $lines->groupBy(fn (BudgetLine $line): string => mb_strtolower($line->type.'|'.$line->category.'|'.trim($line->description)))
            ->filter(fn (Collection $group): bool => $group->count() > 1)
            ->count();

        return [
            'key' => 'duplicates',
            'label' => 'Tidak ada uraian ganda',
            'problem' => 'Ada uraian yang tercatat ganda',
            'detail' => $duplicates === 0 ? 'Tidak ada kegiatan dengan uraian sama pada bidang yang sama.' : "{$duplicates} uraian tercatat lebih dari sekali pada bidang yang sama, sehingga totalnya bisa terhitung ganda.",
            'passed' => $duplicates === 0,
        ];
    }

    /**
     * @return array{key: string, label: string, problem: string, detail: string, passed: bool}
     */
    private function verification(int $total, int $unverified, int $unavailable): array
    {
        $passed = $unverified + $unavailable === 0;

        return [
            'key' => 'verification',
            'label' => 'Semua baris sudah diverifikasi',
            'problem' => 'Masih ada baris yang belum diverifikasi',
            'detail' => $passed ? "Semua {$total} baris sudah dicocokkan dengan dokumen sumber." : "{$unverified} baris belum diverifikasi dan {$unavailable} baris belum tersedia datanya.",
            'passed' => $passed,
        ];
    }
}
