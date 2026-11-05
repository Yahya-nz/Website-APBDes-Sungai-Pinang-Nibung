@extends('layouts.app')
@section('title', 'Riwayat Aktivitas')
@section('heading', 'Riwayat Aktivitas')
@section('content')
<div class="page-head" data-reveal>
    <div>
        <nav class="crumbs" aria-label="Lokasi"><a href="{{ route('admin.index') }}">Dashboard pengelola</a><span>/</span><span>Riwayat aktivitas</span></nav>
        <h1>Riwayat Aktivitas</h1>
        <p>Semua perubahan oleh perangkat desa, dari yang terbaru. Riwayat tidak dapat diubah atau dihapus dari halaman ini.</p>
    </div>
</div>

<section class="card section-card" data-reveal>
    <nav class="tabs" aria-label="Jenis aktivitas">
        <a class="tab {{ ! $action ? 'is-active' : '' }}" href="{{ route('admin.activities.index') }}" @if(! $action) aria-current="page" @endif>Semua <span>{{ $counts->sum() }}</span></a>
        @foreach(\App\Models\ActivityLog::ACTIONS as $key => [$label])
            @if(($counts[$key] ?? 0) > 0 || $action === $key)
                <a class="tab {{ $action === $key ? 'is-active' : '' }}" href="{{ route('admin.activities.index', ['aksi' => $key]) }}" @if($action === $key) aria-current="page" @endif>{{ $label }} <span>{{ $counts[$key] ?? 0 }}</span></a>
            @endif
        @endforeach
    </nav>
    <ul class="timeline timeline-page">
        @forelse($logs as $log)
            <li><span class="timeline-dot"><x-icon :name="$log->icon()" /></span><div><strong>{{ $log->label() }}</strong><p>{{ $log->description }}</p><span>{{ $log->user?->name ?? 'Sistem' }} · {{ $log->created_at->timezone('Asia/Jakarta')->translatedFormat('j M Y, H.i') }} WIB @if($log->year)· TA {{ $log->year }}@endif</span></div></li>
        @empty
            <li><div class="empty" style="grid-column: 1 / -1"><x-icon name="history" /><p>Belum ada aktivitas{{ $action ? ' untuk jenis ini' : '' }}.</p></div></li>
        @endforelse
    </ul>
    @if($logs->hasPages())
        <div class="pagination"><span>Halaman {{ $logs->currentPage() }} dari {{ $logs->lastPage() }} · {{ $logs->total() }} aktivitas</span><nav>@if($logs->previousPageUrl())<a class="btn btn-secondary btn-sm" href="{{ $logs->previousPageUrl() }}"><x-icon name="chevron-left" /> Sebelumnya</a>@endif @if($logs->nextPageUrl())<a class="btn btn-secondary btn-sm" href="{{ $logs->nextPageUrl() }}">Berikutnya <x-icon name="chevron-right" /></a>@endif</nav></div>
    @endif
</section>
@endsection
