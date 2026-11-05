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
@endphp
<div class="page-head" data-reveal>
    <div>
        <nav class="crumbs" aria-label="Lokasi"><a href="{{ route('dashboard', ['year' => $payload['year']]) }}">Dashboard</a><span>/</span><span>Status verifikasi</span></nav>
        <h1>Status Verifikasi Data</h1>
        <p>Asal data dan status pemeriksaannya terhadap dokumen sumber.</p>
    </div>
</div>

<section class="grid grid-3">
    @foreach($statuses as [$key, $label, $count, $text, $tone, $icon])
        <article class="card kpi kpi-{{ $tone }}" data-reveal><span class="kpi-icon"><x-icon :name="$icon" /></span><div><span class="kpi-label">{{ $label }}</span><p class="kpi-value">{{ $count }} <span style="font-size: 14px; font-weight: 500; color: var(--muted)">baris</span></p><x-progress :value="$count / $total * 100" :color="$tone" :label="$label" /><p class="kpi-meta"><span>{{ $text }}</span></p></div></article>
    @endforeach
</section>

<section class="grid">
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
        <footer class="card-foot" style="display: block; line-height: 1.6">Status terverifikasi berarti angka sudah dicocokkan pengelola dengan dokumennya, bukan hasil audit. Realisasi, sisa anggaran, dan penyerapan hanya dihitung dari kegiatan yang sudah terverifikasi.</footer>
    </article>
</section>
@endsection
