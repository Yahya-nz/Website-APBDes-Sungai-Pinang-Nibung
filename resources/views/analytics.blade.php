@extends('layouts.app')
@section('title', 'Grafik & Analisis')
@section('heading', 'Grafik & Analisis TA '.$payload['year'])
@section('content')
@php
    $summary = $payload['summary'];
    $total = max($summary['total_count'], 1);
    $status = collect([
        ['name' => 'Terverifikasi', 'amount' => $summary['verified_count'], 'color' => '#22C55E'],
        ['name' => 'Belum diverifikasi', 'amount' => $summary['unverified_count'], 'color' => '#F59E0B'],
        ['name' => 'Belum tersedia', 'amount' => $summary['unavailable_count'], 'color' => '#94A3B8'],
    ])->filter(fn ($item) => $item['amount'] > 0)->map(fn ($item) => $item + ['percentage' => round($item['amount'] / $total * 100, 1)])->values()->all();
    $topActivities = collect($payload['rows'])->where('type', 'expense')->sortByDesc('amount')->take(5);
    $measured = collect($payload['expense'])->whereNotNull('realization_percent');
    $bestBidang = $measured->sortByDesc('realization_percent')->first();
    $lowestBidang = $measured->sortBy('realization_percent')->first();
@endphp
<div class="page-head" data-reveal>
    <div>
        <nav class="crumbs" aria-label="Lokasi"><a href="{{ route('dashboard', ['year' => $payload['year']]) }}">Dashboard</a><span>/</span><span>Grafik & Analisis</span></nav>
        <h1>Grafik & Analisis</h1>
        <p>Gambaran komposisi anggaran, sumber dana, dan perkembangan realisasi dalam bentuk grafik.</p>
    </div>
</div>

@if($summary['total_count'] === 0)
    <div class="notice notice-info"><x-icon name="info" /><p>Data APBDes tahun {{ $payload['year'] }} belum tersedia.</p></div>
@else
<section class="grid grid-3">
    <article class="card kpi kpi-blue" data-reveal><span class="kpi-icon"><x-icon name="trophy" /></span><div><span class="kpi-label">Belanja terbesar</span><p class="kpi-value" style="font-size: 17px; white-space: normal">{{ $payload['expense'][0]['name'] ?? '–' }}</p><p class="kpi-meta"><span>{{ isset($payload['expense'][0]) ? number_format($payload['expense'][0]['percentage'], 1, ',', '.').'% dari total belanja' : '' }}</span></p></div></article>
    <article class="card kpi kpi-green" data-reveal><span class="kpi-icon"><x-icon name="trending-up" /></span><div><span class="kpi-label">Penyerapan tertinggi</span><p class="kpi-value" style="font-size: 17px; white-space: normal">{{ $bestBidang['name'] ?? '–' }}</p><p class="kpi-meta"><span>{{ $bestBidang ? number_format($bestBidang['realization_percent'] ?? 0, 1, ',', '.').'% terealisasi' : '' }}</span></p></div></article>
    <article class="card kpi kpi-amber" data-reveal><span class="kpi-icon"><x-icon name="hourglass" /></span><div><span class="kpi-label">Perlu dipantau</span><p class="kpi-value" style="font-size: 17px; white-space: normal">{{ $lowestBidang['name'] ?? '–' }}</p><p class="kpi-meta"><span>{{ $lowestBidang ? number_format($lowestBidang['realization_percent'] ?? 0, 1, ',', '.').'% terealisasi' : '' }}</span></p></div></article>
</section>

<section class="grid grid-2">
    <article class="card" data-reveal>
        <header class="card-head"><div><h2>Komposisi pendapatan</h2><p>Sumber penerimaan desa.</p></div></header>
        <div class="card-body"><div class="donut-wrap"><div class="donut"><canvas id="income-chart" role="img" aria-label="Diagram komposisi pendapatan."></canvas><div class="donut-center"><span>Pendapatan</span><strong><x-money :value="$summary['income']" /></strong></div></div><ul class="legend">@foreach($payload['income'] as $item)<li><span class="dot" style="--dot: {{ $item['color'] }}"></span><span>{{ $item['name'] }}</span><span class="pct">{{ number_format($item['percentage'], 1, ',', '.') }}%</span></li>@endforeach</ul></div></div>
    </article>
    <article class="card" data-reveal>
        <header class="card-head"><div><h2>Belanja menurut sumber dana</h2><p>Dari mana belanja setiap kegiatan dibiayai.</p></div></header>
        <div class="card-body"><div class="donut-wrap"><div class="donut"><canvas id="funding-chart" role="img" aria-label="Diagram belanja menurut sumber dana."></canvas><div class="donut-center"><span>Belanja</span><strong><x-money :value="$summary['expense']" /></strong></div></div><ul class="legend">@foreach($payload['funding_sources'] as $item)<li><span class="dot" style="--dot: {{ $item['color'] }}"></span><span>{{ $item['name'] }}<small><x-money :value="$item['amount']" /></small></span><span class="pct">{{ number_format($item['percentage'], 1, ',', '.') }}%</span></li>@endforeach</ul></div></div>
    </article>
</section>

<section class="grid grid-2">
    <article class="card" data-reveal>
        <header class="card-head"><div><h2>Komposisi belanja per bidang</h2><p>Klik batang untuk melihat kegiatannya.</p></div></header>
        <div class="card-body"><div class="chart"><canvas id="expense-chart" role="img" aria-label="Grafik batang belanja per bidang."></canvas></div></div>
    </article>
    <article class="card" data-reveal>
        <header class="card-head"><div><h2>Rencana vs realisasi per bidang</h2><p>Batang muda: anggaran. Batang biru: sudah dibelanjakan.</p></div></header>
        <div class="card-body"><div class="chart"><canvas id="compare-chart" role="img" aria-label="Grafik perbandingan anggaran dan realisasi per bidang."></canvas></div></div>
    </article>
</section>

<section class="grid grid-7-5">
    <article class="card" data-reveal>
        <header class="card-head"><div><h2>Realisasi bulanan</h2><p>Pengeluaran per bulan dan akumulasinya dibandingkan rencana.</p></div></header>
        <div class="card-body"><div class="chart chart-lg"><canvas id="monthly-chart" role="img" aria-label="Grafik realisasi bulanan."></canvas></div></div>
    </article>
    <article class="card" data-reveal>
        <header class="card-head"><div><h2>Status verifikasi data</h2><p>{{ $summary['total_count'] }} baris anggaran.</p></div></header>
        <div class="card-body"><div class="donut-wrap"><div class="donut" style="width: 180px; height: 180px"><canvas id="status-chart" role="img" aria-label="Diagram status verifikasi."></canvas><div class="donut-center"><span>Terverifikasi</span><strong>{{ round($summary['verified_count'] / $total * 100) }}%</strong></div></div><ul class="legend">@foreach($status as $item)<li><span class="dot" style="--dot: {{ $item['color'] }}"></span><span>{{ $item['name'] }}</span><span class="pct">{{ $item['amount'] }}</span></li>@endforeach</ul></div></div>
    </article>
</section>

<section class="card" data-reveal style="margin-bottom: 20px">
    <header class="card-head"><div><h2>Lima kegiatan dengan anggaran terbesar</h2><p>Bersama-sama {{ number_format($topActivities->sum('amount') / max($summary['expense'], 1) * 100, 1, ',', '.') }}% dari total belanja.</p></div></header>
    <div class="card-body">
        @foreach($topActivities as $row)
            <div class="progress-item">
                <div class="progress-item-head"><strong>{{ $row['description'] }}</strong><span class="amount"><x-money :value="$row['amount']" /></span></div>
                <x-progress :value="$row['amount'] / max($topActivities->max('amount'), 1) * 100" color="blue" :label="$row['description']" />
                <div class="progress-item-foot"><span>{{ $row['category'] }} · {{ $row['funding_source'] }}</span><span>{{ number_format($row['amount'] / max($summary['expense'], 1) * 100, 1, ',', '.') }}% belanja · {{ $row['realization_counted'] ? 'realisasi '.number_format($row['realization_percent'] ?? 0, 0, ',', '.').'%' : 'realisasi belum dihitung' }}</span></div>
            </div>
        @endforeach
    </div>
</section>
@endif
@endsection

@push('scripts')<script>window.dashboardData = {{ Illuminate\Support\Js::from([
    'income' => $payload['income'],
    'expense' => array_map(fn (array $item): array => $item + ['url' => route('budgets.index', ['type' => 'expense', 'category' => $item['name'], 'year' => $payload['year']])], $payload['expense']),
    'funding' => $payload['funding_sources'],
    'monthly' => $payload['monthly'],
    'status' => $status,
]) }};</script>@endpush
