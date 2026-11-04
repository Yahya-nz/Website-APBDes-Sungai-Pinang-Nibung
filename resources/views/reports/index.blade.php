@extends('layouts.app')
@section('title', 'Laporan')
@section('heading', 'Laporan Realisasi Anggaran')
@section('content')
@php
    $months = \App\Services\BudgetDashboard::MONTHS;
    $query = ['year' => $report['year'], 'period' => $report['period'], 'value' => $report['value']];
    $totals = $report['totals'];
@endphp
<div class="page-head" data-reveal>
    <div>
        <nav class="crumbs" aria-label="Lokasi"><a href="{{ route('dashboard', ['year' => $report['year']]) }}">Dashboard</a><span>/</span><span>Laporan</span></nav>
        <h1>Laporan Realisasi Anggaran</h1>
        <p>Laporan dibuat otomatis dari data APBDes dan catatan pengeluaran. Pilih periode, periksa ringkasannya, lalu unduh.</p>
    </div>
</div>

<section class="card" data-reveal style="margin-bottom: 20px">
    <form class="filters" method="GET" action="{{ route('reports.index') }}" style="align-items: flex-end">
        <div class="field"><label for="report-year">Tahun</label><select class="select" id="report-year" name="year" style="width: 140px">@foreach($years as $year)<option value="{{ $year }}" @selected($year == $report['year'])>{{ $year }}</option>@endforeach</select></div>
        <div class="field"><label for="report-period">Jenis laporan</label><select class="select" id="report-period" name="period" data-period-toggle style="width: 180px"><option value="month" @selected($report['period'] === 'month')>Bulanan</option><option value="semester" @selected($report['period'] === 'semester')>Semester</option></select></div>
        <div class="field" data-period="month" @if($report['period'] !== 'month') hidden @endif><label for="report-month">Bulan</label><select class="select" id="report-month" name="value" style="width: 180px" @disabled($report['period'] !== 'month')>@foreach($months as $number => $label)<option value="{{ $number }}" @selected($report['period'] === 'month' && $report['value'] === $number)>{{ $label }}</option>@endforeach</select></div>
        <div class="field" data-period="semester" @if($report['period'] !== 'semester') hidden @endif><label for="report-semester">Semester</label><select class="select" id="report-semester" name="value" style="width: 220px" @disabled($report['period'] !== 'semester')><option value="1" @selected($report['period'] === 'semester' && $report['value'] === 1)>Semester I (Januari–Juni)</option><option value="2" @selected($report['period'] === 'semester' && $report['value'] === 2)>Semester II (Juli–Desember)</option></select></div>
        <button class="btn btn-secondary" type="submit"><x-icon name="eye" /> Tampilkan</button>
        <div class="page-actions" style="margin-left: auto">
            <a class="btn btn-danger" href="{{ route('reports.download', ['format' => 'pdf'] + $query) }}"><x-icon name="file-text" /> Unduh PDF</a>
            <a class="btn btn-success" href="{{ route('reports.download', ['format' => 'xlsx'] + $query) }}"><x-icon name="file-spreadsheet" /> Unduh Excel</a>
        </div>
    </form>
</section>

@if($report['is_demo'])
    <div class="notice notice-warn" data-reveal><x-icon name="flask-conical" /><p>Realisasi pada laporan ini adalah <strong>data contoh</strong>. Berkas yang diunduh ikut diberi keterangan “CONTOH”.</p></div>
@endif

<section class="grid grid-4">
    <article class="card kpi kpi-blue" data-reveal><span class="kpi-icon"><x-icon name="calendar-range" /></span><div><span class="kpi-label">Periode</span><p class="kpi-value" style="font-size: 18px">{{ $report['label'] }}</p><p class="kpi-meta"><span>{{ \Carbon\Carbon::parse($report['start'])->translatedFormat('j M') }} – {{ \Carbon\Carbon::parse($report['end'])->translatedFormat('j M Y') }}</span></p></div></article>
    <article class="card kpi kpi-green" data-reveal><span class="kpi-icon"><x-icon name="receipt-text" /></span><div><span class="kpi-label">Realisasi periode ini</span><p class="kpi-value"><x-money :value="$totals['period']" :decimals="2" /></p><p class="kpi-meta"><span>{{ count($report['transactions']) }} transaksi</span></p></div></article>
    <article class="card kpi kpi-purple" data-reveal><span class="kpi-icon"><x-icon name="chart-no-axes-column-increasing" /></span><div><span class="kpi-label">Realisasi s.d. periode</span><p class="kpi-value"><x-money :value="$totals['cumulative']" :decimals="2" /></p><x-progress :value="$totals['percent']" color="purple" label="Penyerapan kumulatif" /><p class="kpi-meta"><span>{{ number_format($totals['percent'], 1, ',', '.') }}% dari anggaran</span></p></div></article>
    <article class="card kpi kpi-amber" data-reveal><span class="kpi-icon"><x-icon name="wallet" /></span><div><span class="kpi-label">Sisa anggaran</span><p class="kpi-value"><x-money :value="$totals['remaining']" :decimals="2" /></p></div></article>
</section>

<section class="card" data-reveal style="margin-bottom: 20px">
    <header class="card-head"><div><h2>Rekap per bidang — {{ $report['label'] }}</h2><p>Isi sama dengan halaman pertama laporan PDF dan sheet “Rekap” di Excel.</p></div></header>
    <div class="table-wrap">
        <table class="table stack">
            <thead><tr><th>Bidang</th><th class="text-right">Anggaran</th><th class="text-right">Realisasi periode ini</th><th class="text-right">Realisasi s.d. periode</th><th class="text-right">Sisa</th><th>Penyerapan</th></tr></thead>
            <tbody>
            @forelse($report['categories'] as $category)
                <tr style="--row: {{ $loop->index }}"><td class="cell-main strong">{{ $category['name'] }}</td><td class="text-right" data-label="Anggaran"><x-money :value="$category['budget']" :decimals="2" /></td><td class="text-right" data-label="Periode ini"><x-money :value="$category['period']" :decimals="2" /></td><td class="text-right amount" data-label="S.d. periode"><x-money :value="$category['cumulative']" :decimals="2" /></td><td class="text-right" data-label="Sisa"><x-money :value="$category['remaining']" :decimals="2" /></td><td data-label="Penyerapan"><div class="mini-progress"><x-progress :value="$category['percent']" color="blue" :label="'Penyerapan '.$category['name']" /><span>{{ number_format($category['percent'], 1, ',', '.') }}%</span></div></td></tr>
            @empty
                <tr><td colspan="6"><div class="empty"><x-icon name="file-x" /><p>Belum ada data belanja untuk tahun {{ $report['year'] }}.</p></div></td></tr>
            @endforelse
            </tbody>
            @if(count($report['categories']))
                <tfoot><tr><th>Total</th><td class="text-right"><x-money :value="$totals['budget']" :decimals="2" /></td><td class="text-right"><x-money :value="$totals['period']" :decimals="2" /></td><td class="text-right"><x-money :value="$totals['cumulative']" :decimals="2" /></td><td class="text-right"><x-money :value="$totals['remaining']" :decimals="2" /></td><td>{{ number_format($totals['percent'], 1, ',', '.') }}%</td></tr></tfoot>
            @endif
        </table>
    </div>
</section>

<section class="card" data-reveal style="margin-bottom: 20px">
    <header class="card-head"><div><h2>Transaksi pada periode ini</h2><p>{{ count($report['transactions']) }} pengeluaran tercatat.</p></div></header>
    <div class="table-wrap">
        <table class="table stack">
            <thead><tr><th>Tanggal</th><th>Kegiatan</th><th>Keterangan</th><th class="text-right">Nominal</th></tr></thead>
            <tbody>
            @forelse(array_slice($report['transactions'], 0, 40) as $item)
                <tr style="--row: {{ $loop->index }}"><td data-label="Tanggal" style="white-space: nowrap">{{ \Carbon\Carbon::parse($item['date'])->translatedFormat('j M Y') }}</td><td class="cell-main"><span class="strong">{{ $item['activity'] }}</span><span class="sub">{{ $item['category'] }}</span></td><td data-label="Keterangan">{{ $item['description'] }}@if($item['is_demo']) <span class="badge badge-demo">contoh</span>@endif</td><td class="text-right amount" data-label="Nominal"><x-money :value="$item['amount']" :decimals="2" /></td></tr>
            @empty
                <tr><td colspan="4"><div class="empty"><x-icon name="receipt-text" /><p>Tidak ada pengeluaran pada periode ini.</p></div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if(count($report['transactions']) > 40)<footer class="card-foot"><span>Menampilkan 40 dari {{ count($report['transactions']) }} transaksi. Daftar lengkap ada di berkas PDF dan Excel.</span></footer>@endif
</section>
@endsection
