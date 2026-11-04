@extends('layouts.app')
@section('title', 'Kelola data')
@section('content')
@php
    $filters = $payload['filters'];
    $hasFilter = $filters['q'] || $filters['type'] || $filters['status'];
    $actionLabels = ['create' => 'Menambah anggaran', 'update' => 'Memperbarui anggaran', 'delete' => 'Menghapus anggaran', 'import' => 'Mengimpor dataset', 'seed' => 'Menyiapkan data contoh'];
    $actionIcons = ['create' => 'plus', 'update' => 'pencil', 'delete' => 'trash-2', 'import' => 'upload', 'seed' => 'database'];
@endphp

<header class="page-header" data-reveal>
    <div>
        <p class="page-kicker">Pengelola · APBDes {{ $payload['year'] }}</p>
        <h1>Kelola data anggaran</h1>
        <p class="page-lead">Perubahan langsung tampil di halaman publik dan tercatat di riwayat aktivitas.</p>
    </div>
    <div class="page-actions">
        <x-year-select :years="$payload['years']" :selected="$payload['year']" id="admin-year" />
        <a class="button button-outline" href="#impor-data"><x-icon name="upload" /> Impor</a>
        <button type="button" class="button button-primary" data-budget-open><x-icon name="plus" /> Tambah baris</button>
    </div>
</header>

<section class="panel panel-flush" aria-labelledby="admin-table-title" data-reveal>
    <header class="panel-head">
        <div><h2 id="admin-table-title">Baris anggaran <span class="count-pill">{{ $payload['summary']['total_count'] }}</span></h2><p>Klik ikon pensil untuk mengubah, atau ikon tempat sampah untuk menghapus.</p></div>
        <a class="button button-outline button-small" href="{{ route('export', ['year' => $payload['year']]) }}"><x-icon name="download" /> Ekspor CSV</a>
    </header>

    <form class="filters" action="{{ route('admin.index') }}" method="GET">
        <input type="hidden" name="year" value="{{ $payload['year'] }}">
        <div class="field-search"><x-icon name="search" /><label class="sr-only" for="admin-search">Cari baris anggaran</label><input id="admin-search" type="search" name="q" value="{{ $filters['q'] }}" placeholder="Cari uraian atau kategori"></div>
        <label class="field-select"><span class="sr-only">Jenis anggaran</span><select name="type"><option value="">Semua jenis</option><option value="income" @selected($filters['type'] === 'income')>Pendapatan</option><option value="expense" @selected($filters['type'] === 'expense')>Belanja</option></select></label>
        <label class="field-select"><span class="sr-only">Status pemeriksaan</span><select name="status"><option value="">Semua status</option><option value="verified" @selected($filters['status'] === 'verified')>Terverifikasi</option><option value="unverified" @selected($filters['status'] === 'unverified')>Belum diverifikasi</option><option value="unavailable" @selected($filters['status'] === 'unavailable')>Belum tersedia</option></select></label>
        <button class="button button-primary" type="submit">Terapkan</button>
        @if($hasFilter)<a class="button button-quiet" href="{{ route('admin.index', ['year' => $payload['year']]) }}">Hapus filter</a>@endif
    </form>

    <div class="table-scroll">
        <table class="table table-responsive">
            <thead><tr><th scope="col">Uraian</th><th scope="col">Jenis</th><th scope="col" class="num">Anggaran</th><th scope="col">Status</th><th scope="col"><span class="sr-only">Aksi</span></th></tr></thead>
            <tbody>
            @forelse($payload['rows'] as $row)
                <tr style="--row: {{ $loop->index }}">
                    <td data-label="Uraian"><span class="row-title">{{ $row['description'] }}</span><span class="row-sub">{{ $row['category'] }}@if($row['is_demo']) · <span class="demo-tag">contoh</span>@endif</span></td>
                    <td data-label="Jenis"><span class="type-label"><span class="type-dot type-{{ $row['type'] }}"></span>{{ $row['type'] === 'income' ? 'Pendapatan' : 'Belanja' }}</span></td>
                    <td data-label="Anggaran" class="num"><x-money :value="$row['amount']" /></td>
                    <td data-label="Status"><x-status :status="$row['verification_status']" /></td>
                    <td class="row-actions">
                        <button class="icon-button" type="button" data-budget-edit="{{ json_encode($row) }}" data-update-url="{{ route('admin.budgets.update', $row['id']) }}" aria-label="Ubah {{ $row['description'] }}" title="Ubah"><x-icon name="pencil" /></button>
                        <form action="{{ route('admin.budgets.destroy', $row['id']) }}" method="POST" data-confirm-delete="{{ $row['description'] }}">@csrf @method('DELETE')<button class="icon-button icon-button-danger" type="submit" aria-label="Hapus {{ $row['description'] }}" title="Hapus"><x-icon name="trash-2" /></button></form>
                    </td>
                </tr>
            @empty
                <tr class="is-empty-row"><td colspan="5"><div class="empty"><x-icon name="search-x" /><strong>Tidak ada baris yang cocok</strong><p>{{ $hasFilter ? 'Coba kata kunci lain atau hapus filter.' : 'Tahun ini belum memiliki data. Tambahkan baris atau impor dari template.' }}</p>@if($hasFilter)<a class="button button-outline button-small" href="{{ route('admin.index', ['year' => $payload['year']]) }}">Hapus filter</a>@endif</div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <footer class="panel-foot"><span>{{ count($payload['rows']) }} baris ditampilkan</span><span><x-icon name="lock" /> Hanya terlihat oleh pengelola</span></footer>
</section>

<div class="two-col two-col-wide">
    <section class="panel" id="impor-data" aria-labelledby="import-title" data-reveal>
        <header class="panel-head"><div><h2 id="import-title">Impor dari berkas</h2><p>CSV atau Excel (.xlsx) dengan kolom sesuai template, maksimal 2 MB.</p></div></header>
        <form action="{{ route('admin.import') }}" method="POST" enctype="multipart/form-data" class="import-form">
            @csrf
            <label class="dropzone" data-dropzone>
                <input id="import-file" type="file" name="file" accept=".csv,.xlsx" required aria-describedby="import-help">
                <x-icon name="file-up" />
                <span class="dropzone-title" data-dropzone-label>Seret berkas ke sini atau <u>pilih dari komputer</u></span>
                <span class="dropzone-hint" id="import-help">Semua baris diperiksa dulu. Jika ada satu baris salah, tidak ada yang disimpan.</span>
            </label>
            <div class="form-grid">
                <div class="field"><label for="import-year">Tahun anggaran</label><input id="import-year" type="number" name="year" min="2000" max="2100" value="{{ old('year', $payload['year']) }}" required></div>
                <div class="field"><label for="import-date">Tanggal dokumen</label><input id="import-date" type="date" name="source_date" value="{{ old('source_date') }}"></div>
                <div class="field span-2"><label for="import-source">Sumber data <span class="optional">bila kolom sumber kosong</span></label><input id="import-source" type="text" name="source" maxlength="500" value="{{ old('source') }}" placeholder="Contoh: Perdes No. 2 Tahun 2026 tentang APBDes"></div>
            </div>
            <label class="check"><input type="checkbox" name="is_demo" value="1" @checked(old('is_demo'))><span>Tandai sebagai data contoh (bukan data resmi)</span></label>
            <div class="form-actions">
                <a class="button button-quiet" href="{{ route('admin.import.template') }}"><x-icon name="file-down" /> Unduh template</a>
                <button type="submit" class="button button-primary"><x-icon name="upload" /> Periksa dan impor</button>
            </div>
        </form>
    </section>

    <section class="panel" aria-labelledby="activity-title" data-reveal>
        <header class="panel-head"><div><h2 id="activity-title">Riwayat aktivitas</h2><p>Siapa mengubah apa, dan kapan.</p></div></header>
        @forelse(array_slice($payload['activities'], 0, 6) as $activity)
            @if($loop->first)<ol class="timeline">@endif
            <li>
                <span class="timeline-icon timeline-{{ $activity['action'] }}"><x-icon :name="$actionIcons[$activity['action']] ?? 'circle'" /></span>
                <div>
                    <strong>{{ $actionLabels[$activity['action']] ?? $activity['action'] }}</strong>
                    <p>{{ $activity['description'] }}</p>
                    <span class="timeline-meta">{{ $activity['user'] }} · {{ \Carbon\Carbon::parse($activity['created_at'])->timezone('Asia/Jakarta')->translatedFormat('j M Y, H.i') }} WIB</span>
                </div>
            </li>
            @if($loop->last)</ol>@endif
        @empty
            <div class="empty"><x-icon name="history" /><strong>Belum ada aktivitas</strong><p>Riwayat akan muncul setelah data ditambah atau diubah.</p></div>
        @endforelse
    </section>
</div>

<section class="panel panel-flush" aria-labelledby="dataset-history-title" data-reveal>
    <header class="panel-head"><div><h2 id="dataset-history-title">Dataset tahun {{ $payload['year'] }}</h2><p>Asal setiap kelompok data yang pernah dimasukkan.</p></div></header>
    <div class="table-scroll">
        <table class="table table-responsive">
            <thead><tr><th scope="col">Nama dataset</th><th scope="col">Sumber</th><th scope="col" class="num">Baris</th><th scope="col">Ditambahkan</th></tr></thead>
            <tbody>
            @forelse($payload['datasets'] as $dataset)
                <tr><td data-label="Dataset"><span class="row-title">{{ $dataset['name'] }}</span>@if($dataset['is_demo'])<span class="row-sub"><span class="demo-tag">contoh</span></span>@endif</td><td data-label="Sumber">{{ $dataset['source'] }}</td><td data-label="Baris" class="num">{{ $dataset['row_count'] }}</td><td data-label="Ditambahkan">{{ \Carbon\Carbon::parse($dataset['created_at'])->timezone('Asia/Jakarta')->translatedFormat('j M Y') }}</td></tr>
            @empty
                <tr class="is-empty-row"><td colspan="4"><div class="empty"><p>Belum ada dataset untuk tahun ini.</p></div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>

<dialog id="budget-dialog" class="dialog" aria-labelledby="budget-dialog-title" @if($errors->any() && old('_budget_form')) data-open-on-load @endif>
    <form id="budget-form" action="{{ route('admin.budgets.store') }}" data-store-url="{{ route('admin.budgets.store') }}" method="POST">
        <header class="dialog-head">
            <h2 id="budget-dialog-title" data-dialog-title>Tambah baris anggaran</h2>
            <button class="icon-button" type="button" data-dialog-close aria-label="Tutup formulir"><x-icon name="x" /></button>
        </header>
        @csrf
        <input name="_budget_form" type="hidden" value="1">
        <input name="_method" type="hidden" value="PATCH" disabled>
        <input name="_editing_id" type="hidden" value="{{ old('_editing_id') }}">
        <div class="dialog-body form-grid">
            <div class="field"><label for="budget-year">Tahun anggaran</label><input id="budget-year" name="year" type="number" min="2000" max="2100" value="{{ old('year', $payload['year']) }}" required></div>
            <div class="field"><label for="budget-type">Jenis</label><select id="budget-type" name="type" required><option value="income" @selected(old('type') === 'income')>Pendapatan</option><option value="expense" @selected(old('type', 'expense') === 'expense')>Belanja</option></select></div>
            <div class="field span-2"><label for="budget-category">Bidang / sumber pendapatan</label><input id="budget-category" name="category" list="budget-category-options" value="{{ old('category') }}" maxlength="150" placeholder="Misalnya: Pembangunan Desa" required><datalist id="budget-category-options">@foreach($payload['categories'] as $category)<option value="{{ $category }}">@endforeach</datalist>@error('category')<span class="field-error">{{ $message }}</span>@enderror</div>
            <div class="field span-2"><label for="budget-description">Uraian kegiatan</label><textarea id="budget-description" name="description" rows="2" maxlength="255" placeholder="Misalnya: Rehabilitasi saluran irigasi RT 03" required>{{ old('description') }}</textarea>@error('description')<span class="field-error">{{ $message }}</span>@enderror</div>
            <div class="field"><label for="budget-amount">Nilai anggaran (Rp)</label><input id="budget-amount" name="amount" type="number" min="0" step="0.01" value="{{ old('amount') }}" placeholder="25000000" required inputmode="decimal" data-money-preview>@error('amount')<span class="field-error">{{ $message }}</span>@enderror<span class="field-hint" data-money-output></span></div>
            <div class="field"><label for="budget-realization">Realisasi (Rp) <span class="optional">opsional</span></label><input id="budget-realization" name="realization" type="number" min="0" step="0.01" value="{{ old('realization') }}" placeholder="Kosongkan bila belum ada" inputmode="decimal" data-money-preview><span class="field-hint" data-money-output></span></div>
            <div class="field span-2"><label for="budget-source">Dokumen sumber</label><input id="budget-source" name="source" type="text" maxlength="500" value="{{ old('source') }}" placeholder="Misalnya: Perdes No. 2 Tahun 2026 tentang APBDes" required></div>
            <div class="field"><label for="budget-source-date">Tanggal dokumen</label><input id="budget-source-date" name="source_date" type="date" value="{{ old('source_date') }}"></div>
            <div class="field"><label for="budget-status">Status pemeriksaan</label><select id="budget-status" name="verification_status" required><option value="unverified" @selected(old('verification_status', 'unverified') === 'unverified')>Belum diverifikasi</option><option value="verified" @selected(old('verification_status') === 'verified')>Terverifikasi</option><option value="unavailable" @selected(old('verification_status') === 'unavailable')>Belum tersedia</option></select></div>
            <p class="field-hint span-2 demo-hint" data-budget-demo-note hidden><x-icon name="flask-conical" /> Baris ini berasal dari data contoh. Setelah diubah pun tetap berlabel contoh.</p>
        </div>
        <footer class="dialog-foot"><button class="button button-quiet" type="button" data-dialog-close>Batal</button><button class="button button-primary" type="submit">Simpan</button></footer>
    </form>
</dialog>

<dialog id="delete-dialog" class="dialog dialog-small" aria-labelledby="delete-dialog-title">
    <div class="dialog-body">
        <span class="danger-icon"><x-icon name="trash-2" /></span>
        <h2 id="delete-dialog-title">Hapus baris ini?</h2>
        <p>“<strong data-delete-description></strong>” akan dihapus dari data publik. Catatan penghapusannya tetap tersimpan di riwayat.</p>
    </div>
    <footer class="dialog-foot"><button class="button button-quiet" type="button" data-dialog-close>Batal</button><button class="button button-danger" type="button" data-delete-confirm>Hapus</button></footer>
</dialog>
@endsection
