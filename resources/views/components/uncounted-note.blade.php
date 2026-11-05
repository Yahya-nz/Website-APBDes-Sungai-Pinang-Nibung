@props(['summary'])
@if(($summary['uncounted_lines'] ?? 0) > 0)
    @php
        $detail = 'anggaran '.\App\Support\Money::format($summary['uncounted_budget']);
        if ($summary['uncounted_spent'] > 0) {
            $detail .= ', pengeluaran tercatat '.\App\Support\Money::format($summary['uncounted_spent']);
        }
    @endphp
    <div {{ $attributes->merge(['class' => 'notice notice-info']) }}><x-icon name="info" /><span><strong>{{ $summary['uncounted_lines'] }} kegiatan belum diverifikasi</strong> ({{ $detail }}) tidak dihitung dalam realisasi, sisa anggaran, dan penyerapan sampai dicocokkan dengan dokumen sumber.</span></div>
@endif
