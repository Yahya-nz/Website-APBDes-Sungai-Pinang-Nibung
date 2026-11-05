@extends('layouts.app')
@section('title', 'Pengelolaan Data APBDes')
@section('heading', 'Pengelolaan Data APBDes '.$payload['year'])
@section('content')
@php
    $year = $payload['year'];
    $filters = $payload['filters'];
    $search = $payload['search'];
    $summary = $payload['summary'];
    $hasFilter = $filters['q'] || $filters['type'] || $filters['status'] || $filters['dataset'];
    $statusCounts = ['verified' => $summary['verified_count'], 'unverified' => $summary['unverified_count'], 'unavailable' => $summary['unavailable_count']];
    $statusTones = ['verified' => ['green', 'shield-check'], 'unverified' => ['amber', 'scan-search'], 'unavailable' => ['red', 'file-question']];
    $passedChecks = collect($checks)->where('passed', true)->count();
    $otherForm = old('_budget_form') || old('_dataset_form');
    $importOld = fn (string $key, $default = null) => $otherForm ? $default : old($key, $default);
    $selectedIds = array_map('intval', (array) old('ids', []));
@endphp
<div class="page-head" data-reveal>
    <div>
        <nav class="crumbs" aria-label="Lokasi"><a href="{{ route('admin.index', ['year' => $year]) }}">Dashboard pengelola</a><span>/</span><span>Pengelolaan data</span></nav>
        <h1>Manajemen Dataset Anggaran</h1>
        <p>Impor, periksa, verifikasi, dan perbarui data APBDes sebelum ditampilkan pada dashboard.</p>
    </div>
    <div class="page-actions"><a class="btn btn-secondary" href="{{ route('admin.realizations.index', ['year' => $year]) }}"><x-icon name="receipt-text" /> Catat realisasi</a><button class="btn btn-primary" type="button" data-budget-open><x-icon name="plus" /> Tambah anggaran</button></div>
</div>

<div class="notice notice-warn" data-reveal><x-icon name="info" /><span><strong>Penting.</strong> Data yang belum lengkap atau belum terverifikasi tidak digunakan untuk menghitung realisasi, sisa anggaran, dan penyerapan. Barisnya tetap tampil dengan label statusnya; tandai terverifikasi hanya setelah periode, kategori, uraian, dan nominalnya dicocokkan dengan dokumen sumber.</span></div>

<form id="impor" class="grid grid-2" method="POST" action="{{ route('admin.import') }}" enctype="multipart/form-data">
    @csrf
    <article class="card" data-reveal>
        <header class="card-head"><div><h2>1. Impor Data APBDes</h2><p>Unggah dataset terstruktur untuk menambah data anggaran.</p></div><a class="btn btn-secondary btn-sm" href="{{ route('admin.import.template') }}"><x-icon name="file-down" /> Template</a></header>
        <div class="card-body form-stack">
            <label class="dropzone" data-dropzone><input id="import-file" type="file" name="file" accept=".csv,.xlsx" required><x-icon name="file-up" /><strong data-dropzone-label>Seret berkas ke sini atau <u>pilih berkas Excel atau CSV</u></strong><p>Format dan kelengkapan kolom diperiksa sebelum impor. Jika ada satu baris salah, tidak ada yang disimpan.</p></label>
            @error('file')<span class="field-error">{{ $message }}</span>@enderror
            <p class="hint">Kolom: year, type, category, sub_category, description, volume, amount, funding_source, source, verification_status, source_date. Maksimal 2 MB.</p>
        </div>
    </article>
    <article class="card" data-reveal>
        <header class="card-head"><div><h2>2. Informasi Sumber Data</h2><p>Dicatat pada Riwayat Dataset dan dipakai untuk baris yang kolom sumbernya kosong.</p></div></header>
        <div class="card-body form-stack">
            <div class="field"><label for="import-year">Periode anggaran</label><input class="input" id="import-year" type="number" name="year" min="2000" max="2100" value="{{ $importOld('year', $year) }}" required>@error('year')<span class="field-error">{{ $message }}</span>@enderror</div>
            <div class="field"><label for="import-source">Nama / jenis sumber</label><input class="input" id="import-source" name="source" maxlength="500" value="{{ $importOld('source') }}" required placeholder="Contoh: Perdes No. 2 Tahun 2026 tentang APBDes">@error('source')<span class="field-error">{{ $message }}</span>@enderror</div>
            <div class="field"><label for="import-date">Tanggal pembaruan <span class="required-tag">Wajib dicatat</span></label><input class="input" id="import-date" type="date" name="source_date" max="{{ now()->toDateString() }}" value="{{ $importOld('source_date') }}" required>@error('source_date')<span class="field-error">{{ $message }}</span>@enderror</div>
            <div class="form-actions"><span class="hint">Impor hanya dapat dilakukan pengelola data.</span><button class="btn btn-primary" type="submit"><x-icon name="upload" /> Periksa & impor</button></div>
        </div>
    </article>
</form>

<section class="card section-card" id="pemeriksaan" data-reveal>
    <header class="card-head"><div><h2>3. Pemeriksaan dan Status Verifikasi</h2><p>Periksa kesesuaian periode, kategori, nama komponen, nilai nominal, dan total terhadap dokumen sumber.</p></div>@if($checks)<span class="badge {{ $passedChecks === count($checks) ? 'badge-green' : 'badge-amber' }}">{{ $passedChecks }} dari {{ count($checks) }} lolos</span>@endif</header>
    <div class="card-body">
        <div class="status-pills">
            @foreach($statusCounts as $key => $count)
                <a class="status-pill tone-{{ $statusTones[$key][0] }} {{ $filters['status'] === $key ? 'is-active' : '' }}" href="{{ route('admin.budgets.index', ['year' => $year, 'status' => $key]) }}#data"><x-icon :name="$statusTones[$key][1]" /><span><strong>{{ \App\Models\BudgetLine::STATUSES[$key] }}</strong><small>{{ $count }} baris</small></span></a>
            @endforeach
        </div>
        <ul class="check-list">
            @forelse($checks as $check)
                <li class="{{ $check['passed'] ? 'is-passed' : 'is-failed' }}"><x-icon :name="$check['passed'] ? 'circle-check' : 'triangle-alert'" /><div><strong>{{ $check['passed'] ? $check['label'] : $check['problem'] }}</strong><p>{{ $check['detail'] }}</p></div></li>
            @empty
                <li class="is-empty">Belum ada data tahun {{ $year }} untuk diperiksa.</li>
            @endforelse
        </ul>
    </div>
    <footer class="card-foot"><span>Untuk menetapkan status, centang baris pada tabel <a class="link" href="#data">Data anggaran</a> lalu pilih statusnya.</span><span>Terverifikasi berarti sudah dicocokkan pengelola, bukan hasil audit.</span></footer>
</section>

<section class="card section-card" id="riwayat" data-reveal>
    <header class="card-head">
        <div><h2>4. Riwayat Dataset</h2><p>Setiap impor dan input manual tercatat beserta sumber dan tanggal pembaruannya.</p></div>
        <div class="table-search"><x-icon name="search" /><label class="sr-only" for="dataset-search">Cari dataset</label><input class="input" id="dataset-search" type="search" placeholder="Cari nama dataset / sumber" data-table-filter="#dataset-table [data-dataset-row]" data-empty-target="#dataset-empty"></div>
    </header>
    <div class="table-wrap">
        <table class="table stack" id="dataset-table">
            <thead><tr><th>Periode</th><th>Nama dataset</th><th>Sumber data</th><th>Tanggal pembaruan</th><th>Status</th><th><span class="sr-only">Aksi</span></th></tr></thead>
            <tbody>
            @forelse($datasets as $dataset)
                @php
                    $state = $dataset->budget_lines_count === 0 ? ['Kosong', 'badge-gray'] : ($dataset->verified_lines_count === $dataset->budget_lines_count ? ['Terverifikasi', 'badge-green'] : ['Perlu cek', 'badge-amber']);
                    $editData = ['id' => $dataset->id, 'name' => $dataset->name, 'source' => $dataset->source, 'source_date' => $dataset->source_date?->toDateString(), 'rows' => $dataset->budget_lines_count];
                @endphp
                <tr data-dataset-row style="--row: {{ $loop->index }}" @class(['is-selected' => $activeDataset?->is($dataset)])>
                    <td data-label="Periode"><span class="strong">{{ $dataset->year }}</span></td>
                    <td class="cell-main"><span class="strong">{{ $dataset->name }}</span><span class="sub">{{ $dataset->budget_lines_count }} baris · {{ $dataset->verified_lines_count }} terverifikasi · dicatat {{ $dataset->user?->name ?? 'sistem' }}, {{ $dataset->created_at->timezone('Asia/Jakarta')->translatedFormat('j M Y') }}</span></td>
                    <td data-label="Sumber data">{{ $dataset->source }}</td>
                    <td data-label="Tanggal pembaruan">@if($dataset->source_date){{ $dataset->source_date->translatedFormat('j M Y') }}@else<span class="badge badge-amber">Belum dicatat</span>@endif</td>
                    <td data-label="Status"><span class="badge status {{ $state[1] }}">{{ $state[0] }}</span></td>
                    <td class="cell-actions">
                        <div class="row-actions">
                            <a class="icon-btn" href="{{ route('admin.budgets.index', ['year' => $dataset->year, 'dataset' => $dataset->id]) }}#data" title="Lihat baris dataset" aria-label="Lihat baris {{ $dataset->name }}"><x-icon name="list-filter" /></a>
                            <button class="icon-btn" type="button" data-dataset-edit="{{ json_encode($editData) }}" data-update-url="{{ route('admin.datasets.update', $dataset) }}" title="Ubah informasi sumber" aria-label="Ubah informasi sumber {{ $dataset->name }}"><x-icon name="pencil" /></button>
                            <form method="POST" action="{{ route('admin.datasets.destroy', $dataset) }}" data-confirm-title="Hapus dataset ini?" data-confirm="Dataset “{{ $dataset->name }}” beserta {{ $dataset->budget_lines_count }} baris anggaran{{ $dataset->realizations_count ? ' dan '.$dataset->realizations_count.' catatan realisasinya' : '' }} akan dihapus. Gunakan ini untuk membatalkan impor yang salah. Riwayat aktivitas tetap menyimpan jejaknya.">@csrf @method('DELETE')<button class="icon-btn icon-btn-danger" type="submit" title="Hapus dataset" aria-label="Hapus dataset {{ $dataset->name }}"><x-icon name="trash-2" /></button></form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6"><div class="empty"><x-icon name="folder-open" /><p>Belum ada dataset. Impor berkas atau tambah anggaran untuk memulai.</p></div></td></tr>
            @endforelse
            <tr id="dataset-empty" hidden><td colspan="6"><div class="empty"><x-icon name="search-x" /><p>Tidak ada dataset yang cocok dengan pencarian.</p></div></td></tr>
            </tbody>
        </table>
    </div>
    <footer class="card-foot"><span>{{ $datasets->count() }} dataset tercatat</span><span>Menghapus dataset juga menghapus baris anggaran dan catatan realisasinya.</span></footer>
</section>

<section class="card section-card" id="data" data-reveal>
    <header class="card-head"><div><h2>Data anggaran <span class="badge badge-blue">{{ $summary['total_count'] }} baris</span></h2><p>Tahun anggaran {{ $year }} · sumber: {{ $payload['source'] ?? '–' }}</p></div><a class="btn btn-secondary btn-sm" href="{{ route('export', ['year' => $year]) }}"><x-icon name="download" /> Ekspor CSV</a></header>
    <form class="filters" method="GET" action="{{ route('admin.budgets.index') }}#data" style="padding-top: 0">
        <input type="hidden" name="year" value="{{ $year }}">
        @if($filters['dataset'])<input type="hidden" name="dataset" value="{{ $filters['dataset'] }}">@endif
        <div class="search"><x-icon name="search" /><label class="sr-only" for="admin-search">Cari</label><input class="input" id="admin-search" type="search" name="q" value="{{ $filters['q'] }}" placeholder="Cari kegiatan, bidang, sumber dana (tekan /)" data-search></div>
        <label class="sr-only" for="admin-type">Jenis</label><select class="select" id="admin-type" name="type"><option value="">Semua jenis</option><option value="income" @selected($filters['type'] === 'income')>Pendapatan</option><option value="expense" @selected($filters['type'] === 'expense')>Belanja</option></select>
        <label class="sr-only" for="admin-status">Status</label><select class="select" id="admin-status" name="status"><option value="">Semua status</option>@foreach(\App\Models\BudgetLine::STATUSES as $key => $label)<option value="{{ $key }}" @selected($filters['status'] === $key)>{{ $label }}</option>@endforeach</select>
        <button class="btn btn-primary" type="submit">Terapkan</button>
        @if($hasFilter)<a class="btn btn-ghost" href="{{ route('admin.budgets.index', ['year' => $year]) }}#data">Hapus filter</a>@endif
    </form>
    @if($activeDataset)
        <div class="filter-summary"><span>Menampilkan baris dari dataset <strong>{{ $activeDataset->name }}</strong></span><a href="{{ route('admin.budgets.index', array_filter(['year' => $year, 'q' => $filters['q'], 'type' => $filters['type'], 'status' => $filters['status']])) }}#data"><x-icon name="x" /> Semua dataset</a></div>
    @endif
    <form id="verify-form" class="bulk-bar" method="POST" action="{{ route('admin.budgets.verify') }}" data-bulk-bar>
        @csrf @method('PATCH')
        <label class="check"><input type="checkbox" data-select-all><span>Pilih semua</span></label>
        <span class="bulk-count" data-bulk-count aria-live="polite">Centang baris untuk menetapkan status sekaligus</span>
        <label class="sr-only" for="bulk-status">Status verifikasi</label>
        <select class="select" id="bulk-status" name="verification_status">@foreach(\App\Models\BudgetLine::STATUSES as $key => $label)<option value="{{ $key }}" @selected(old('verification_status', 'verified') === $key)>{{ $label }}</option>@endforeach</select>
        <button class="btn btn-primary btn-sm" type="submit"><x-icon name="shield-check" /> Tetapkan status</button>
    </form>
    <div class="table-wrap">
        <table class="table stack">
            <thead><tr><th class="cell-check"><span class="sr-only">Pilih</span></th><th>Uraian</th><th>Jenis</th><th>Sumber dana</th><th class="text-right">Anggaran</th><th>Realisasi</th><th>Status</th><th><span class="sr-only">Aksi</span></th></tr></thead>
            <tbody>
            @forelse($payload['rows'] as $row)
                <tr style="--row: {{ $loop->index }}">
                    <td class="cell-check"><input type="checkbox" name="ids[]" value="{{ $row['id'] }}" form="verify-form" aria-label="Pilih {{ $row['description'] }}" @checked(in_array($row['id'], $selectedIds, true))></td>
                    <td class="cell-main"><span class="strong">{{ $search->highlight($row['description']) }}</span><span class="sub">{{ $search->highlight($row['category']) }}@if($row['sub_category']) · {{ $row['sub_category'] }}@endif @if($row['volume'])· {{ $row['volume'] }}@endif</span></td>
                    <td data-label="Jenis"><span class="badge {{ $row['type'] === 'income' ? 'badge-green' : 'badge-blue' }}">{{ $row['type'] === 'income' ? 'Pendapatan' : 'Belanja' }}</span></td>
                    <td data-label="Sumber dana">{{ $row['funding_source'] ?? '–' }}</td>
                    <td class="text-right amount" data-label="Anggaran"><x-money :value="$row['amount']" :decimals="2" /></td>
                    <td data-label="Realisasi">@if($row['type'] === 'expense')<x-realization-cell :row="$row" />@else – @endif</td>
                    <td data-label="Status"><x-status :status="$row['verification_status']" /></td>
                    <td class="cell-actions">
                        <div class="row-actions">
                            <button class="icon-btn" type="button" data-budget-edit="{{ json_encode(collect($row)->only(['id', 'year', 'type', 'category', 'sub_category', 'description', 'volume', 'amount', 'funding_source', 'source', 'source_date', 'verification_status'])) }}" data-update-url="{{ route('admin.budgets.update', $row['id']) }}" aria-label="Ubah {{ $row['description'] }}" title="Ubah"><x-icon name="pencil" /></button>
                            <form method="POST" action="{{ route('admin.budgets.destroy', $row['id']) }}" data-confirm="Data “{{ $row['description'] }}” beserta catatan realisasinya akan dihapus. Catatan penghapusan tetap tersimpan di riwayat.">@csrf @method('DELETE')<button class="icon-btn icon-btn-danger" type="submit" aria-label="Hapus {{ $row['description'] }}" title="Hapus"><x-icon name="trash-2" /></button></form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8"><div class="empty"><x-icon name="search-x" /><strong>Tidak ada data</strong><p>{{ $hasFilter ? 'Coba kata kunci lain atau hapus filter.' : 'Tambahkan anggaran atau impor dari template.' }}</p></div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <footer class="card-foot"><span>{{ count($payload['rows']) }} baris ditampilkan</span><span><x-icon name="lock-keyhole" class="icon" style="display: inline; width: 14px; height: 14px; vertical-align: -2px" /> Hanya perangkat desa yang dapat mengubah data</span></footer>
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
            <div class="field"><label for="budget-year">Tahun anggaran</label><input class="input" id="budget-year" name="year" type="number" min="2000" max="2100" value="{{ old('_budget_form') ? old('year') : $year }}" required></div>
            <div class="field"><label for="budget-type">Jenis</label><select class="select" id="budget-type" name="type" required><option value="expense" @selected(old('type', 'expense') === 'expense')>Belanja</option><option value="income" @selected(old('type') === 'income')>Pendapatan</option></select></div>
            <div class="field"><label for="budget-category">Bidang / jenis pendapatan</label><input class="input" id="budget-category" name="category" list="category-options" maxlength="150" value="{{ old('category') }}" required><datalist id="category-options">@foreach($payload['categories'] as $category)<option value="{{ $category }}">@endforeach</datalist></div>
            <div class="field"><label for="budget-sub">Sub bidang <span class="optional">opsional</span></label><input class="input" id="budget-sub" name="sub_category" maxlength="150" value="{{ old('sub_category') }}" placeholder="Misalnya: Kesehatan"></div>
            <div class="field span-2"><label for="budget-description">Nama kegiatan</label><input class="input" id="budget-description" name="description" maxlength="255" value="{{ old('description') }}" required placeholder="Misalnya: Rehabilitasi saluran irigasi RT 03"></div>
            <div class="field"><label for="budget-volume">Volume <span class="optional">opsional</span></label><input class="input" id="budget-volume" name="volume" maxlength="50" value="{{ old('volume') }}" placeholder="12 bulan, 1 paket, 3 unit"></div>
            <div class="field"><label for="budget-funding">Sumber dana</label><input class="input" id="budget-funding" name="funding_source" list="funding-options" maxlength="50" value="{{ old('funding_source') }}" placeholder="Dana Desa, ADD, BHP"><datalist id="funding-options">@foreach(['Dana Desa', 'ADD', 'BHP', 'PAD', 'Bunga Bank'] as $source)<option value="{{ $source }}">@endforeach</datalist></div>
            <div class="field"><label for="budget-amount">Anggaran (Rp)</label><input class="input" id="budget-amount" name="amount" type="number" min="0" step="0.01" inputmode="decimal" value="{{ old('amount') }}" required data-money-preview><span class="money-preview" data-money-output></span></div>
            <div class="field"><label for="budget-status">Status verifikasi</label><select class="select" id="budget-status" name="verification_status" required>@foreach(['unverified', 'verified', 'unavailable'] as $key)<option value="{{ $key }}" @selected(old('verification_status', 'unverified') === $key)>{{ \App\Models\BudgetLine::STATUSES[$key] }}</option>@endforeach</select></div>
            <div class="field span-2"><label for="budget-source">Dokumen sumber</label><input class="input" id="budget-source" name="source" maxlength="500" value="{{ old('_budget_form') ? old('source') : $payload['source'] }}" required></div>
            <div class="field"><label for="budget-source-date">Tanggal dokumen <span class="optional">opsional</span></label><input class="input" id="budget-source-date" name="source_date" type="date" max="{{ now()->toDateString() }}" value="{{ old('_budget_form') ? old('source_date') : '' }}"></div>
        </div>
        <footer class="dialog-foot"><button class="btn btn-secondary" type="button" data-dialog-close>Batal</button><button class="btn btn-primary" type="submit"><x-icon name="check" /> Simpan</button></footer>
    </form>
</dialog>

<dialog id="dataset-dialog" class="dialog" aria-labelledby="dataset-dialog-title" @if($errors->any() && old('_dataset_form')) data-open-on-load @endif>
    <form method="POST" action="{{ old('_dataset_form') && old('_dataset_id') ? route('admin.datasets.update', (int) old('_dataset_id')) : route('admin.budgets.index') }}" data-dataset-form>
        <header class="dialog-head"><h2 id="dataset-dialog-title">Ubah informasi sumber dataset</h2><button class="icon-btn" type="button" data-dialog-close aria-label="Tutup"><x-icon name="x" /></button></header>
        @csrf @method('PATCH')
        <input type="hidden" name="_dataset_form" value="1">
        <input type="hidden" name="_dataset_id" value="{{ old('_dataset_form') ? old('_dataset_id') : '' }}">
        <div class="dialog-body form-grid">
            @if($errors->any() && old('_dataset_form'))<div class="alert span-2" style="margin: 0" data-dataset-error><x-icon name="circle-alert" /><div>{{ $errors->first() }}</div></div>@endif
            <div class="field span-2"><label for="dataset-name">Nama dataset</label><input class="input" id="dataset-name" name="name" maxlength="255" value="{{ old('_dataset_form') ? old('name') : '' }}" required></div>
            <div class="field span-2"><label for="dataset-source">Nama / jenis sumber</label><input class="input" id="dataset-source" name="source" maxlength="500" value="{{ old('_dataset_form') ? old('source') : '' }}" required placeholder="Contoh: Baliho APBDes 2026 atau Perdes APBDes"></div>
            <div class="field"><label for="dataset-date">Tanggal pembaruan <span class="required-tag">Wajib dicatat</span></label><input class="input" id="dataset-date" type="date" name="source_date" max="{{ now()->toDateString() }}" value="{{ old('_dataset_form') ? old('source_date') : '' }}" required></div>
            <label class="check span-2"><input type="checkbox" name="apply_to_lines" value="1" @checked(old('_dataset_form') ? old('apply_to_lines') : true)><span>Terapkan sumber dan tanggal ini ke <strong data-dataset-rows>semua baris</strong> dataset ini</span></label>
        </div>
        <footer class="dialog-foot"><button class="btn btn-secondary" type="button" data-dialog-close>Batal</button><button class="btn btn-primary" type="submit"><x-icon name="check" /> Simpan</button></footer>
    </form>
</dialog>
<x-confirm-dialog />
@endsection
