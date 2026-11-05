@extends('layouts.app')
@php
    $type = in_array(request('type'), ['income', 'expense'], true) ? request('type') : null;
    $title = ['income' => 'Pendapatan Desa', 'expense' => 'Belanja Desa'][$type] ?? 'Rincian Kegiatan';
    $summary = $payload['summary'];
    $search = $payload['search'];
    $filters = $payload['filters'];
    $hasFilter = filled($filters['q']) || filled($filters['category']) || filled($filters['source']) || filled($filters['status']);
    $rows = collect($payload['rows']);
    $groups = $search->isEmpty() ? $rows->groupBy('category') : collect(['Hasil pencarian' => $rows]);
    $tabs = [['Semua', null, $summary['total_count']], ['Pendapatan', 'income', $summary['income_count']], ['Belanja', 'expense', $summary['expense_count']]];
    $columns = $type === 'income' ? 4 : 6;
@endphp
@section('title', $title)
@section('heading', $title.' TA '.$payload['year'])
@section('content')
<div class="page-head" data-reveal>
    <div>
        <nav class="crumbs" aria-label="Lokasi"><a href="{{ route('dashboard', ['year' => $payload['year']]) }}">Dashboard</a><span>/</span><span>APBDes {{ $payload['year'] }}</span></nav>
        <h1>{{ $title }}</h1>
        <p>@if($type === 'income')Sumber penerimaan desa sesuai APBDes.@elseif($type === 'expense')Kegiatan belanja per bidang, lengkap dengan volume, sumber dana, dan realisasinya.@else Seluruh baris pendapatan dan belanja beserta sumber dan status pemeriksaannya.@endif</p>
    </div>
    <div class="page-actions"><a class="btn btn-secondary" href="{{ route('export', request()->query()) }}"><x-icon name="download" /> Unduh CSV</a><a class="btn btn-primary" href="{{ route('reports.index', ['year' => $payload['year']]) }}"><x-icon name="file-text" /> Laporan</a></div>
</div>

<section class="card" data-reveal style="margin-bottom: 20px">
    <div class="stat-row cols-4">
        <div class="stat"><span>Rencana pendapatan</span><strong>@if($summary['income_count'] > 0)<x-money :value="$summary['income']" :decimals="2" />@else Belum tersedia @endif</strong></div>
        <div class="stat"><span>Rencana belanja</span><strong>@if($summary['expense_count'] > 0)<x-money :value="$summary['expense']" :decimals="2" />@else Belum tersedia @endif</strong></div>
        <div class="stat"><span>Realisasi belanja</span><strong>@if($summary['realization'] !== null)<x-money :value="$summary['realization']" :decimals="2" />@else Belum tersedia @endif</strong></div>
        <div class="stat"><span>Jumlah kegiatan</span><strong>{{ $summary['expense_count'] }} kegiatan</strong></div>
    </div>
</section>

<section class="card" data-reveal>
    <nav class="tabs" aria-label="Jenis anggaran">
        @foreach($tabs as [$label, $tabType, $count])
            <a class="tab {{ $type === $tabType ? 'is-active' : '' }}" href="{{ route('budgets.index', array_filter(['year' => $payload['year'], 'type' => $tabType])) }}" @if($type === $tabType) aria-current="page" @endif>{{ $label }} <span>{{ $count }}</span></a>
        @endforeach
    </nav>
    <form class="filters" method="GET" action="{{ route('budgets.index') }}">
        <input type="hidden" name="year" value="{{ $payload['year'] }}">
        @if($type)<input type="hidden" name="type" value="{{ $type }}">@endif
        <div class="search"><x-icon name="search" /><label class="sr-only" for="budget-search">Cari kegiatan, bidang, atau sumber dana</label><input class="input" id="budget-search" type="search" name="q" value="{{ $filters['q'] }}" placeholder="Cari kegiatan, bidang, atau sumber dana (tekan /)" data-search></div>
        <label class="sr-only" for="filter-category">Bidang</label>
        <select class="select" id="filter-category" name="category"><option value="">Semua bidang/sumber</option>@foreach($payload['categories'] as $category)<option @selected($filters['category'] === $category)>{{ $category }}</option>@endforeach</select>
        @if($type !== 'income')
            <label class="sr-only" for="filter-source">Sumber dana</label>
            <select class="select" id="filter-source" name="source"><option value="">Semua sumber dana</option>@foreach($payload['funding_options'] as $source)<option @selected($filters['source'] === $source)>{{ $source }}</option>@endforeach</select>
        @endif
        <label class="sr-only" for="filter-status">Status</label>
        <select class="select" id="filter-status" name="status"><option value="">Semua status</option><option value="verified" @selected($filters['status'] === 'verified')>Terverifikasi</option><option value="unverified" @selected($filters['status'] === 'unverified')>Belum diverifikasi</option><option value="unavailable" @selected($filters['status'] === 'unavailable')>Belum tersedia</option></select>
        <button class="btn btn-primary" type="submit"><x-icon name="list-filter" /> Terapkan</button>
    </form>
    @if($hasFilter)
        <div class="filter-summary"><span><strong>{{ $rows->count() }}</strong> baris ditemukan @if($filters['q'])untuk “{{ $filters['q'] }}”@endif @if(! $search->isEmpty() && $rows->count() > 1)· diurutkan dari yang paling cocok @endif</span><a href="{{ route('budgets.index', array_filter(['year' => $payload['year'], 'type' => $type])) }}"><x-icon name="x" /> Hapus filter</a></div>
    @endif

    <div class="table-wrap">
        <table class="table stack">
            <thead>
                <tr>
                    <th>{{ $type === 'income' ? 'Sumber pendapatan' : 'Kegiatan / komponen' }}</th>
                    @if($type !== 'income')<th>Volume</th><th>Sumber dana</th>@endif
                    <th class="text-right">Anggaran (Rp)</th>
                    @if($type !== 'income')<th>Realisasi</th>@else<th class="text-right">Persentase</th>@endif
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            @forelse($groups as $category => $items)
                @if($groups->count() > 1 || ! $search->isEmpty() || ! $type)
                    <tr class="table-group"><td colspan="{{ $columns }}">{{ $category }} · <x-money :value="\App\Support\Money::sum($items->pluck('amount'))" :decimals="2" /></td></tr>
                @endif
                @foreach($items->groupBy(fn ($row) => $row['sub_category'] ?? '') as $subCategory => $subItems)
                    @if($subCategory !== '' && $search->isEmpty())
                        <tr class="table-subgroup"><td colspan="{{ $columns }}">Sub bidang {{ $subCategory }} · <x-money :value="\App\Support\Money::sum($subItems->pluck('amount'))" :decimals="2" /></td></tr>
                    @endif
                    @foreach($subItems as $row)
                        <tr style="--row: {{ $loop->index }}">
                            <td class="cell-main"><span class="strong">{{ $search->highlight($row['description']) }}</span><span class="sub">{{ $search->highlight($row['type'] === 'income' ? 'Pendapatan' : $row['category']) }}</span></td>
                            @if($type !== 'income')
                                <td data-label="Volume">{{ $row['volume'] ?? '–' }}</td>
                                <td data-label="Sumber dana">@if($row['funding_source'])<span class="badge badge-blue">{{ $search->highlight($row['funding_source']) }}</span>@else – @endif</td>
                            @endif
                            <td class="text-right amount" data-label="Anggaran"><x-money :value="$row['amount']" :decimals="2" /></td>
                            @if($type !== 'income')
                                <td data-label="Realisasi">@if($row['type'] === 'expense')<x-realization-cell :row="$row" />@else – @endif</td>
                            @else
                                <td class="text-right" data-label="Persentase">{{ $summary['income'] > 0 ? number_format($row['amount'] / $summary['income'] * 100, 1, ',', '.') : 0 }}%</td>
                            @endif
                            <td data-label="Status"><x-status :status="$row['verification_status']" /></td>
                        </tr>
                    @endforeach
                @endforeach
            @empty
                <tr><td colspan="{{ $columns }}"><div class="empty"><x-icon name="search-x" /><strong>@if(! $search->isEmpty())Tidak ada hasil untuk “{{ implode(' ', $search->terms()) }}”@else Tidak ada data @endif</strong><p>@if(! $search->isEmpty())Semua kata harus muncul di kegiatan, bidang, atau sumber dana. Coba kata yang lebih umum, misalnya “jalan” atau “posyandu”.@elseif($summary['total_count'] === 0)Data APBDes tahun {{ $payload['year'] }} belum tersedia.@else Coba ubah atau hapus filter yang dipakai.@endif</p></div></td></tr>
            @endforelse
            </tbody>
            @if($rows->isNotEmpty() && $search->isEmpty())
                <tfoot><tr><th>Total {{ $hasFilter ? 'hasil filter' : strtolower($title) }}</th>@if($type !== 'income')<td></td><td></td>@endif<td class="text-right"><x-money :value="\App\Support\Money::sum($rows->pluck('amount'))" :decimals="2" /></td><td></td><td></td></tr></tfoot>
            @endif
        </table>
    </div>
    <footer class="card-foot"><span>{{ $rows->count() }} baris · tahun anggaran {{ $payload['year'] }}</span><span>Nilai dalam rupiah · sumber: {{ $payload['source'] ?? '–' }}</span></footer>
</section>
@endsection
