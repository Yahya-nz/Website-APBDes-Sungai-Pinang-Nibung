@extends('layouts.app')
@section('title', 'Ringkasan')
@section('content')
@php
    use App\Support\Money;

    $summary = $payload['summary'];
    $income = $payload['income'];
    $expenses = $payload['expense'];
    $rows = array_slice($payload['rows'], 0, 6);
    $total = $summary['total_count'];
    $hasIncome = $summary['income_count'] > 0;
    $hasExpense = $summary['expense_count'] > 0;
    $percentVerified = $total ? round($summary['verified_count'] / $total * 100) : 0;
    $largest = max($summary['income'], $summary['expense'], 1);
    $balance = $summary['balance'];
    $latestYearWithData = collect($payload['years'])->first(fn ($year) => $year !== $payload['year']);
    $updatedAt = $payload['last_updated'] ? \Carbon\Carbon::parse($payload['last_updated'])->timezone('Asia/Jakarta')->translatedFormat('j F Y') : null;
@endphp

<header class="page-header" data-reveal>
    <div>
        <p class="page-kicker">Desa Sungai Pinang Nibung</p>
        <h1>Ringkasan APBDes {{ $payload['year'] }}</h1>
        <p class="page-lead">Rencana pendapatan dan belanja desa dalam satu halaman.@if($updatedAt) Data terakhir diperbarui {{ $updatedAt }}.@endif</p>
    </div>
    <div class="page-actions">
        <x-year-select :years="$payload['years']" :selected="$payload['year']" id="dashboard-year" />
        <a class="button button-outline" href="{{ route('export', ['year' => $payload['year']]) }}"><x-icon name="download" /> Unduh CSV</a>
    </div>
</header>

@if($payload['is_demo'])<x-demo-notice data-reveal />@endif

@if($total === 0)
    <section class="empty-year" data-reveal>
        <x-icon name="calendar-x-2" />
        <h2>Belum ada data untuk tahun {{ $payload['year'] }}</h2>
        <p>Pemerintah desa belum memasukkan rincian APBDes tahun ini. Total pendapatan dan belanja: Belum tersedia.</p>
        @if($latestYearWithData)<a class="button button-primary" href="{{ route('dashboard', ['year' => $latestYearWithData]) }}">Lihat tahun {{ $latestYearWithData }}</a>@endif
    </section>
@else
<section class="balance" aria-labelledby="balance-title" data-reveal>
    <div class="balance-head">
        <h2 id="balance-title">Pendapatan dan belanja</h2>
        @if($hasIncome && $hasExpense)
            <span class="balance-tag {{ $balance > 0 ? 'is-surplus' : ($balance < 0 ? 'is-deficit' : 'is-even') }}">
                {{ $balance > 0 ? 'Surplus' : ($balance < 0 ? 'Defisit' : 'Seimbang') }}@if($balance != 0) <x-money :value="abs($balance)" />@endif
            </span>
        @endif
    </div>
    <div class="balance-bars">
        <div class="balance-row">
            <span class="balance-label">Pendapatan</span>
            <span class="balance-track"><span class="balance-fill fill-income" style="--fill: {{ $hasIncome ? round($summary['income'] / $largest * 100, 2) : 0 }}%"></span></span>
            <span class="balance-amount">@if($hasIncome)<x-money :value="$summary['income']" />@else Belum tersedia @endif</span>
        </div>
        <div class="balance-row">
            <span class="balance-label">Belanja</span>
            <span class="balance-track"><span class="balance-fill fill-expense" style="--fill: {{ $hasExpense ? round($summary['expense'] / $largest * 100, 2) : 0 }}%"></span></span>
            <span class="balance-amount">@if($hasExpense)<x-money :value="$summary['expense']" />@else Belum tersedia @endif</span>
        </div>
    </div>
    <p class="balance-note">Angka di atas adalah <em>rencana</em> anggaran. Realisasi baru ditampilkan setelah seluruh belanja tahun ini terverifikasi.</p>
</section>
@endif

<section class="stats" aria-label="Indikator utama">
    <article class="stat" data-reveal>
        <span class="stat-label">Total pendapatan</span>
        @if($hasIncome)
            @php $short = Money::short($summary['income']); @endphp
            <p class="stat-value"><span class="stat-prefix">Rp</span><span data-count="{{ $short['value'] }}">{{ $short['value'] }}</span> <span class="stat-unit">{{ $short['unit'] }}</span></p>
            <p class="stat-meta">dari {{ count($income) }} sumber pendapatan</p>
        @else
            <p class="stat-value is-empty">Belum tersedia</p>
            <p class="stat-meta">Belum ada data pendapatan</p>
        @endif
    </article>
    <article class="stat" data-reveal>
        <span class="stat-label">Total belanja</span>
        @if($hasExpense)
            @php $short = Money::short($summary['expense']); @endphp
            <p class="stat-value"><span class="stat-prefix">Rp</span><span data-count="{{ $short['value'] }}">{{ $short['value'] }}</span> <span class="stat-unit">{{ $short['unit'] }}</span></p>
            <p class="stat-meta">untuk {{ count($expenses) }} bidang kegiatan</p>
        @else
            <p class="stat-value is-empty">Belum tersedia</p>
            <p class="stat-meta">Belum ada data belanja</p>
        @endif
    </article>
    <article class="stat" data-reveal>
        <span class="stat-label">Realisasi belanja</span>
        @if($summary['realization'] !== null)
            @php $short = Money::short($summary['realization']); @endphp
            <p class="stat-value"><span class="stat-prefix">Rp</span><span data-count="{{ $short['value'] }}">{{ $short['value'] }}</span> <span class="stat-unit">{{ $short['unit'] }}</span></p>
            <p class="stat-meta">@if($summary['absorption'] !== null){{ number_format($summary['absorption'], 1, ',', '.') }}% penyerapan anggaran @else Penyerapan belum dapat dihitung @endif</p>
        @else
            <p class="stat-value is-empty">Belum tersedia</p>
            <p class="stat-meta">Menunggu data terverifikasi</p>
        @endif
    </article>
    <article class="stat" data-reveal>
        <span class="stat-label">Data terverifikasi</span>
        <p class="stat-value"><span data-count="{{ $summary['verified_count'] }}">{{ $summary['verified_count'] }}</span><span class="stat-of">/{{ $total }}</span> <span class="stat-unit">baris</span></p>
        <div class="stat-progress" role="progressbar" aria-valuenow="{{ $percentVerified }}" aria-valuemin="0" aria-valuemax="100" aria-label="Persentase data terverifikasi"><span style="--fill: {{ $percentVerified }}%"></span></div>
        <p class="stat-meta">{{ $percentVerified }}% sudah dicek ke dokumen sumber · <a href="{{ route('quality', ['year' => $payload['year']]) }}">detail</a></p>
    </article>
</section>

@if($summary['remaining'] !== null)
<div class="remaining" data-reveal>
    <x-icon name="wallet" />
    <span>Sisa anggaran</span>
    <strong><x-money :value="$summary['remaining']" :decimals="2" /></strong>
    <p>Selisih rencana belanja dengan realisasi yang sudah terverifikasi.</p>
</div>
@endif

@if($total > 0)
<div class="two-col">
    <section class="panel" aria-labelledby="income-title" data-reveal>
        <header class="panel-head">
            <div><h2 id="income-title">Sumber pendapatan</h2><p>Porsi setiap sumber terhadap total pendapatan.</p></div>
        </header>
        @if(count($income))
        <div class="income-layout">
            <div class="donut">
                <canvas id="income-chart" role="img" aria-label="Diagram lingkaran sumber pendapatan. Rinciannya ada pada tabel di sebelahnya."></canvas>
                <div class="donut-center"><strong>{{ count($income) }}</strong><span>sumber</span></div>
            </div>
            <table class="legend-table">
                <caption class="sr-only">Rincian sumber pendapatan</caption>
                <tbody>
                @foreach($income as $item)
                    <tr>
                        <th scope="row"><span class="swatch" style="--swatch: {{ $item['color'] }}"></span>{{ $item['name'] }}</th>
                        <td class="num"><x-money :value="$item['amount']" /></td>
                        <td class="num legend-share">{{ number_format($item['percentage'], 1, ',', '.') }}%</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @else
            <p class="panel-empty">Data pendapatan tahun ini belum tersedia.</p>
        @endif
    </section>

    <section class="panel" aria-labelledby="expense-title" data-reveal>
        <header class="panel-head">
            <div><h2 id="expense-title">Belanja per bidang</h2><p>Klik bidang untuk melihat kegiatan di dalamnya.</p></div>
        </header>
        @forelse($expenses as $index => $item)
            @if($loop->first)<ol class="bars">@endif
            <li>
                <a class="bar" href="{{ route('budgets.index', ['type' => 'expense', 'category' => $item['name'], 'year' => $payload['year']]) }}" style="--fill: {{ $item['percentage'] }}%; --delay: {{ $index * 90 }}ms">
                    <span class="bar-name">{{ $item['name'] }}</span>
                    <span class="bar-value"><x-money :value="$item['amount']" /> <small>{{ number_format($item['percentage'], 0, ',', '.') }}%</small></span>
                    <span class="bar-track" aria-hidden="true"><span></span></span>
                </a>
            </li>
            @if($loop->last)</ol>@endif
        @empty
            <p class="panel-empty">Data belanja tahun ini belum tersedia.</p>
        @endforelse
    </section>
</div>

<section class="panel" aria-labelledby="latest-title" data-reveal>
    <header class="panel-head">
        <div><h2 id="latest-title">Rincian kegiatan</h2><p>{{ count($rows) }} dari {{ count($payload['rows']) }} baris anggaran tahun {{ $payload['year'] }}.</p></div>
        <a href="{{ route('budgets.index', ['year' => $payload['year']]) }}" class="button button-outline button-small">Lihat semua <x-icon name="arrow-right" /></a>
    </header>
    @include('partials.budget-table', ['budgetRows' => $rows, 'showSource' => false])
</section>
@endif
@endsection

@push('scripts')<script>window.dashboardData = {{ Illuminate\Support\Js::from(['income' => $payload['income']]) }};</script>@endpush
