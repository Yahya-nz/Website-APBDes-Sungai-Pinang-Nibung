@extends('layouts.app')

@section('title', 'Kualitas data')

@section('content')
@php
    $summary = $payload['summary'];
    $unavailableCount = $summary['unavailable_count'];
@endphp
<div class="page-heading">
    <div><div class="page-kicker">APBDes {{ $payload['year'] }}</div><h1>Kualitas data</h1><p>Dari dokumen mana angka-angka ini berasal, dan seberapa jauh sudah diperiksa.</p></div>
    <div class="heading-actions"><form method="GET" class="year-form"><x-icon name="calendar-days" /><label class="sr-only" for="quality-year">Tahun kualitas data</label><select id="quality-year" name="year" data-auto-submit>@foreach($payload['years'] as $year)<option value="{{ $year }}" @selected($year == $payload['year'])>TA {{ $year }}</option>@endforeach</select><noscript><button>Pilih</button></noscript></form><a class="btn btn-secondary" href="{{ route('budgets.index', ['year' => $payload['year']]) }}">Telusuri anggaran <x-icon name="arrow-up-right" /></a></div>
</div>

<div class="quality-metrics">
    <section class="card quality-metric"><div class="card-body"><span class="metric-icon"><x-icon name="shield-check" /></span><span class="section-label">Terverifikasi</span><strong class="metric-value">{{ $summary['verified_count'] }} <small>data</small></strong><p class="text-muted">Sudah dicocokkan dengan dokumen sumber.</p></div></section>
    <section class="card quality-metric"><div class="card-body"><span class="metric-icon amber"><x-icon name="scan-line" /></span><span class="section-label">Belum diverifikasi</span><strong class="metric-value">{{ $summary['unverified_count'] }} <small>data</small></strong><p class="text-muted">Masih menunggu pemeriksaan pengelola.</p></div></section>
    <section class="card quality-metric"><div class="card-body"><span class="metric-icon muted"><x-icon name="file-question" /></span><span class="section-label">Belum tersedia</span><strong class="metric-value">{{ $unavailableCount }} <small>data</small></strong><p class="text-muted">Datanya belum diperoleh atau belum lengkap.</p></div></section>
</div>

<div class="quality-grid">
    <section class="card">
        <div class="card-header"><div><h2>Sumber dataset</h2><p>Dataset yang dipakai untuk tahun anggaran {{ $payload['year'] }}.</p></div><x-icon name="files" /></div>
        <div class="card-body source-list">
            @forelse($payload['datasets'] as $dataset)
            <article class="source-item">
                <div class="source-icon"><x-icon name="file-spreadsheet" /></div>
                <div class="source-content"><h3>{{ $dataset['name'] }}</h3><p>{{ $dataset['source'] }}</p><div class="source-meta"><span>{{ $dataset['row_count'] }} baris</span><span>{{ \Carbon\Carbon::parse($dataset['created_at'])->timezone('Asia/Jakarta')->translatedFormat('d M Y') }}</span><x-status :status="$dataset['status']" />@if($dataset['is_demo'])<span class="badge badge-demo">Data contoh</span>@endif</div></div>
            </article>
            @empty
            <div class="empty-state"><x-icon name="folder-open" /><h3>Belum ada dataset</h3><p>Informasi sumber akan muncul setelah pengelola menambahkan data.</p></div>
            @endforelse
        </div>
    </section>

    <section class="card verification-explainer">
        <div class="card-header"><div><h2>Arti setiap status</h2><p>Baca status sebelum memakai angkanya.</p></div></div>
        <div class="card-body">
            <div class="status-explanation"><x-status status="verified" /><p>Angka dan uraian telah dicocokkan dengan dokumen sumber oleh pengelola.</p></div>
            <div class="status-explanation"><x-status status="unverified" /><p>Data dapat ditelusuri, tetapi belum selesai diperiksa. Hindari mengambil kesimpulan akhir.</p></div>
            <div class="status-explanation"><x-status status="unavailable" /><p>Data belum diperoleh atau belum memadai. Nilai yang belum diketahui tidak dianggap nol.</p></div>
        </div>
    </section>
</div>

<section class="card quality-note"><div class="card-body"><div class="quality-note-icon"><x-icon name="info" /></div><div><h3>Cara membaca angka di portal ini</h3><p>APBDes adalah rencana keuangan. Realisasi, penyerapan, dan sisa anggaran hanya dihitung saat data pendukungnya tersedia dan terverifikasi. Status verifikasi menunjukkan pemeriksaan data oleh pengelola, bukan keputusan audit. Dataset demo merupakan simulasi dan bukan informasi keuangan resmi desa.</p></div><a class="btn btn-ghost" href="{{ route('about') }}">Tentang portal <x-icon name="arrow-right" /></a></div></section>
@endsection
