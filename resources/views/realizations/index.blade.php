@extends('layouts.app')
@section('title', 'Realisasi Anggaran')
@section('heading', 'Monitoring Realisasi TA '.$payload['year'])
@section('content')
@php
    $summary = $payload['summary'];
    $months = \App\Services\BudgetDashboard::MONTHS;
    $activities = collect($payload['rows'])->where('type', 'expense')->sortByDesc('realization_percent');
@endphp
<div class="page-head" data-reveal>
    <div>
        <nav class="crumbs" aria-label="Lokasi"><a href="{{ route('dashboard', ['year' => $payload['year']]) }}">Dashboard</a><span>/</span><span>Realisasi</span></nav>
        <h1>Realisasi Anggaran</h1>
        <p>Perbandingan rencana dan realisasi belanja, diperbarui setiap kali perangkat desa mencatat pengeluaran.</p>
    </div>
    <div class="page-actions"><a class="btn btn-secondary" href="{{ route('analytics', ['year' => $payload['year']]) }}"><x-icon name="chart-pie" /> Grafik & Analisis</a><a class="btn btn-primary" href="{{ route('reports.index', ['year' => $payload['year']]) }}"><x-icon name="file-text" /> Unduh laporan</a></div>
</div>

<x-uncounted-note :summary="$summary" data-reveal />

<section class="grid grid-4">
    <article class="card kpi kpi-blue" data-reveal><span class="kpi-icon"><x-icon name="shopping-cart" /></span><div><span class="kpi-label">{{ $summary['uncounted_lines'] ? 'Anggaran terverifikasi' : 'Rencana belanja' }}</span><p class="kpi-value">@if($summary['expense_count'])<x-money :value="$summary['counted_budget']" :decimals="2" />@else Belum tersedia @endif</p></div></article>
    <article class="card kpi kpi-purple" data-reveal><span class="kpi-icon"><x-icon name="chart-no-axes-column-increasing" /></span><div><span class="kpi-label">Realisasi</span><p class="kpi-value">@if($summary['realization'] !== null)<x-money :value="$summary['realization']" :decimals="2" />@else Belum tersedia @endif</p></div></article>
    <article class="card kpi kpi-green" data-reveal><span class="kpi-icon"><x-icon name="wallet" /></span><div><span class="kpi-label">Sisa anggaran</span><p class="kpi-value">@if($summary['remaining'] !== null)<x-money :value="$summary['remaining']" :decimals="2" />@else Belum tersedia @endif</p></div></article>
    <article class="card kpi kpi-amber" data-reveal><span class="kpi-icon"><x-icon name="gauge" /></span><div><span class="kpi-label">Penyerapan</span><p class="kpi-value">{{ $summary['absorption'] !== null ? number_format($summary['absorption'], 1, ',', '.').'%' : 'Belum tersedia' }}</p><x-progress :value="$summary['absorption'] ?? 0" color="amber" size="lg" label="Penyerapan anggaran" /></div></article>
</section>

<section class="grid grid-7-5">
    <article class="card" data-reveal>
        <header class="card-head"><div><h2>Perkembangan realisasi per bulan</h2><p>Garis biru: realisasi kumulatif. Garis abu-abu: rencana jika dibelanjakan rata tiap bulan.</p></div></header>
        <div class="card-body"><div class="chart chart-lg"><canvas id="monthly-chart" role="img" aria-label="Grafik realisasi per bulan dibandingkan rencana."></canvas></div></div>
    </article>
    <article class="card" data-reveal>
        <header class="card-head"><div><h2>Per bidang</h2><p>Progress rencana vs realisasi.</p></div></header>
        <div class="card-body">
            @foreach($payload['expense'] as $item)
                @php $percent = $item['realization_percent'] ?? 0; @endphp
                <div class="progress-item">
                    <div class="progress-item-head"><strong>{{ $item['name'] }}</strong><span class="num">{{ number_format($percent, 1, ',', '.') }}%</span></div>
                    <x-progress :value="$percent" :color="$percent >= 100 ? 'green' : ($percent >= 60 ? 'blue' : 'amber')" size="lg" :label="'Realisasi '.$item['name']" />
                    <div class="progress-item-foot"><span><x-money :value="$item['realization']" /> dari <x-money :value="$item['counted_amount']" />{{ $item['uncounted_lines'] ? ' (terverifikasi)' : '' }}</span><span>Sisa <x-money :value="$item['counted_amount'] - $item['realization']" /></span></div>
                </div>
            @endforeach
        </div>
    </article>
</section>

<section class="grid grid-2">
    <article class="card" data-reveal>
        <header class="card-head"><div><h2>Realisasi per kegiatan</h2><p>{{ $activities->count() }} kegiatan, diurutkan dari penyerapan tertinggi.</p></div></header>
        <div class="table-wrap scroll-panel">
            <table class="table stack">
                <thead><tr><th>Kegiatan</th><th class="text-right">Anggaran</th><th>Realisasi</th></tr></thead>
                <tbody>
                @foreach($activities as $row)
                    <tr style="--row: {{ $loop->index }}">
                        <td class="cell-main"><span class="strong">{{ $row['description'] }}</span><span class="sub">{{ $row['category'] }}</span></td>
                        <td class="text-right amount" data-label="Anggaran"><x-money :value="$row['amount']" /></td>
                        <td data-label="Realisasi"><x-realization-cell :row="$row" /></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </article>
    <article class="card" data-reveal id="transaksi">
        <header class="card-head">
            <div><h2>Daftar pengeluaran</h2><p>{{ $month ? 'Bulan '.$months[$month] : 'Semua bulan' }} · {{ $transactions->total() }} transaksi</p></div>
            <form method="GET" action="{{ route('realizations.index') }}#transaksi"><input type="hidden" name="year" value="{{ $payload['year'] }}"><label class="sr-only" for="month-filter">Bulan</label><select class="select" id="month-filter" name="month" data-auto-submit style="width: 170px"><option value="">Semua bulan</option>@foreach($months as $number => $label)<option value="{{ $number }}" @selected($month === $number)>{{ $label }}</option>@endforeach</select></form>
        </header>
        @forelse($transactions as $item)
            <div class="list-row">
                <span class="list-icon"><x-icon name="receipt-text" /></span>
                <div><p class="list-title">{{ $item->budgetLine?->description }}</p><p class="list-meta">{{ $item->spent_on->translatedFormat('j M Y') }} · {{ $item->description }}@if($item->budgetLine && $item->budgetLine->verification_status !== 'verified') · <span class="badge badge-gray" title="Kegiatan belum diverifikasi">tidak dihitung</span>@endif</p></div>
                <span class="amount num"><x-money :value="$item->amount" /></span>
            </div>
        @empty
            <div class="empty"><x-icon name="receipt-text" /><strong>Belum ada pengeluaran</strong><p>Tidak ada transaksi pada periode ini.</p></div>
        @endforelse
        @if($transactions->hasPages())
            <div class="pagination"><span>Halaman {{ $transactions->currentPage() }} dari {{ $transactions->lastPage() }}</span><nav>@if($transactions->previousPageUrl())<a class="btn btn-secondary btn-sm" href="{{ $transactions->previousPageUrl() }}#transaksi"><x-icon name="chevron-left" /> Sebelumnya</a>@endif @if($transactions->nextPageUrl())<a class="btn btn-secondary btn-sm" href="{{ $transactions->nextPageUrl() }}#transaksi">Berikutnya <x-icon name="chevron-right" /></a>@endif</nav></div>
        @endif
    </article>
</section>
@endsection

@push('scripts')<script>window.dashboardData = {{ Illuminate\Support\Js::from(['monthly' => $payload['monthly']]) }};</script>@endpush
