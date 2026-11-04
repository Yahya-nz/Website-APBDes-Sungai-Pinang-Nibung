@extends('layouts.app')
@section('title', 'Status Verifikasi')
@section('heading', 'Status Verifikasi Data TA '.$payload['year'])
@section('content')
@php
    $summary = $payload['summary'];
    $total = max($summary['total_count'], 1);
    $statuses = [
        ['verified', 'Terverifikasi', $summary['verified_count'], 'Angka dan uraian sudah dicocokkan dengan dokumen sumber.', 'green', 'shield-check'],
        ['unverified', 'Belum diverifikasi', $summary['unverified_count'], 'Sudah dimasukkan, tetapi masih perlu dicek ulang.', 'amber', 'scan-search'],
        ['unavailable', 'Belum tersedia', $summary['unavailable_count'], 'Data belum diperoleh. Nilai yang belum diketahui tidak dianggap nol.', 'purple', 'file-question'],
    ];
    $mismatches = collect($payload['checks'])->filter(fn ($check) => abs($check['difference']) >= 0.01);
@endphp
<div class="page-head" data-reveal>
    <div>
        <nav class="crumbs" aria-label="Lokasi"><a href="{{ route('dashboard', ['year' => $payload['year']]) }}">Dashboard</a><span>/</span><span>Status verifikasi</span></nav>
        <h1>Status Verifikasi Data</h1>
        <p>Asal data, hasil pemeriksaannya, dan pemeriksaan otomatis kecocokan angka.</p>
    </div>
</div>

<section class="grid grid-3">
    @foreach($statuses as [$key, $label, $count, $text, $tone, $icon])
        <article class="card kpi kpi-{{ $tone }}" data-reveal><span class="kpi-icon"><x-icon :name="$icon" /></span><div><span class="kpi-label">{{ $label }}</span><p class="kpi-value">{{ $count }} <span style="font-size: 14px; font-weight: 500; color: var(--muted)">baris</span></p><x-progress :value="$count / $total * 100" :color="$tone" :label="$label" /><p class="kpi-meta"><span>{{ $text }}</span></p></div></article>
    @endforeach
</section>

<section class="grid grid-7-5">
    <article class="card" data-reveal>
        <header class="card-head"><div><h2>Pemeriksaan otomatis sumber dana</h2><p>Belanja per sumber dana dibandingkan dengan pendapatan dari sumber yang sama.</p></div>@if($mismatches->isEmpty())<span class="badge badge-green">Semua cocok</span>@else<span class="badge badge-amber">{{ $mismatches->count() }} perlu dicek</span>@endif</header>
        <div class="table-wrap">
            <table class="table stack">
                <thead><tr><th>Sumber dana</th><th class="text-right">Pendapatan</th><th class="text-right">Belanja</th><th class="text-right">Selisih</th><th>Hasil</th></tr></thead>
                <tbody>
                @forelse($payload['checks'] as $check)
                    @php $ok = abs($check['difference']) < 0.01; @endphp
                    <tr style="--row: {{ $loop->index }}"><td class="cell-main strong">{{ $check['source'] }}</td><td class="text-right" data-label="Pendapatan"><x-money :value="$check['income']" :decimals="2" /></td><td class="text-right" data-label="Belanja"><x-money :value="$check['expense']" :decimals="2" /></td><td class="text-right amount" data-label="Selisih" style="color: {{ $ok ? 'inherit' : '#b45309' }}">{{ $check['difference'] > 0 ? '+' : '' }}<x-money :value="$check['difference']" :decimals="2" /></td><td data-label="Hasil">@if($ok)<span class="badge badge-green status">Cocok</span>@else<span class="badge badge-amber status">Perlu dicek</span>@endif</td></tr>
                @empty
                    <tr><td colspan="5"><div class="empty"><p>Belum ada data untuk diperiksa.</p></div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($mismatches->isNotEmpty())
            <footer class="card-foot" style="display: block; color: var(--ink-2); line-height: 1.6">Selisih Dana Desa dan ADD sama-sama Rp9.600.000, sama dengan anggaran <strong>Insentif RT</strong> yang di baliho tertulis bersumber Dana Desa. Kemungkinan sumber dananya ADD. Karena itu baris Insentif RT ditandai <em>belum diverifikasi</em> sampai dicek ke dokumen APBDes.</footer>
        @endif
    </article>
    <article class="card" data-reveal>
        <header class="card-head"><div><h2>Sumber dataset</h2><p>Asal data tahun {{ $payload['year'] }}.</p></div></header>
        @forelse($payload['datasets'] as $dataset)
            <div class="list-row">
                <span class="list-icon" style="background: var(--blue-50); color: var(--blue)"><x-icon name="file-spreadsheet" /></span>
                <div><p class="list-title">{{ $dataset['name'] }}</p><p class="list-meta">{{ $dataset['source'] }} · {{ $dataset['row_count'] }} baris · {{ \Carbon\Carbon::parse($dataset['created_at'])->translatedFormat('j M Y') }}</p></div>
                <x-status :status="$dataset['status']" />
            </div>
        @empty
            <div class="empty"><x-icon name="folder-open" /><p>Belum ada dataset.</p></div>
        @endforelse
        <footer class="card-foot" style="display: block; line-height: 1.6">Status terverifikasi berarti angka sudah dicocokkan pengelola dengan dokumennya, bukan hasil audit. Realisasi contoh tidak dihitung sebagai data resmi.</footer>
    </article>
</section>
@endsection
