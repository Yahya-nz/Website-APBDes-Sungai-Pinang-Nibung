@extends('layouts.app')
@section('title', 'Pengaduan Masuk')
@section('heading', 'Pengaduan Masuk')
@section('content')
@php $statusClass = ['new' => 'badge-red', 'in_progress' => 'badge-amber', 'resolved' => 'badge-green']; @endphp
<div class="page-head" data-reveal>
    <div>
        <nav class="crumbs" aria-label="Lokasi"><span>Perangkat desa</span><span>/</span><span>Pengaduan masuk</span></nav>
        <h1>Pengaduan Masuk</h1>
        <p>Pesan dari warga. Tanggapan yang ditulis di sini bisa dibaca warga dengan nomor tiketnya.</p>
    </div>
</div>

<section class="card" data-reveal style="margin-bottom: 20px">
    <nav class="tabs" aria-label="Status pengaduan">
        <a class="tab {{ ! $status ? 'is-active' : '' }}" href="{{ route('admin.complaints.index') }}">Semua <span>{{ $counts->sum() }}</span></a>
        @foreach(\App\Models\Complaint::STATUSES as $key => $label)
            <a class="tab {{ $status === $key ? 'is-active' : '' }}" href="{{ route('admin.complaints.index', ['status' => $key]) }}">{{ $label }} <span>{{ $counts[$key] ?? 0 }}</span></a>
        @endforeach
    </nav>
    @forelse($complaints as $complaint)
        <article style="padding: 18px 20px; border-bottom: 1px solid var(--line-2)" data-reveal>
            <div style="display: flex; flex-wrap: wrap; gap: 8px 14px; align-items: center; margin-bottom: 10px">
                <strong style="font-size: 15px">{{ \App\Models\Complaint::TOPICS[$complaint->topic] }}</strong>
                <span class="badge badge-blue">{{ $complaint->ticket }}</span>
                <span class="badge status {{ $statusClass[$complaint->status] }}">{{ \App\Models\Complaint::STATUSES[$complaint->status] }}</span>
                <span class="hint" style="margin-left: auto">{{ $complaint->created_at->timezone('Asia/Jakarta')->translatedFormat('j M Y, H.i') }} WIB</span>
            </div>
            <p class="hint" style="margin-bottom: 8px">Dari: {{ $complaint->name ?: 'Tanpa nama' }}@if($complaint->contact) · {{ $complaint->contact }}@endif @if($complaint->budgetLine)· Kegiatan: {{ $complaint->budgetLine->description }}@endif</p>
            <div class="message-box" style="margin-bottom: 12px">{{ $complaint->message }}</div>
            <form method="POST" action="{{ route('admin.complaints.update', $complaint) }}" class="form-grid" style="grid-template-columns: 200px 1fr auto; align-items: end">
                @csrf @method('PATCH')
                @if($status)<input type="hidden" name="status_filter" value="{{ $status }}">@endif
                <div class="field"><label for="status-{{ $complaint->id }}">Status</label><select class="select" id="status-{{ $complaint->id }}" name="status">@foreach(\App\Models\Complaint::STATUSES as $key => $label)<option value="{{ $key }}" @selected($complaint->status === $key)>{{ $label }}</option>@endforeach</select></div>
                <div class="field"><label for="response-{{ $complaint->id }}">Tanggapan untuk warga</label><input class="input" id="response-{{ $complaint->id }}" name="response" maxlength="2000" value="{{ $complaint->response }}" placeholder="Tulis tanggapan singkat"></div>
                <button class="btn btn-primary" type="submit"><x-icon name="send" /> Simpan</button>
            </form>
            @if($complaint->responded_at)<p class="hint" style="margin-top: 8px">Ditanggapi {{ $complaint->responded_at->timezone('Asia/Jakarta')->translatedFormat('j M Y, H.i') }} WIB oleh {{ $complaint->responder?->name ?? 'perangkat desa' }}</p>@endif
        </article>
    @empty
        <div class="empty"><x-icon name="inbox" /><strong>Tidak ada pengaduan</strong><p>Belum ada pesan dengan status ini.</p></div>
    @endforelse
    @if($complaints->hasPages())
        <div class="pagination"><span>Halaman {{ $complaints->currentPage() }} dari {{ $complaints->lastPage() }}</span><nav>@if($complaints->previousPageUrl())<a class="btn btn-secondary btn-sm" href="{{ $complaints->previousPageUrl() }}">Sebelumnya</a>@endif @if($complaints->nextPageUrl())<a class="btn btn-secondary btn-sm" href="{{ $complaints->nextPageUrl() }}">Berikutnya</a>@endif</nav></div>
    @endif
</section>
@endsection
