@extends('layouts.app')
@section('title', 'Pembiayaan')
@section('heading', 'Pembiayaan Desa TA '.$payload['year'])
@section('content')
@php $summary = $payload['summary']; $hasData = $summary['total_count'] > 0; @endphp
<div class="page-head" data-reveal>
    <div>
        <nav class="crumbs" aria-label="Lokasi"><a href="{{ route('dashboard', ['year' => $payload['year']]) }}">Dashboard</a><span>/</span><span>APBDes {{ $payload['year'] }}</span></nav>
        <h1>Pembiayaan Desa</h1>
        <p>Selisih pendapatan dan belanja, serta penerimaan atau pengeluaran pembiayaan.</p>
    </div>
</div>

<section class="grid grid-3">
    <article class="card kpi kpi-green" data-reveal><span class="kpi-icon"><x-icon name="hand-coins" /></span><div><span class="kpi-label">Pendapatan</span><p class="kpi-value">@if($hasData)<x-money :value="$summary['income']" :decimals="2" />@else Belum tersedia @endif</p></div></article>
    <article class="card kpi kpi-blue" data-reveal><span class="kpi-icon"><x-icon name="shopping-cart" /></span><div><span class="kpi-label">Belanja</span><p class="kpi-value">@if($hasData)<x-money :value="$summary['expense']" :decimals="2" />@else Belum tersedia @endif</p></div></article>
    <article class="card kpi kpi-amber" data-reveal><span class="kpi-icon"><x-icon name="scale" /></span><div><span class="kpi-label">Surplus / (defisit)</span><p class="kpi-value">@if($hasData)<x-money :value="$summary['balance']" :decimals="2" />@else Belum tersedia @endif</p><p class="kpi-meta"><span>{{ ! $hasData ? '–' : ($summary['balance'] == 0 ? 'Pendapatan dan belanja seimbang' : ($summary['balance'] > 0 ? 'Pendapatan lebih besar dari belanja' : 'Belanja lebih besar dari pendapatan')) }}</span></p></div></article>
</section>

<section class="grid grid-7-5">
    <article class="card" data-reveal>
        <header class="card-head"><div><h2>Rincian pembiayaan</h2><p>Sesuai baliho APBDes {{ $payload['year'] }}.</p></div></header>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Uraian</th><th class="text-right">Nilai (Rp)</th></tr></thead>
                <tbody>
                    <tr><td class="strong">Penerimaan pembiayaan (SiLPA tahun sebelumnya, pencairan dana cadangan)</td><td class="text-right amount">@if($hasData)<x-money :value="0" :decimals="2" />@else – @endif</td></tr>
                    <tr><td class="strong">Pengeluaran pembiayaan (pembentukan dana cadangan, penyertaan modal)</td><td class="text-right amount">@if($hasData)<x-money :value="0" :decimals="2" />@else – @endif</td></tr>
                    <tr><td class="strong">Surplus / (defisit)</td><td class="text-right amount">@if($hasData)<x-money :value="$summary['balance']" :decimals="2" />@else – @endif</td></tr>
                </tbody>
                <tfoot><tr><th>Sisa lebih pembiayaan anggaran (SiLPA)</th><td class="text-right">@if($hasData)<x-money :value="$summary['balance'] + $summary['financing']" :decimals="2" />@else – @endif</td></tr></tfoot>
            </table>
        </div>
    </article>
    <article class="card" data-reveal>
        <header class="card-head"><div><h2>Arti istilah</h2><p>Supaya angka pembiayaan mudah dibaca.</p></div></header>
        <div class="card-body prose">
            <p><strong>Surplus/defisit</strong> adalah selisih pendapatan dan belanja. Pada APBDes {{ $payload['year'] }}, pendapatan dan belanja sama besar sehingga tidak ada surplus maupun defisit.</p>
            <p><strong>Pembiayaan</strong> dipakai untuk menutup defisit atau memanfaatkan surplus, misalnya dari sisa anggaran tahun lalu (SiLPA). Baliho mencantumkan “Sisa Lebih Pembiayaan: –”, artinya tidak ada pembiayaan tahun ini.</p>
        </div>
    </article>
</section>
@endsection
