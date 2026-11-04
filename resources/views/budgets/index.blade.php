@extends('layouts.app')
@php
    $type = in_array(request('type'), ['income', 'expense'], true) ? request('type') : null;
    $pageTitle = ['income' => 'Pendapatan', 'expense' => 'Belanja'][$type] ?? 'Rincian anggaran';
    $summary = $payload['summary'];
    $hasFilter = filled(request('q')) || filled(request('category')) || filled(request('status'));
    $tabs = [
        ['label' => 'Semua', 'type' => null, 'count' => $summary['total_count']],
        ['label' => 'Pendapatan', 'type' => 'income', 'count' => $summary['income_count']],
        ['label' => 'Belanja', 'type' => 'expense', 'count' => $summary['expense_count']],
    ];
@endphp
@section('title', $pageTitle)
@section('content')
<header class="page-header" data-reveal>
    <div>
        <p class="page-kicker">APBDes {{ $payload['year'] }}</p>
        <h1>{{ $pageTitle }}</h1>
        <p class="page-lead">
            @if($type === 'income') Rencana penerimaan desa menurut sumbernya.
            @elseif($type === 'expense') Rencana belanja desa menurut bidang dan kegiatan.
            @else Seluruh baris anggaran beserta dokumen sumber dan status pemeriksaannya.
            @endif
        </p>
    </div>
    <div class="page-actions">
        <a class="button button-outline" href="{{ route('export', request()->query()) }}"><x-icon name="download" /> Unduh CSV</a>
    </div>
</header>

@if($payload['is_demo'])<x-demo-notice data-reveal />@endif

<dl class="totals" data-reveal>
    <div>
        <dt><span class="type-dot type-income"></span>Rencana pendapatan</dt>
        <dd>@if($summary['income_count'] > 0)<x-money :value="$summary['income']" />@else Belum tersedia @endif</dd>
    </div>
    <div>
        <dt><span class="type-dot type-expense"></span>Rencana belanja</dt>
        <dd>@if($summary['expense_count'] > 0)<x-money :value="$summary['expense']" />@else Belum tersedia @endif</dd>
    </div>
    <div class="totals-note">Total dihitung dari seluruh baris tahun {{ $payload['year'] }}, tidak terpengaruh filter.</div>
</dl>

<section class="panel panel-flush" aria-label="Tabel rincian anggaran" data-reveal>
    <nav class="tabs" aria-label="Jenis anggaran">
        @foreach($tabs as $tab)
            <a class="tab {{ $type === $tab['type'] ? 'is-active' : '' }}" href="{{ route('budgets.index', array_filter(array_merge(request()->except(['type', 'category']), ['type' => $tab['type']]), 'filled')) }}" @if($type === $tab['type']) aria-current="page" @endif>{{ $tab['label'] }} <span>{{ $tab['count'] }}</span></a>
        @endforeach
    </nav>

    <form class="filters" method="GET" action="{{ route('budgets.index') }}">
        @if($type)<input type="hidden" name="type" value="{{ $type }}">@endif
        <div class="field-search">
            <x-icon name="search" />
            <label class="sr-only" for="budget-search">Cari kegiatan, kategori, atau sumber</label>
            <input type="search" id="budget-search" name="q" value="{{ request('q') }}" placeholder="Cari kegiatan, kategori, atau sumber">
        </div>
        <label class="field-select"><span class="sr-only">Tahun anggaran</span>
            <select name="year">@foreach($payload['years'] as $year)<option value="{{ $year }}" @selected($year == $payload['year'])>{{ $year }}</option>@endforeach</select>
        </label>
        <label class="field-select"><span class="sr-only">Bidang atau sumber</span>
            <select name="category"><option value="">Semua bidang</option>@foreach($payload['categories'] as $category)<option @selected(request('category') === $category)>{{ $category }}</option>@endforeach</select>
        </label>
        <label class="field-select"><span class="sr-only">Status pemeriksaan</span>
            <select name="status"><option value="">Semua status</option><option value="verified" @selected(request('status') === 'verified')>Terverifikasi</option><option value="unverified" @selected(request('status') === 'unverified')>Belum diverifikasi</option><option value="unavailable" @selected(request('status') === 'unavailable')>Belum tersedia</option></select>
        </label>
        <button class="button button-primary" type="submit">Terapkan</button>
    </form>

    @if($hasFilter)
        <div class="filter-summary">
            <span><strong>{{ count($payload['rows']) }}</strong> baris ditemukan @if(request('q'))untuk “{{ request('q') }}”@endif @if(request('category'))di {{ request('category') }}@endif</span>
            <a href="{{ route('budgets.index', array_filter(['year' => $payload['year'], 'type' => $type])) }}"><x-icon name="x" /> Hapus filter</a>
        </div>
    @endif

    @include('partials.budget-table', ['budgetRows' => $payload['rows'], 'showSource' => true])

    <footer class="panel-foot">
        <span>{{ count($payload['rows']) }} baris · tahun {{ $payload['year'] }}</span>
        <span>Nilai dalam rupiah</span>
    </footer>
</section>

<p class="footnote" data-reveal><x-icon name="info" /> Anggaran adalah rencana. Realisasi, penyerapan, dan sisa anggaran hanya ditampilkan bila datanya tersedia dan sudah diverifikasi.</p>
@endsection
