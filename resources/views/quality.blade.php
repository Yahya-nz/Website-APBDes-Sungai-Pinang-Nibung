@extends('layouts.app')
@section('title', 'Kualitas data')
@section('content')
@php
    $summary = $payload['summary'];
    $total = max($summary['total_count'], 1);
    $statuses = [
        ['key' => 'verified', 'label' => 'Terverifikasi', 'count' => $summary['verified_count'], 'text' => 'Angka dan uraian sudah dicocokkan dengan dokumen sumber oleh pengelola.'],
        ['key' => 'unverified', 'label' => 'Belum diverifikasi', 'count' => $summary['unverified_count'], 'text' => 'Sudah dimasukkan dan bisa dibaca, tetapi belum selesai dicek. Jangan dijadikan kesimpulan akhir.'],
        ['key' => 'unavailable', 'label' => 'Belum tersedia', 'count' => $summary['unavailable_count'], 'text' => 'Datanya belum diperoleh atau belum lengkap. Nilai yang belum diketahui tidak dianggap nol.'],
    ];
@endphp

<header class="page-header" data-reveal>
    <div>
        <p class="page-kicker">APBDes {{ $payload['year'] }}</p>
        <h1>Sumber dan status data</h1>
        <p class="page-lead">Dari dokumen mana angka-angka ini berasal, dan seberapa jauh sudah diperiksa.</p>
    </div>
    <div class="page-actions">
        <x-year-select :years="$payload['years']" :selected="$payload['year']" id="quality-year" />
    </div>
</header>

<section class="panel" aria-labelledby="status-title" data-reveal>
    <header class="panel-head">
        <div><h2 id="status-title">Status pemeriksaan</h2><p>{{ $summary['total_count'] }} baris anggaran pada tahun {{ $payload['year'] }}.</p></div>
    </header>
    <div class="status-meter" aria-hidden="true">
        @foreach($statuses as $status)
            @if($status['count'] > 0)<span class="meter-{{ $status['key'] }}" style="--fill: {{ round($status['count'] / $total * 100, 2) }}%"></span>@endif
        @endforeach
    </div>
    <dl class="status-list">
        @foreach($statuses as $status)
            <div>
                <dt><x-status :status="$status['key']" /></dt>
                <dd><strong>{{ $status['count'] }}</strong> baris</dd>
                <dd class="status-text">{{ $status['text'] }}</dd>
            </div>
        @endforeach
    </dl>
</section>

<section class="panel" aria-labelledby="dataset-title" data-reveal>
    <header class="panel-head">
        <div><h2 id="dataset-title">Dataset tahun {{ $payload['year'] }}</h2><p>Setiap impor atau input manual dicatat sebagai satu dataset.</p></div>
    </header>
    @forelse($payload['datasets'] as $dataset)
        @if($loop->first)<ul class="dataset-list">@endif
        <li>
            <span class="dataset-icon"><x-icon name="file-spreadsheet" /></span>
            <div class="dataset-body">
                <strong>{{ $dataset['name'] }}</strong>
                <span>{{ $dataset['source'] }}</span>
            </div>
            <div class="dataset-meta">
                <span>{{ $dataset['row_count'] }} baris</span>
                <span>{{ \Carbon\Carbon::parse($dataset['created_at'])->timezone('Asia/Jakarta')->translatedFormat('j M Y') }}</span>
                @if($dataset['is_demo'])<span class="demo-tag">contoh</span>@endif
                <x-status :status="$dataset['status']" />
            </div>
        </li>
        @if($loop->last)</ul>@endif
    @empty
        <div class="empty"><x-icon name="folder-open" /><strong>Belum ada dataset</strong><p>Sumber data akan muncul di sini setelah pengelola menambahkan data.</p></div>
    @endforelse
</section>

<aside class="callout" data-reveal>
    <h2>Cara membaca angka di portal ini</h2>
    <p>APBDes adalah rencana keuangan desa untuk satu tahun. Realisasi, penyerapan, dan sisa anggaran hanya dihitung bila seluruh belanja tahun itu sudah terverifikasi dan bukan data contoh. Status terverifikasi berarti data sudah dicocokkan pengelola dengan dokumennya; status ini bukan hasil audit.</p>
    <a class="text-link" href="{{ route('about') }}">Baca tentang portal <x-icon name="arrow-right" /></a>
</aside>
@endsection
