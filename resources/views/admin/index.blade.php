@extends('layouts.app')
@section('title', 'Dashboard Pengelola')
@section('heading', 'Dashboard Pengelola')
@section('content')
@php
    $year = $payload['year'];
    $summary = $payload['summary'];
    $verifiedPercent = $summary['total_count'] > 0 ? $summary['verified_count'] / $summary['total_count'] * 100 : 0;
    $pending = $summary['unverified_count'] + $summary['unavailable_count'];
    $passedChecks = collect($checks)->where('passed', true)->count();
    $complaintTotal = (int) $complaintCounts->sum();
    $resolvedPercent = $complaintTotal > 0 ? (int) ($complaintCounts['resolved'] ?? 0) / $complaintTotal * 100 : 0;
    $statusClass = ['new' => 'badge-red', 'in_progress' => 'badge-amber', 'resolved' => 'badge-green'];
@endphp
<div class="page-head" data-reveal>
    <div>
        <nav class="crumbs" aria-label="Lokasi"><span>Perangkat desa</span><span>/</span><span>Dashboard pengelola</span></nav>
        <h1>Halo, {{ auth()->user()->name }}</h1>
        <p>Ringkasan pengelolaan data APBDes {{ $year }} dan hal yang perlu ditindaklanjuti.</p>
    </div>
    <div class="page-actions">
        <a class="btn btn-secondary" href="{{ route('dashboard', ['year' => $year]) }}"><x-icon name="eye" /> Lihat halaman publik</a>
        <a class="btn btn-primary" href="{{ route('admin.budgets.index', ['year' => $year]) }}"><x-icon name="database" /> Kelola data APBDes</a>
    </div>
</div>

<x-uncounted-note :summary="$summary" data-reveal />

<section class="grid grid-4">
    <a class="card kpi kpi-blue" href="{{ route('admin.budgets.index', ['year' => $year]) }}#data" data-reveal>
        <span class="kpi-icon"><x-icon name="database" /></span>
        <div><span class="kpi-label">Data anggaran {{ $year }}</span><p class="kpi-value">{{ $summary['total_count'] }} baris</p><x-progress :value="$summary['total_count'] > 0 ? 100 : 0" color="blue" label="Data anggaran" /><p class="kpi-meta"><span>{{ $summary['income_count'] }} pendapatan · {{ $summary['expense_count'] }} kegiatan belanja</span></p></div>
    </a>
    <a class="card kpi kpi-green" href="{{ route('admin.budgets.index', ['year' => $year, 'status' => $summary['unverified_count'] > 0 ? 'unverified' : 'verified']) }}#data" data-reveal>
        <span class="kpi-icon"><x-icon name="shield-check" /></span>
        <div><span class="kpi-label">Terverifikasi</span><p class="kpi-value">{{ $summary['verified_count'] }} dari {{ $summary['total_count'] }}</p><x-progress :value="$verifiedPercent" color="green" label="Baris terverifikasi" /><p class="kpi-meta"><span>{{ $pending > 0 ? $pending.' baris perlu dicek' : 'Semua baris sudah dicek' }}</span><span>{{ number_format($verifiedPercent, 0, ',', '.') }}%</span></p></div>
    </a>
    <a class="card kpi kpi-amber" href="{{ route('admin.complaints.index', ($complaintCounts['new'] ?? 0) > 0 ? ['status' => 'new'] : []) }}" data-reveal>
        <span class="kpi-icon"><x-icon name="message-square-text" /></span>
        <div><span class="kpi-label">Pengaduan baru</span><p class="kpi-value">{{ (int) ($complaintCounts['new'] ?? 0) }} pesan</p><x-progress :value="$resolvedPercent" color="amber" label="Pengaduan selesai" /><p class="kpi-meta"><span>{{ (int) ($complaintCounts['in_progress'] ?? 0) }} diproses · {{ (int) ($complaintCounts['resolved'] ?? 0) }} selesai</span></p></div>
    </a>
    <a class="card kpi kpi-purple" href="{{ route('admin.realizations.index', ['year' => $year]) }}" data-reveal>
        <span class="kpi-icon"><x-icon name="receipt-text" /></span>
        <div><span class="kpi-label">Realisasi belanja</span>@if($summary['realization'] !== null)<p class="kpi-value"><x-money :value="$summary['realization']" /></p>@else<p class="kpi-value is-empty">Belum ada</p>@endif<x-progress :value="$summary['absorption'] ?? 0" color="purple" label="Penyerapan anggaran" /><p class="kpi-meta"><span>{{ $summary['transactions'] }} transaksi terhitung</span><span>{{ number_format($summary['absorption'] ?? 0, 1, ',', '.') }}%</span></p></div>
    </a>
</section>

<section class="grid grid-7-5">
    <article class="card" data-reveal>
        <header class="card-head"><div><h2>Perlu ditindaklanjuti</h2><p>Disusun otomatis dari pengaduan, realisasi, dan pemeriksaan data.</p></div>@if($tasks)<span class="badge badge-amber">{{ count($tasks) }} hal</span>@else<span class="badge badge-green">Semua beres</span>@endif</header>
        @forelse($tasks as $task)
            <div class="list-row task">
                <span class="list-icon tone-{{ $task['tone'] }}"><x-icon :name="$task['icon']" /></span>
                <div><p class="list-title">{{ $task['title'] }}</p><p class="list-meta">{{ $task['detail'] }}</p></div>
                <a class="btn btn-secondary btn-sm" href="{{ $task['url'] }}">{{ $task['action'] }} <x-icon name="arrow-right" /></a>
            </div>
        @empty
            <div class="empty"><x-icon name="circle-check" /><strong>Tidak ada yang perlu ditindaklanjuti</strong><p>Data sudah terverifikasi dan semua pengaduan sudah ditanggapi.</p></div>
        @endforelse
        <footer class="card-foot"><span>{{ $passedChecks }} dari {{ count($checks) }} pemeriksaan otomatis lolos</span><a class="link" href="{{ route('admin.budgets.index', ['year' => $year]) }}#pemeriksaan">Lihat pemeriksaan <x-icon name="arrow-right" /></a></footer>
    </article>

    <article class="card" data-reveal>
        <header class="card-head"><div><h2>Pengaduan belum selesai</h2><p>Pesan warga berstatus baru atau diproses.</p></div></header>
        @forelse($latestComplaints as $complaint)
            <a class="list-row" href="{{ route('admin.complaints.index', ['status' => $complaint->status]) }}">
                <span class="list-icon tone-amber"><x-icon name="message-square-text" /></span>
                <div><p class="list-title">{{ \App\Models\Complaint::TOPICS[$complaint->topic] ?? 'Pesan' }} · {{ $complaint->ticket }}</p><p class="list-meta">{{ \Illuminate\Support\Str::limit($complaint->message, 90) }}</p></div>
                <span class="badge status {{ $statusClass[$complaint->status] ?? 'badge-gray' }}">{{ \App\Models\Complaint::STATUSES[$complaint->status] ?? $complaint->status }}</span>
            </a>
        @empty
            <div class="empty"><x-icon name="inbox" /><p>Semua pengaduan sudah selesai ditanggapi.</p></div>
        @endforelse
        <footer class="card-foot"><span>{{ $complaintTotal }} pesan tercatat</span><a class="link" href="{{ route('admin.complaints.index') }}">Buka kotak masuk <x-icon name="arrow-right" /></a></footer>
    </article>
</section>

<section class="grid grid-7-5">
    <article class="card" data-reveal>
        <header class="card-head"><div><h2>Riwayat aktivitas</h2><p>Siapa mengubah apa, dan kapan.</p></div></header>
        <ul class="timeline">
            @forelse(array_slice($payload['activities'], 0, 6) as $activity)
                <li><span class="timeline-dot"><x-icon :name="\App\Models\ActivityLog::ACTIONS[$activity['action']][1] ?? 'circle'" /></span><div><strong>{{ \App\Models\ActivityLog::ACTIONS[$activity['action']][0] ?? $activity['action'] }}</strong><p>{{ $activity['description'] }}</p><span>{{ $activity['user'] }} · {{ \Carbon\Carbon::parse($activity['created_at'])->timezone('Asia/Jakarta')->translatedFormat('j M Y, H.i') }} WIB</span></div></li>
            @empty
                <li><div class="empty" style="grid-column: 1 / -1"><p>Belum ada aktivitas.</p></div></li>
            @endforelse
        </ul>
        <footer class="card-foot"><span>Semua perubahan tercatat otomatis.</span><a class="link" href="{{ route('admin.activities.index') }}">Lihat semua riwayat <x-icon name="arrow-right" /></a></footer>
    </article>

    <article class="card" data-reveal>
        <header class="card-head"><div><h2>Pintasan</h2><p>Pekerjaan pengelola yang paling sering dilakukan.</p></div></header>
        <nav class="shortcuts" aria-label="Pintasan pengelola">
            <a href="{{ route('admin.budgets.index', ['year' => $year]) }}#impor"><x-icon name="upload" /><strong>Impor data</strong><span>CSV atau Excel dari template</span></a>
            <a href="{{ route('admin.budgets.index', ['year' => $year]) }}#pemeriksaan"><x-icon name="shield-check" /><strong>Verifikasi data</strong><span>Tetapkan status beberapa baris sekaligus</span></a>
            <a href="{{ route('admin.realizations.index', ['year' => $year]) }}"><x-icon name="receipt-text" /><strong>Catat realisasi</strong><span>Pengeluaran langsung tampil ke warga</span></a>
            <a href="{{ route('reports.index', ['year' => $year]) }}"><x-icon name="file-text" /><strong>Unduh laporan</strong><span>PDF atau Excel per bulan dan semester</span></a>
            <a href="{{ route('admin.users.index') }}"><x-icon name="users-round" /><strong>Pengguna</strong><span>{{ $officials }} akun pengelola aktif</span></a>
            <a href="{{ route('admin.settings') }}"><x-icon name="key-round" /><strong>Ganti kata sandi</strong><span>Amankan akun Anda</span></a>
        </nav>
    </article>
</section>
<x-confirm-dialog />
@endsection
