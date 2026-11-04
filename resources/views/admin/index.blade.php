@extends('layouts.app')
@section('title', 'Kelola Data APBDes')
@section('heading', 'Kelola Data APBDes '.$payload['year'])
@section('content')
@php
    $filters = $payload['filters'];
    $search = $payload['search'];
    $hasFilter = $filters['q'] || $filters['type'] || $filters['status'];
    $actionLabels = ['create' => 'Menambah anggaran', 'update' => 'Memperbarui anggaran', 'delete' => 'Menghapus data', 'import' => 'Mengimpor data', 'seed' => 'Menyiapkan data contoh', 'realization' => 'Mencatat pengeluaran', 'complaint' => 'Menanggapi pengaduan', 'password' => 'Mengganti kata sandi'];
    $actionIcons = ['create' => 'plus', 'update' => 'pencil', 'delete' => 'trash-2', 'import' => 'upload', 'seed' => 'database', 'realization' => 'receipt-text', 'complaint' => 'message-square-text', 'password' => 'key-round'];
@endphp
<div class="page-head" data-reveal>
    <div>
        <nav class="crumbs" aria-label="Lokasi"><span>Perangkat desa</span><span>/</span><span>Kelola data</span></nav>
        <h1>Kelola Data APBDes</h1>
        <p>Tambah, ubah, dan impor data anggaran. Perubahan langsung tampil di halaman publik dan tercatat di riwayat.</p>
    </div>
    <div class="page-actions"><a class="btn btn-secondary" href="#impor"><x-icon name="upload" /> Impor</a><a class="btn btn-secondary" href="{{ route('admin.realizations.index', ['year' => $payload['year']]) }}"><x-icon name="receipt-text" /> Catat realisasi</a><button class="btn btn-primary" type="button" data-budget-open><x-icon name="plus" /> Tambah anggaran</button></div>
</div>

<section class="card" data-reveal style="margin-bottom: 20px">
    <header class="card-head"><div><h2>Data anggaran <span class="badge badge-blue">{{ $payload['summary']['total_count'] }} baris</span></h2><p>Tahun anggaran {{ $payload['year'] }} · sumber: {{ $payload['source'] ?? '–' }}</p></div><a class="btn btn-secondary btn-sm" href="{{ route('export', ['year' => $payload['year']]) }}"><x-icon name="download" /> Ekspor CSV</a></header>
    <form class="filters" method="GET" action="{{ route('admin.index') }}" style="padding-top: 0">
        <input type="hidden" name="year" value="{{ $payload['year'] }}">
        <div class="search"><x-icon name="search" /><label class="sr-only" for="admin-search">Cari</label><input class="input" id="admin-search" type="search" name="q" value="{{ $filters['q'] }}" placeholder="Cari kegiatan, bidang, sumber dana" data-search></div>
        <label class="sr-only" for="admin-type">Jenis</label><select class="select" id="admin-type" name="type"><option value="">Semua jenis</option><option value="income" @selected($filters['type'] === 'income')>Pendapatan</option><option value="expense" @selected($filters['type'] === 'expense')>Belanja</option></select>
        <label class="sr-only" for="admin-status">Status</label><select class="select" id="admin-status" name="status"><option value="">Semua status</option><option value="verified" @selected($filters['status'] === 'verified')>Terverifikasi</option><option value="unverified" @selected($filters['status'] === 'unverified')>Belum diverifikasi</option><option value="unavailable" @selected($filters['status'] === 'unavailable')>Belum tersedia</option></select>
        <button class="btn btn-primary" type="submit">Terapkan</button>
        @if($hasFilter)<a class="btn btn-ghost" href="{{ route('admin.index', ['year' => $payload['year']]) }}">Hapus filter</a>@endif
    </form>
    <div class="table-wrap">
        <table class="table stack">
            <thead><tr><th>Uraian</th><th>Jenis</th><th>Sumber dana</th><th class="text-right">Anggaran</th><th>Realisasi</th><th>Status</th><th><span class="sr-only">Aksi</span></th></tr></thead>
            <tbody>
            @forelse($payload['rows'] as $row)
                <tr style="--row: {{ $loop->index }}">
                    <td class="cell-main"><span class="strong">{{ $search->highlight($row['description']) }}</span><span class="sub">{{ $search->highlight($row['category']) }}@if($row['sub_category']) · {{ $row['sub_category'] }}@endif @if($row['volume'])· {{ $row['volume'] }}@endif</span></td>
                    <td data-label="Jenis"><span class="badge {{ $row['type'] === 'income' ? 'badge-green' : 'badge-blue' }}">{{ $row['type'] === 'income' ? 'Pendapatan' : 'Belanja' }}</span></td>
                    <td data-label="Sumber dana">{{ $row['funding_source'] ?? '–' }}</td>
                    <td class="text-right amount" data-label="Anggaran"><x-money :value="$row['amount']" :decimals="2" /></td>
                    <td data-label="Realisasi">@if($row['type'] === 'expense')<div class="mini-progress"><x-progress :value="$row['realization_percent'] ?? 0" color="blue" :label="'Realisasi '.$row['description']" /><span>{{ number_format($row['realization_percent'] ?? 0, 0, ',', '.') }}%</span></div>@else – @endif</td>
                    <td data-label="Status"><x-status :status="$row['verification_status']" /></td>
                    <td class="cell-actions" style="white-space: nowrap; text-align: right">
                        <button class="icon-btn" type="button" data-budget-edit="{{ json_encode(collect($row)->only(['id', 'year', 'type', 'category', 'sub_category', 'description', 'volume', 'amount', 'funding_source', 'source', 'source_date', 'verification_status'])) }}" data-update-url="{{ route('admin.budgets.update', $row['id']) }}" aria-label="Ubah {{ $row['description'] }}" title="Ubah"><x-icon name="pencil" /></button>
                        <form style="display: inline" method="POST" action="{{ route('admin.budgets.destroy', $row['id']) }}" data-confirm="Data “{{ $row['description'] }}” beserta catatan realisasinya akan dihapus. Catatan penghapusan tetap tersimpan di riwayat.">@csrf @method('DELETE')<button class="icon-btn icon-btn-danger" type="submit" aria-label="Hapus {{ $row['description'] }}" title="Hapus"><x-icon name="trash-2" /></button></form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7"><div class="empty"><x-icon name="search-x" /><strong>Tidak ada data</strong><p>{{ $hasFilter ? 'Coba kata kunci lain atau hapus filter.' : 'Tambahkan anggaran atau impor dari template.' }}</p></div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <footer class="card-foot"><span>{{ count($payload['rows']) }} baris ditampilkan</span><span><x-icon name="lock-keyhole" class="icon" style="display: inline; width: 14px; height: 14px; vertical-align: -2px" /> Hanya perangkat desa yang dapat mengubah data</span></footer>
</section>

<section class="grid grid-7-5">
    <article class="card" id="impor" data-reveal>
        <header class="card-head"><div><h2>Impor dari CSV atau Excel</h2><p>Gunakan template supaya kolomnya sesuai. Maksimal 2 MB.</p></div><a class="btn btn-secondary btn-sm" href="{{ route('admin.import.template') }}"><x-icon name="file-down" /> Template</a></header>
        <form class="card-body" method="POST" action="{{ route('admin.import') }}" enctype="multipart/form-data" style="display: grid; gap: 16px">
            @csrf
            <label class="dropzone" data-dropzone><input id="import-file" type="file" name="file" accept=".csv,.xlsx" required><x-icon name="file-up" /><strong data-dropzone-label>Seret berkas ke sini atau <u>pilih dari komputer</u></strong><p>Semua baris diperiksa dulu. Jika ada satu baris salah, tidak ada yang disimpan.</p></label>
            <div class="form-grid">
                <div class="field"><label for="import-year">Tahun anggaran</label><input class="input" id="import-year" type="number" name="year" min="2000" max="2100" value="{{ old('year', $payload['year']) }}" required></div>
                <div class="field"><label for="import-date">Tanggal dokumen <span class="optional">opsional</span></label><input class="input" id="import-date" type="date" name="source_date" value="{{ old('source_date') }}"></div>
                <div class="field span-2"><label for="import-source">Sumber data <span class="optional">bila kolom sumber kosong</span></label><input class="input" id="import-source" name="source" maxlength="500" value="{{ old('source') }}" placeholder="Contoh: Perdes No. 2 Tahun 2026 tentang APBDes"></div>
            </div>
            <label class="check"><input type="checkbox" name="is_demo" value="1" @checked(old('is_demo'))><span>Tandai sebagai data contoh</span></label>
            <div class="form-actions"><span class="hint">Kolom: year, type, category, sub_category, description, volume, amount, funding_source, source, verification_status, source_date</span><button class="btn btn-primary" type="submit"><x-icon name="upload" /> Periksa & impor</button></div>
        </form>
    </article>
    <article class="card" data-reveal>
        <header class="card-head"><div><h2>Riwayat aktivitas</h2><p>Siapa mengubah apa, dan kapan.</p></div></header>
        <ul class="timeline">
            @forelse(array_slice($payload['activities'], 0, 6) as $activity)
                <li><span class="timeline-dot"><x-icon :name="$actionIcons[$activity['action']] ?? 'circle'" /></span><div><strong>{{ $actionLabels[$activity['action']] ?? $activity['action'] }}</strong><p>{{ $activity['description'] }}</p><span>{{ $activity['user'] }} · {{ \Carbon\Carbon::parse($activity['created_at'])->timezone('Asia/Jakarta')->translatedFormat('j M Y, H.i') }} WIB</span></div></li>
            @empty
                <li><div class="empty" style="grid-column: 1 / -1"><p>Belum ada aktivitas.</p></div></li>
            @endforelse
        </ul>
    </article>
</section>

<dialog id="budget-dialog" class="dialog" aria-labelledby="budget-dialog-title" @if($errors->any() && old('_budget_form')) data-open-on-load @endif>
    <form id="budget-form" method="POST" action="{{ route('admin.budgets.store') }}" data-store-url="{{ route('admin.budgets.store') }}" data-default-source="{{ $payload['source'] }}">
        <header class="dialog-head"><h2 id="budget-dialog-title" data-dialog-title>Tambah anggaran</h2><button class="icon-btn" type="button" data-dialog-close aria-label="Tutup"><x-icon name="x" /></button></header>
        @csrf
        <input type="hidden" name="_budget_form" value="1">
        <input type="hidden" name="_method" value="PATCH" disabled>
        <input type="hidden" name="_editing_id" value="{{ old('_editing_id') }}">
        <div class="dialog-body form-grid">
            @if($errors->any() && old('_budget_form'))<div class="alert span-2" style="margin: 0"><x-icon name="circle-alert" /><div>{{ $errors->first() }}</div></div>@endif
            <div class="field"><label for="budget-year">Tahun anggaran</label><input class="input" id="budget-year" name="year" type="number" min="2000" max="2100" value="{{ old('year', $payload['year']) }}" required></div>
            <div class="field"><label for="budget-type">Jenis</label><select class="select" id="budget-type" name="type" required><option value="expense" @selected(old('type', 'expense') === 'expense')>Belanja</option><option value="income" @selected(old('type') === 'income')>Pendapatan</option></select></div>
            <div class="field"><label for="budget-category">Bidang / jenis pendapatan</label><input class="input" id="budget-category" name="category" list="category-options" maxlength="150" value="{{ old('category') }}" required><datalist id="category-options">@foreach($payload['categories'] as $category)<option value="{{ $category }}">@endforeach</datalist></div>
            <div class="field"><label for="budget-sub">Sub bidang <span class="optional">opsional</span></label><input class="input" id="budget-sub" name="sub_category" maxlength="150" value="{{ old('sub_category') }}" placeholder="Misalnya: Kesehatan"></div>
            <div class="field span-2"><label for="budget-description">Nama kegiatan</label><input class="input" id="budget-description" name="description" maxlength="255" value="{{ old('description') }}" required placeholder="Misalnya: Rehabilitasi saluran irigasi RT 03"></div>
            <div class="field"><label for="budget-volume">Volume <span class="optional">opsional</span></label><input class="input" id="budget-volume" name="volume" maxlength="50" value="{{ old('volume') }}" placeholder="12 bulan, 1 paket, 3 unit"></div>
            <div class="field"><label for="budget-funding">Sumber dana</label><input class="input" id="budget-funding" name="funding_source" list="funding-options" maxlength="50" value="{{ old('funding_source') }}" placeholder="Dana Desa, ADD, BHP"><datalist id="funding-options">@foreach(['Dana Desa', 'ADD', 'BHP', 'PAD', 'Bunga Bank'] as $source)<option value="{{ $source }}">@endforeach</datalist></div>
            <div class="field"><label for="budget-amount">Anggaran (Rp)</label><input class="input" id="budget-amount" name="amount" type="number" min="0" step="0.01" inputmode="decimal" value="{{ old('amount') }}" required data-money-preview><span class="money-preview" data-money-output></span></div>
            <div class="field"><label for="budget-status">Status verifikasi</label><select class="select" id="budget-status" name="verification_status" required><option value="unverified" @selected(old('verification_status', 'unverified') === 'unverified')>Belum diverifikasi</option><option value="verified" @selected(old('verification_status') === 'verified')>Terverifikasi</option><option value="unavailable" @selected(old('verification_status') === 'unavailable')>Belum tersedia</option></select></div>
            <div class="field span-2"><label for="budget-source">Dokumen sumber</label><input class="input" id="budget-source" name="source" maxlength="500" value="{{ old('source', $payload['source']) }}" required></div>
            <div class="field"><label for="budget-source-date">Tanggal dokumen <span class="optional">opsional</span></label><input class="input" id="budget-source-date" name="source_date" type="date" value="{{ old('source_date') }}"></div>
        </div>
        <footer class="dialog-foot"><button class="btn btn-secondary" type="button" data-dialog-close>Batal</button><button class="btn btn-primary" type="submit"><x-icon name="check" /> Simpan</button></footer>
    </form>
</dialog>
<x-confirm-dialog />
@endsection
