@extends('layouts.app')
@section('title', 'Pengaduan Masuk')
@section('heading', 'Pengaduan Masuk')
@section('content')
@php
    $statusClass = ['new' => 'badge-red', 'in_progress' => 'badge-amber', 'resolved' => 'badge-green'];
    $topics = \App\Models\Complaint::TOPICS;
    $statuses = \App\Models\Complaint::STATUSES;
    $tabLink = fn (?string $key) => route('admin.complaints.index', array_filter(['status' => $key, 'q' => $search ?: null]));
    $reopen = old('_complaint_form') ? $complaints->firstWhere('id', (int) old('_complaint_id')) : null;
    $detail = fn ($complaint) => [
        'id' => $complaint->id,
        'ticket' => $complaint->ticket,
        'topic' => $topics[$complaint->topic] ?? $complaint->topic,
        'name' => $complaint->name ?: 'Tanpa nama',
        'contact' => $complaint->contact,
        'activity' => $complaint->budgetLine?->description,
        'message' => $complaint->message,
        'status' => $complaint->status,
        'response' => $complaint->response,
        'sent' => $complaint->created_at->timezone('Asia/Jakarta')->translatedFormat('j M Y, H.i').' WIB',
        'responded' => $complaint->responded_at ? 'Ditanggapi '.$complaint->responded_at->timezone('Asia/Jakarta')->translatedFormat('j M Y, H.i').' WIB oleh '.($complaint->responder?->name ?? 'perangkat desa') : null,
        'url' => route('admin.complaints.update', $complaint),
    ];
@endphp
<div class="page-head" data-reveal>
    <div>
        <nav class="crumbs" aria-label="Lokasi"><a href="{{ route('admin.index') }}">Dashboard pengelola</a><span>/</span><span>Pengaduan masuk</span></nav>
        <h1>Pengaduan Masuk</h1>
        <p>Pesan dari warga. Tanggapan yang ditulis di sini bisa dibaca warga dengan nomor tiketnya.</p>
    </div>
</div>

<section class="card section-card" data-reveal>
    <nav class="tabs" aria-label="Status pengaduan">
        <a class="tab {{ ! $status ? 'is-active' : '' }}" href="{{ $tabLink(null) }}">Semua <span>{{ $counts->sum() }}</span></a>
        @foreach($statuses as $key => $label)
            <a class="tab {{ $status === $key ? 'is-active' : '' }}" href="{{ $tabLink($key) }}">{{ $label }} <span>{{ $counts[$key] ?? 0 }}</span></a>
        @endforeach
    </nav>
    <form class="filters" method="GET" action="{{ route('admin.complaints.index') }}">
        @if($status)<input type="hidden" name="status" value="{{ $status }}">@endif
        <div class="search"><x-icon name="search" /><label class="sr-only" for="complaint-search">Cari nama pelapor atau nomor tiket</label><input class="input" id="complaint-search" type="search" name="q" value="{{ $search }}" maxlength="100" placeholder="Cari nama pelapor atau nomor tiket (tekan /)" data-search></div>
        <button class="btn btn-primary" type="submit">Cari</button>
    </form>
    @if($search !== '')
        <div class="filter-summary"><span><strong>{{ $complaints->total() }}</strong> pengaduan cocok dengan “{{ $search }}”</span><a href="{{ route('admin.complaints.index', array_filter(['status' => $status])) }}"><x-icon name="x" /> Hapus pencarian</a></div>
    @endif
    <div class="table-wrap">
        <table class="table stack complaint-table">
            <thead><tr><th>Tiket</th><th>Pelapor</th><th>Jenis</th><th>Pesan</th><th>Status</th><th><span class="sr-only">Aksi</span></th></tr></thead>
            <tbody>
            @forelse($complaints as $complaint)
                <tr style="--row: {{ $loop->index }}">
                    <td class="cell-main"><span class="strong mono-ticket">{{ $complaint->ticket }}</span><span class="sub">{{ $complaint->created_at->timezone('Asia/Jakarta')->translatedFormat('j M Y, H.i') }}</span></td>
                    <td data-label="Pelapor"><span class="strong">{{ $complaint->name ?: 'Tanpa nama' }}</span>@if($complaint->contact)<span class="sub">{{ $complaint->contact }}</span>@endif</td>
                    <td data-label="Jenis">{{ $topics[$complaint->topic] ?? $complaint->topic }}@if($complaint->budgetLine)<span class="sub">{{ \Illuminate\Support\Str::limit($complaint->budgetLine->description, 40) }}</span>@endif</td>
                    <td data-label="Pesan" class="cell-message">{{ \Illuminate\Support\Str::limit($complaint->message, 90) }}</td>
                    <td data-label="Status"><span class="badge status {{ $statusClass[$complaint->status] ?? 'badge-gray' }}">{{ $statuses[$complaint->status] ?? $complaint->status }}</span>@if($complaint->response)<span class="sub">ada tanggapan</span>@endif</td>
                    <td class="cell-actions"><button class="btn btn-secondary btn-sm" type="button" data-complaint-open="{{ json_encode($detail($complaint)) }}"><x-icon name="message-square-text" /> Tanggapi</button></td>
                </tr>
            @empty
                <tr><td colspan="6"><div class="empty"><x-icon name="inbox" /><strong>Tidak ada pengaduan</strong><p>{{ $search !== '' ? 'Tidak ada pelapor atau tiket yang cocok dengan pencarian.' : 'Belum ada pesan dengan status ini.' }}</p></div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <x-pagination :paginator="$complaints" label="pengaduan" />
</section>

<dialog id="complaint-dialog" class="dialog" aria-labelledby="complaint-dialog-title" @if($reopen) data-open-on-load @endif>
    <form method="POST" action="{{ $reopen ? route('admin.complaints.update', $reopen) : route('admin.complaints.index') }}" data-complaint-form>
        <header class="dialog-head"><h2 id="complaint-dialog-title"><span data-field="topic">{{ $reopen ? ($topics[$reopen->topic] ?? '') : 'Pengaduan' }}</span> · <span data-field="ticket">{{ $reopen?->ticket }}</span></h2><button class="icon-btn" type="button" data-dialog-close aria-label="Tutup"><x-icon name="x" /></button></header>
        @csrf @method('PATCH')
        <input type="hidden" name="_complaint_form" value="1">
        <input type="hidden" name="_complaint_id" value="{{ $reopen?->id }}">
        @if($status)<input type="hidden" name="status_filter" value="{{ $status }}">@endif
        @if($search !== '')<input type="hidden" name="q_filter" value="{{ $search }}">@endif
        @if($complaints->currentPage() > 1)<input type="hidden" name="page_filter" value="{{ $complaints->currentPage() }}">@endif
        <div class="dialog-body" style="display: grid; gap: 14px">
            @if($reopen && $errors->any())<div class="alert" style="margin: 0"><x-icon name="circle-alert" /><div>{{ $errors->first() }}</div></div>@endif
            <dl class="dl complaint-meta">
                <dt>Pelapor</dt><dd><span data-field="name">{{ $reopen ? ($reopen->name ?: 'Tanpa nama') : '' }}</span> <span class="muted" data-field="contact">{{ $reopen?->contact }}</span></dd>
                <dt>Dikirim</dt><dd data-field="sent">{{ $reopen?->created_at->timezone('Asia/Jakarta')->translatedFormat('j M Y, H.i') }}</dd>
                <dt>Kegiatan</dt><dd data-field="activity">{{ $reopen?->budgetLine?->description ?? '–' }}</dd>
            </dl>
            <div><p class="hint" style="margin-bottom: 6px">Isi pesan</p><div class="message-box" data-field="message">{{ $reopen?->message }}</div></div>
            <div class="field"><label for="complaint-status">Status</label><select class="select" id="complaint-status" name="status">@foreach($statuses as $key => $label)<option value="{{ $key }}" @selected(old('status', $reopen?->status) === $key)>{{ $label }}</option>@endforeach</select></div>
            <div class="field"><label for="complaint-response">Tanggapan untuk warga</label><textarea class="textarea" id="complaint-response" name="response" maxlength="2000" placeholder="Tulis tanggapan yang bisa dibaca warga dengan nomor tiketnya">{{ old('response', $reopen?->response) }}</textarea><span class="hint" data-field="responded">{{ $reopen && $reopen->responded_at ? 'Ditanggapi '.$reopen->responded_at->timezone('Asia/Jakarta')->translatedFormat('j M Y, H.i').' WIB' : '' }}</span></div>
        </div>
        <footer class="dialog-foot"><button class="btn btn-secondary" type="button" data-dialog-close>Batal</button><button class="btn btn-primary" type="submit"><x-icon name="send" /> Simpan tanggapan</button></footer>
    </form>
</dialog>
@endsection
