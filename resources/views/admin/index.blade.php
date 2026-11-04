@extends('layouts.app')

@section('title', 'Pengelolaan data')

@section('content')
<div class="page-heading">
    <div><div class="page-kicker">Pengelola · APBDes {{ $payload['year'] }}</div><h1>Kelola data anggaran</h1><p>Perubahan langsung tampil di halaman publik dan tercatat di riwayat aktivitas.</p></div>
    <div class="heading-actions"><form method="GET" class="year-form"><x-icon name="calendar-days" /><label class="sr-only" for="admin-year">Tahun anggaran pengelolaan</label><select id="admin-year" name="year" data-auto-submit>@foreach($payload['years'] as $year)<option value="{{ $year }}" @selected($year == $payload['year'])>TA {{ $year }}</option>@endforeach</select><noscript><button>Pilih</button></noscript></form><button type="button" class="btn btn-primary" data-budget-open><x-icon name="plus" /> Tambah anggaran</button></div>
</div>

<section class="card admin-data-card">
    <div class="card-header"><div><h2>Dataset anggaran <span class="badge badge-muted">{{ $payload['summary']['total_count'] }} data</span></h2><p>Tahun anggaran {{ $payload['year'] }} · Perubahan akan tercatat dalam riwayat.</p></div><a class="btn btn-secondary btn-sm" href="{{ route('export', ['year' => $payload['year']]) }}"><x-icon name="download" /> Ekspor CSV</a></div>

    <form class="table-filters" action="{{ route('admin.index') }}" method="GET">
        <input type="hidden" name="year" value="{{ $payload['year'] }}">
        <div class="search-input"><x-icon name="search" /><label class="sr-only" for="admin-search">Cari anggaran</label><input id="admin-search" type="search" name="q" value="{{ $payload['filters']['q'] }}" placeholder="Cari uraian atau kategori…"></div>
        <div class="filter-select"><label class="sr-only" for="admin-type">Jenis anggaran</label><select id="admin-type" name="type"><option value="">Semua jenis</option><option value="income" @selected($payload['filters']['type'] === 'income')>Pendapatan</option><option value="expense" @selected($payload['filters']['type'] === 'expense')>Belanja</option></select></div>
        <div class="filter-select"><label class="sr-only" for="admin-status">Status verifikasi</label><select id="admin-status" name="status"><option value="">Semua status</option><option value="verified" @selected($payload['filters']['status'] === 'verified')>Terverifikasi</option><option value="unverified" @selected($payload['filters']['status'] === 'unverified')>Belum diverifikasi</option><option value="unavailable" @selected($payload['filters']['status'] === 'unavailable')>Belum tersedia</option></select></div>
        <button class="btn btn-secondary" type="submit">Terapkan <x-icon name="sliders-horizontal" /></button>
        @if($payload['filters']['q'] || $payload['filters']['type'] || $payload['filters']['status'])<a class="btn btn-ghost" href="{{ route('admin.index', ['year' => $payload['year']]) }}">Reset</a>@endif
    </form>

    <div class="table-wrap"><table class="data-table stack-on-mobile"><thead><tr><th scope="col">Uraian anggaran</th><th scope="col">Jenis</th><th scope="col" class="text-right">Nilai anggaran</th><th scope="col">Status</th><th scope="col" class="text-right">Aksi</th></tr></thead><tbody>
        @forelse($payload['rows'] as $row)
        <tr style="--row: {{ $loop->index }}">
            <td data-label="Uraian"><span class="table-title">{{ $payload['search']->highlight($row['description']) }}</span><span class="table-subtitle">{{ $payload['search']->highlight($row['category']) }} @if($row['is_demo'])<span class="inline-demo">· Contoh</span>@endif</span></td>
            <td data-label="Jenis"><span class="type-label {{ $row['type'] === 'income' ? 'type-income' : 'type-expense' }}"><x-icon :name="$row['type'] === 'income' ? 'arrow-down-left' : 'arrow-up-right'" />{{ $row['type'] === 'income' ? 'Pendapatan' : 'Belanja' }}</span></td>
            <td data-label="Anggaran" class="text-right table-amount"><x-money :value="$row['amount']" /></td>
            <td data-label="Status"><x-status :status="$row['verification_status']" /></td>
            <td class="actions-cell"><div class="row-actions"><button class="icon-btn" type="button" data-budget-edit="{{ json_encode($row) }}" data-update-url="{{ route('admin.budgets.update', $row['id']) }}" aria-label="Edit {{ $row['description'] }}" title="Edit anggaran"><x-icon name="pencil" /></button><form action="{{ route('admin.budgets.destroy', $row['id']) }}" method="POST" data-confirm-delete="{{ $row['description'] }}">@csrf @method('DELETE')<button class="icon-btn icon-btn-danger" type="submit" aria-label="Hapus {{ $row['description'] }}" title="Hapus anggaran"><x-icon name="trash-2" /></button></form></div></td>
        </tr>
        @empty
        <tr class="empty-row"><td colspan="5"><div class="empty-state"><x-icon name="search-x" /><h3>Tidak ada data yang cocok</h3><p>Coba kata kunci lain atau atur kembali filter pencarian.</p><a class="btn btn-secondary btn-sm" href="{{ route('admin.index', ['year' => $payload['year']]) }}">Reset pencarian</a></div></td></tr>
        @endforelse
    </tbody></table></div>
    <div class="table-footer"><span>{{ count($payload['rows']) }} data ditampilkan</span><span><x-icon name="lock-keyhole" /> Hanya pengelola yang dapat mengubah data</span></div>
</section>

<div class="admin-lower-grid">
    <section class="card import-card" id="impor-data">
        <div class="card-header"><div><h2>Impor dataset</h2><p>Unggah data terstruktur dari berkas CSV atau Excel.</p></div><span class="metric-icon"><x-icon name="upload" /></span></div>
        <div class="card-body">
            <form action="{{ route('admin.import') }}" method="POST" enctype="multipart/form-data" class="import-form">
                @csrf
                <label class="upload-area" data-dropzone><input id="import-file" type="file" name="file" accept=".csv,.xlsx" required aria-describedby="import-help"><x-icon name="file-up" /><strong data-dropzone-label>Seret berkas ke sini atau <u>pilih dari komputer</u></strong><p>CSV atau XLSX · Maksimal 2 MB</p></label>
                <p id="import-help" class="form-help">Gunakan struktur kolom pada template. Semua baris diperiksa sebelum data disimpan.</p>
                <div class="form-grid">
                    <div class="form-field"><label for="import-year">Tahun anggaran</label><input id="import-year" type="number" name="year" min="2000" max="2100" value="{{ old('year', $payload['year']) }}" required></div>
                    <div class="form-field"><label for="import-date">Tanggal sumber</label><input id="import-date" type="date" name="source_date" value="{{ old('source_date') }}"></div>
                    <div class="form-field full-width"><label for="import-source">Sumber data</label><input id="import-source" type="text" name="source" maxlength="500" value="{{ old('source') }}" placeholder="Contoh: Dokumen APBDes yang ditetapkan"><span class="form-help">Digunakan jika kolom sumber pada baris belum diisi.</span></div>
                </div>
                <label class="checkbox-field"><input type="checkbox" name="is_demo" value="1" @checked(old('is_demo'))><span>Ini dataset demo untuk mencoba aplikasi</span></label>
                <div class="form-actions"><a class="btn btn-secondary" href="{{ route('admin.import.template') }}"><x-icon name="download" /> Unduh template</a><button type="submit" class="btn btn-primary"><x-icon name="upload" /> Periksa & impor</button></div>
            </form>
        </div>
    </section>

    <section class="card activity-card">
        <div class="card-header"><div><h2>Aktivitas terbaru</h2><p>Riwayat perubahan membantu menjaga keterlacakan data.</p></div><x-icon name="history" /></div>
        <div class="card-body activity-list">
            @forelse(array_slice($payload['activities'], 0, 6) as $activity)
            <article class="activity-item"><span class="activity-dot"></span><div><h3>{{ ['create' => 'Anggaran ditambahkan', 'update' => 'Anggaran diperbarui', 'delete' => 'Anggaran dihapus', 'import' => 'Dataset diimpor', 'seed' => 'Data contoh disiapkan'][$activity['action']] ?? $activity['action'] }}</h3><p>{{ $activity['description'] }}</p><span class="activity-meta">{{ $activity['user'] }} · {{ \Carbon\Carbon::parse($activity['created_at'])->timezone('Asia/Jakarta')->translatedFormat('d M Y, H:i') }} WIB</span></div></article>
            @empty
            <div class="empty-state"><x-icon name="history" /><h3>Riwayat masih kosong</h3><p>Aktivitas akan tercatat setelah data ditambahkan atau diperbarui.</p></div>
            @endforelse
        </div>
    </section>
</div>

<section class="card dataset-history">
    <div class="card-header"><div><h2>Riwayat dataset</h2><p>Dokumentasi sumber dan berkas yang telah ditambahkan.</p></div><x-icon name="layers" /></div>
    <div class="table-wrap"><table class="data-table stack-on-mobile"><thead><tr><th scope="col">Nama dataset</th><th scope="col">Sumber</th><th scope="col">Tahun</th><th scope="col">Jumlah data</th><th scope="col">Ditambahkan</th></tr></thead><tbody>
        @forelse($payload['datasets'] as $dataset)
        <tr><td data-label="Dataset"><span class="table-title">{{ $dataset['name'] }}</span>@if($dataset['is_demo'])<span class="badge badge-demo">Data contoh</span>@endif</td><td data-label="Sumber">{{ $dataset['source'] }}</td><td data-label="Tahun">{{ $dataset['year'] }}</td><td data-label="Jumlah">{{ $dataset['row_count'] }} baris</td><td data-label="Ditambahkan">{{ \Carbon\Carbon::parse($dataset['created_at'])->timezone('Asia/Jakarta')->translatedFormat('d M Y') }}</td></tr>
        @empty
        <tr><td colspan="5"><div class="empty-state"><p>Belum ada dataset yang ditambahkan.</p></div></td></tr>
        @endforelse
    </tbody></table></div>
</section>

<dialog id="budget-dialog" class="form-dialog" aria-labelledby="budget-dialog-title" @if($errors->any() && old('_budget_form')) data-open-on-load @endif>
    <div class="dialog-header"><div><h2 id="budget-dialog-title" data-dialog-title>Tambah anggaran</h2></div><button class="icon-btn" type="button" data-dialog-close aria-label="Tutup formulir"><x-icon name="x" /></button></div>
    <form id="budget-form" action="{{ route('admin.budgets.store') }}" data-store-url="{{ route('admin.budgets.store') }}" method="POST">
        @csrf
        <input name="_budget_form" type="hidden" value="1">
        <input name="_method" type="hidden" value="PATCH" disabled>
        <input name="_editing_id" type="hidden" value="{{ old('_editing_id') }}">
        <div class="dialog-body form-grid">
            <div class="form-field"><label for="budget-year">Tahun anggaran</label><input id="budget-year" name="year" type="number" min="2000" max="2100" value="{{ old('year', $payload['year']) }}" required></div>
            <div class="form-field"><label for="budget-type">Jenis anggaran</label><select id="budget-type" name="type" required><option value="income" @selected(old('type') === 'income')>Pendapatan</option><option value="expense" @selected(old('type') === 'expense')>Belanja</option></select></div>
            <div class="form-field full-width"><label for="budget-category">Kategori</label><input id="budget-category" name="category" list="budget-category-options" value="{{ old('category') }}" maxlength="150" placeholder="Pilih atau tulis kategori" required><datalist id="budget-category-options">@foreach($payload['categories'] as $category)<option value="{{ $category }}">@endforeach</datalist>@error('category')<span class="field-error">{{ $message }}</span>@enderror</div>
            <div class="form-field full-width"><label for="budget-description">Uraian anggaran</label><textarea id="budget-description" name="description" rows="2" maxlength="255" placeholder="Jelaskan komponen atau kegiatan anggaran" required>{{ old('description') }}</textarea>@error('description')<span class="field-error">{{ $message }}</span>@enderror</div>
            <div class="form-field"><label for="budget-amount">Nilai anggaran (Rp)</label><input id="budget-amount" name="amount" type="number" min="0" step="0.01" value="{{ old('amount') }}" placeholder="Contoh: 25000000" required>@error('amount')<span class="field-error">{{ $message }}</span>@enderror</div>
            <div class="form-field"><label for="budget-realization">Realisasi (Rp) <span class="text-muted">opsional</span></label><input id="budget-realization" name="realization" type="number" min="0" step="0.01" value="{{ old('realization') }}" placeholder="Kosongkan jika belum tersedia"><span class="form-help">Hanya digunakan pada indikator jika terverifikasi.</span></div>
            <div class="form-field full-width"><label for="budget-source">Sumber data</label><input id="budget-source" name="source" type="text" maxlength="500" value="{{ old('source') }}" placeholder="Nama dokumen atau sumber yang dapat ditelusuri" required></div>
            <div class="form-field"><label for="budget-source-date">Tanggal sumber</label><input id="budget-source-date" name="source_date" type="date" value="{{ old('source_date') }}"></div>
            <div class="form-field"><label for="budget-status">Status verifikasi</label><select id="budget-status" name="verification_status" required><option value="unverified" @selected(old('verification_status', 'unverified') === 'unverified')>Belum diverifikasi</option><option value="verified" @selected(old('verification_status') === 'verified')>Terverifikasi</option><option value="unavailable" @selected(old('verification_status') === 'unavailable')>Belum tersedia</option></select></div>
            <p class="form-help full-width" data-budget-demo-note hidden>Data ini berasal dari dataset demo. Perubahan akan tetap diberi label demo dan tidak diperlakukan sebagai data resmi.</p>
        </div>
        <div class="dialog-footer"><button class="btn btn-secondary" type="button" data-dialog-close>Batal</button><button class="btn btn-primary" type="submit"><x-icon name="check" /> Simpan anggaran</button></div>
    </form>
</dialog>

<dialog id="delete-dialog" class="confirm-dialog" aria-labelledby="delete-dialog-title">
    <div class="dialog-body"><span class="delete-icon"><x-icon name="trash-2" /></span><h2 id="delete-dialog-title">Hapus data anggaran?</h2><p>Data <strong data-delete-description></strong> akan dihapus. Aktivitas penghapusan tetap dicatat dalam riwayat.</p></div><div class="dialog-footer"><button class="btn btn-secondary" type="button" data-dialog-close>Batal</button><button class="btn btn-danger" type="button" data-delete-confirm>Ya, hapus data</button></div>
</dialog>
@endsection
