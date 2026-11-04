@extends('layouts.app')
@section('title', 'Profil Desa')
@section('heading', 'Profil Desa Sungai Pinang Nibung')
@section('content')
@php
    $summary = $payload['summary'];
    $bidangIcons = ['Penyelenggaraan Pemerintahan Desa' => ['building-2', 'blue'], 'Pelaksanaan Pembangunan Desa' => ['hammer', 'green'], 'Pembinaan Kemasyarakatan Desa' => ['users-round', 'amber'], 'Pemberdayaan Masyarakat Desa' => ['sprout', 'purple'], 'Penanggulangan Bencana' => ['life-buoy', 'blue']];
@endphp
<section class="hero" data-reveal>
    <div class="hero-copy">
        <span class="hero-kicker"><x-icon name="map-pin" /> Kabupaten Ogan Ilir, Sumatera Selatan</span>
        <h2>Desa Sungai Pinang Nibung</h2>
        <p>“Desa Membangun Indonesia”</p>
        <div class="hero-meta"><span><x-icon name="user-round" /> Kepala Desa: Kamaludin</span><span><x-icon name="calendar-days" /> APBDes Tahun {{ $payload['year'] }}</span></div>
    </div>
    <x-village-art class="hero-art" />
</section>

<section class="grid grid-7-5">
    <article class="card" data-reveal>
        <header class="card-head"><div><h2>Identitas desa</h2><p>Wilayah administrasi dan pemerintahan desa.</p></div></header>
        <div class="card-body">
            <dl class="dl">
                <dt>Nama desa</dt><dd>Sungai Pinang Nibung</dd>
                <dt>Kecamatan</dt><dd>Sungai Pinang</dd>
                <dt>Kabupaten</dt><dd>Ogan Ilir</dd>
                <dt>Provinsi</dt><dd>Sumatera Selatan</dd>
                <dt>Kepala desa</dt><dd>Kamaludin</dd>
                <dt>Perangkat desa</dt><dd>Sekretaris desa, kepala urusan, kepala seksi, dan kepala dusun</dd>
                <dt>Badan Permusyawaratan Desa</dt><dd>Ketua, wakil ketua, sekretaris, dan anggota BPD</dd>
            </dl>
        </div>
    </article>
    <article class="card" data-reveal>
        <header class="card-head"><div><h2>APBDes {{ $payload['year'] }} singkat</h2><p>Dari baliho APBDes yang dipasang desa.</p></div></header>
        <div class="card-body">
            <div class="progress-item"><div class="progress-item-head"><strong>Pendapatan</strong><span class="amount"><x-money :value="$summary['income']" :decimals="2" /></span></div><x-progress value="100" color="green" label="Pendapatan" /></div>
            <div class="progress-item"><div class="progress-item-head"><strong>Belanja</strong><span class="amount"><x-money :value="$summary['expense']" :decimals="2" /></span></div><x-progress :value="$summary['income'] > 0 ? $summary['expense'] / $summary['income'] * 100 : 0" color="blue" label="Belanja" /></div>
            <div class="progress-item"><div class="progress-item-head"><strong>Realisasi belanja</strong><span class="amount">@if($summary['realization'] !== null)<x-money :value="$summary['realization']" :decimals="2" />@else Belum tersedia @endif</span></div><x-progress :value="$summary['absorption'] ?? 0" color="purple" label="Realisasi" /></div>
            <p class="hint" style="margin-top: 12px">{{ $summary['expense_count'] }} kegiatan belanja di {{ count($payload['expense']) }} bidang dan {{ count($payload['income']) }} sumber pendapatan.</p>
        </div>
        <footer class="card-foot"><a class="link" href="{{ route('budgets.index', ['year' => $payload['year']]) }}">Lihat rincian kegiatan <x-icon name="arrow-right" /></a></footer>
    </article>
</section>

<section class="grid grid-3">
    @foreach($payload['expense'] as $item)
        @php [$icon, $tone] = $bidangIcons[$item['name']] ?? ['layers', 'blue']; @endphp
        <article class="card kpi kpi-{{ $tone }}" data-reveal>
            <span class="kpi-icon"><x-icon :name="$icon" /></span>
            <div><span class="kpi-label">Bidang {{ $item['name'] }}</span><p class="kpi-value"><x-money :value="$item['amount']" :decimals="2" /></p><x-progress :value="$item['percentage']" :color="$tone" :label="'Porsi '.$item['name']" /><p class="kpi-meta"><span>{{ number_format($item['percentage'], 1, ',', '.') }}% dari total belanja</span></p></div>
        </article>
    @endforeach
</section>
@endsection
