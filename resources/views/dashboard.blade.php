@extends('layouts.app')
@section('title', 'Dashboard')
@section('heading', 'Dashboard Monitoring APBDes')
@section('content')
@php
    $summary = $payload['summary'];
    $hasData = $summary['total_count'] > 0;
    $absorption = $summary['absorption'] ?? 0;
    $updatedAt = $payload['last_updated'] ? \Carbon\Carbon::parse($payload['last_updated'])->timezone('Asia/Jakarta')->translatedFormat('j F Y') : null;
    $latestYear = collect($payload['years'])->first(fn ($year) => $year !== $payload['year']);
@endphp

<section class="hero" data-reveal>
    <div class="hero-copy">
        <span class="hero-kicker"><x-icon name="sparkles" /> Selamat datang</span>
        <h2>Dashboard Monitoring Anggaran Desa</h2>
        <p>Desa Sungai Pinang Nibung, Kecamatan Sungai Pinang, Kabupaten Ogan Ilir · Tahun Anggaran {{ $payload['year'] }}</p>
        <div class="hero-meta">
            <span><x-icon name="user-round" /> Kepala Desa: Kamaludin</span>
            @if($payload['source'])<span><x-icon name="file-check" /> Sumber: {{ $payload['source'] }}</span>@endif
            @if($updatedAt)<span><x-icon name="calendar-check" /> Diperbarui {{ $updatedAt }}</span>@endif
        </div>
    </div>
    <x-village-art class="hero-art" />
</section>

@if(! $hasData)
    <div class="notice notice-info" data-reveal><x-icon name="info" /><p>Data APBDes tahun {{ $payload['year'] }} belum dimasukkan. Total pendapatan, belanja, dan realisasi: Belum tersedia.@if($latestYear) <a href="{{ route('dashboard', ['year' => $latestYear]) }}">Lihat tahun {{ $latestYear }}</a>.@endif</p></div>
@elseif($payload['realization_is_demo'])
    <div class="notice notice-warn" data-reveal><x-icon name="flask-conical" /><p><strong>Angka anggaran asli dari baliho APBDes {{ $payload['year'] }}.</strong> Transaksi realisasi masih data contoh untuk mencoba fitur monitoring, bukan data resmi desa.</p></div>
@endif

<section class="grid grid-4" aria-label="Ringkasan anggaran">
    <article class="card kpi kpi-green" data-reveal>
        <span class="kpi-icon"><x-icon name="hand-coins" /></span>
        <div>
            <span class="kpi-label">Total pendapatan</span>
            @if($summary['income_count'] > 0)
                <p class="kpi-value"><x-money :value="$summary['income']" :decimals="2" /></p>
                <x-progress value="100" color="green" label="Pendapatan" />
                <p class="kpi-meta"><span>{{ count($payload['income']) }} sumber pendapatan</span><span>100%</span></p>
            @else
                <p class="kpi-value is-empty">Belum tersedia</p>
            @endif
        </div>
    </article>
    <article class="card kpi kpi-blue" data-reveal>
        <span class="kpi-icon"><x-icon name="shopping-cart" /></span>
        <div>
            <span class="kpi-label">Total belanja</span>
            @if($summary['expense_count'] > 0)
                @php $ofIncome = $summary['income'] > 0 ? $summary['expense'] / $summary['income'] * 100 : 0; @endphp
                <p class="kpi-value"><x-money :value="$summary['expense']" :decimals="2" /></p>
                <x-progress :value="$ofIncome" color="blue" label="Belanja terhadap pendapatan" />
                <p class="kpi-meta"><span>{{ count($payload['expense']) }} bidang, {{ $summary['expense_count'] }} kegiatan</span><span>{{ number_format($ofIncome, 0, ',', '.') }}%</span></p>
            @else
                <p class="kpi-value is-empty">Belum tersedia</p>
            @endif
        </div>
    </article>
    <article class="card kpi kpi-amber" data-reveal>
        <span class="kpi-icon"><x-icon name="scale" /></span>
        <div>
            <span class="kpi-label">Total pembiayaan</span>
            @if($hasData)
                <p class="kpi-value"><x-money :value="$summary['financing']" :decimals="2" /></p>
                <x-progress value="0" color="amber" label="Pembiayaan" />
                <p class="kpi-meta"><span>{{ $summary['balance'] == 0 ? 'Anggaran seimbang' : ($summary['balance'] > 0 ? 'Surplus' : 'Defisit') }}</span><a class="link" href="{{ route('financing', ['year' => $payload['year']]) }}">Detail</a></p>
            @else
                <p class="kpi-value is-empty">Belum tersedia</p>
            @endif
        </div>
    </article>
    <article class="card kpi kpi-purple" data-reveal>
        <span class="kpi-icon"><x-icon name="chart-pie" /></span>
        <div>
            <span class="kpi-label">Realisasi belanja</span>
            @if($summary['realization'] !== null)
                <p class="kpi-value"><x-money :value="$summary['realization']" :decimals="2" /></p>
                <x-progress :value="$absorption" color="purple" label="Penyerapan anggaran" />
                <p class="kpi-meta"><span>@if($summary['absorption'] !== null){{ number_format($summary['absorption'], 1, ',', '.') }}% penyerapan anggaran @else Penyerapan belum dapat dihitung @endif</span></p>
            @else
                <p class="kpi-value is-empty">Belum tersedia</p>
                <p class="kpi-meta"><span>Belum ada pengeluaran yang dicatat</span></p>
            @endif
        </div>
    </article>
</section>

@if($summary['remaining'] !== null)
<section class="card" data-reveal style="margin-bottom: 20px">
    <div class="stat-row cols-4">
        <div class="stat"><span>Rencana belanja</span><strong><x-money :value="$summary['expense']" :decimals="2" /></strong></div>
        <div class="stat"><span>Sudah direalisasikan</span><strong style="color: #7c3aed"><x-money :value="$summary['realization']" :decimals="2" /></strong></div>
        <div class="stat"><span>Sisa anggaran</span><strong style="color: var(--green)"><x-money :value="$summary['remaining']" :decimals="2" /></strong></div>
        <div class="stat"><span>Transaksi tercatat</span><strong>{{ $summary['transactions'] }} pengeluaran</strong></div>
    </div>
</section>
@endif

@if($hasData)
<section class="grid grid-2" aria-label="Komposisi anggaran">
    <article class="card" data-reveal>
        <header class="card-head"><div><h2>Komposisi Pendapatan Desa TA {{ $payload['year'] }}</h2><p>Porsi setiap sumber terhadap total pendapatan.</p></div><span class="badge badge-blue">{{ count($payload['income']) }} sumber</span></header>
        <div class="card-body">
            @if(count($payload['income']))
            <div class="donut-wrap">
                <div class="donut"><canvas id="income-chart" role="img" aria-label="Diagram lingkaran komposisi pendapatan. Rinciannya ada pada daftar di sebelahnya."></canvas><div class="donut-center"><span>Total</span><strong><x-money :value="$summary['income']" :decimals="2" /></strong></div></div>
                <ul class="legend">
                    @foreach($payload['income'] as $item)
                        <li><span class="dot" style="--dot: {{ $item['color'] }}"></span><span>{{ $item['name'] }}<small><x-money :value="$item['amount']" /></small></span><span class="pct">{{ number_format($item['percentage'], 1, ',', '.') }}%</span></li>
                    @endforeach
                </ul>
            </div>
            @else
                <div class="empty"><x-icon name="chart-pie" /><p>Data pendapatan belum tersedia.</p></div>
            @endif
        </div>
    </article>
    <article class="card" data-reveal>
        <header class="card-head"><div><h2>Komposisi Belanja Desa TA {{ $payload['year'] }}</h2><p>Rencana belanja per bidang. Klik batang untuk melihat kegiatannya.</p></div><a class="icon-btn" href="{{ route('budgets.index', ['type' => 'expense', 'year' => $payload['year']]) }}" aria-label="Lihat semua belanja"><x-icon name="arrow-up-right" /></a></header>
        <div class="card-body">
            @if(count($payload['expense']))
                <div class="chart"><canvas id="expense-chart" role="img" aria-label="Grafik batang belanja per bidang. Nominalnya tercantum pada tabel Rincian Belanja."></canvas></div>
            @else
                <div class="empty"><x-icon name="chart-column" /><p>Rincian belanja per bidang belum tersedia.</p></div>
            @endif
        </div>
    </article>
</section>

<section class="grid grid-2" aria-label="Rincian per sumber dan bidang">
    @foreach([
        ['title' => 'Rincian Pendapatan Desa', 'column' => 'Jenis pendapatan', 'items' => $payload['income'], 'total' => $summary['income'], 'type' => 'income', 'totalLabel' => 'Total pendapatan'],
        ['title' => 'Rincian Belanja Desa', 'column' => 'Bidang belanja', 'items' => $payload['expense'], 'total' => $summary['expense'], 'type' => 'expense', 'totalLabel' => 'Total belanja'],
    ] as $group)
    <article class="card" data-reveal>
        <header class="card-head"><div><h2>{{ $group['title'] }} TA {{ $payload['year'] }}</h2><p>Nilai dan persentase terhadap total.</p></div><a class="btn btn-primary btn-sm" href="{{ route('budgets.index', ['type' => $group['type'], 'year' => $payload['year']]) }}">Lihat detail</a></header>
        <div class="table-wrap">
            <table class="table stack">
                <thead><tr><th class="no">No</th><th>{{ $group['column'] }}</th><th class="text-right">Anggaran (Rp)</th><th class="text-right">Persentase</th></tr></thead>
                <tbody>
                @foreach($group['items'] as $item)
                    <tr style="--row: {{ $loop->index }}">
                        <td class="no">{{ $loop->iteration }}</td>
                        <td class="cell-main"><a class="strong" href="{{ route('budgets.index', ['type' => $group['type'], 'category' => $item['name'], 'year' => $payload['year']]) }}"><span class="dot-inline" style="--dot: {{ $item['color'] }}"></span>{{ $item['name'] }}</a></td>
                        <td class="text-right amount" data-label="Anggaran"><x-money :value="$item['amount']" :decimals="2" /></td>
                        <td class="text-right" data-label="Persentase">{{ number_format($item['percentage'], 1, ',', '.') }}%</td>
                    </tr>
                @endforeach
                </tbody>
                <tfoot><tr><td class="no"></td><th>{{ $group['totalLabel'] }}</th><td class="text-right"><x-money :value="$group['total']" :decimals="2" /></td><td class="text-right">100%</td></tr></tfoot>
            </table>
        </div>
    </article>
    @endforeach
</section>

<section class="grid grid-7-5" aria-label="Monitoring realisasi">
    <article class="card" data-reveal>
        <header class="card-head"><div><h2>Rencana vs Realisasi per Bidang</h2><p>Seberapa jauh anggaran setiap bidang sudah dibelanjakan.</p></div><a class="link" href="{{ route('realizations.index', ['year' => $payload['year']]) }}">Monitoring <x-icon name="arrow-right" /></a></header>
        <div class="card-body">
            @foreach($payload['expense'] as $item)
                @php $percent = $item['realization_percent'] ?? 0; @endphp
                <a class="progress-item" href="{{ route('budgets.index', ['type' => 'expense', 'category' => $item['name'], 'year' => $payload['year']]) }}">
                    <div class="progress-item-head"><strong>{{ $item['name'] }}</strong><span class="num">{{ number_format($percent, 1, ',', '.') }}%</span></div>
                    <x-progress :value="$percent" :color="$percent >= 100 ? 'green' : ($percent >= 60 ? 'blue' : 'amber')" :label="'Realisasi '.$item['name']" />
                    <div class="progress-item-foot"><span>Realisasi <x-money :value="$item['realization']" /></span><span>Anggaran <x-money :value="$item['amount']" /></span></div>
                </a>
            @endforeach
        </div>
    </article>
    <article class="card" data-reveal>
        <header class="card-head"><div><h2>Pengeluaran Terbaru</h2><p>Transaksi yang baru dicatat perangkat desa.</p></div></header>
        @forelse($payload['latest_realizations'] as $item)
            <div class="list-row">
                <span class="list-icon"><x-icon name="receipt-text" /></span>
                <div><p class="list-title">{{ $item['activity'] }}</p><p class="list-meta">{{ \Carbon\Carbon::parse($item['date'])->translatedFormat('j M Y') }} · {{ $item['description'] }}@if($item['is_demo']) · <span class="badge badge-demo">contoh</span>@endif</p></div>
                <span class="amount num"><x-money :value="$item['amount']" /></span>
            </div>
        @empty
            <div class="empty"><x-icon name="receipt-text" /><p>Belum ada pengeluaran yang dicatat.</p></div>
        @endforelse
        <footer class="card-foot"><span>{{ count($payload['latest_realizations']) }} transaksi terakhir</span><a class="link" href="{{ route('realizations.index', ['year' => $payload['year']]) }}">Semua transaksi <x-icon name="arrow-right" /></a></footer>
    </article>
</section>
@endif
@endsection

@push('scripts')<script>window.dashboardData = {{ Illuminate\Support\Js::from([
    'income' => $payload['income'],
    'expense' => array_map(fn (array $item): array => $item + ['url' => route('budgets.index', ['type' => 'expense', 'category' => $item['name'], 'year' => $payload['year']])], $payload['expense']),
]) }};</script>@endpush
