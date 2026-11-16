@extends('layouts.app')
@section('title', 'Rencana Program')
@section('heading', 'Rencana Program Desa')
@section('content')
@php $statusClass = ['proposed' => 'badge-blue', 'planned' => 'badge-amber', 'ongoing' => 'badge-green', 'done' => 'badge-gray']; @endphp
<div class="page-head" data-reveal>
    <div>
        <nav class="crumbs" aria-label="Lokasi"><a href="{{ route('admin.index') }}">Dashboard pengelola</a><span>/</span><span>Rencana program</span></nav>
        <h1>Rencana Program</h1>
        <p>Program yang ditambahkan di sini langsung tampil di halaman publik dan bisa dinilai warga lewat voting.</p>
    </div>
    <div class="page-actions"><a class="btn btn-secondary" href="{{ route('programs.index') }}"><x-icon name="eye" /> Lihat halaman publik</a><a class="btn btn-primary" href="{{ route('admin.programs.create') }}"><x-icon name="plus" /> Tambah program</a></div>
</div>

<section class="card section-card" data-reveal>
    <div class="table-wrap">
        <table class="table stack">
            <thead><tr><th>Program</th><th>Tahun</th><th>Status</th><th>Voting</th><th>Hasil suara</th><th><span class="sr-only">Aksi</span></th></tr></thead>
            <tbody>
            @forelse($programs as $program)
                @php $results = $program->results(); @endphp
                <tr style="--row: {{ $loop->index }}">
                    <td class="cell-main"><span class="strong">{{ $program->title }}</span><span class="sub">{{ collect([$program->category, $program->location])->filter()->join(' · ') ?: '–' }}@if($program->estimated_budget !== null) · <x-money :value="$program->estimated_budget" />@endif</span></td>
                    <td data-label="Tahun">{{ $program->year }}</td>
                    <td data-label="Status"><span class="badge status {{ $statusClass[$program->status] ?? 'badge-gray' }}">{{ \App\Models\Program::STATUSES[$program->status] ?? $program->status }}</span></td>
                    <td data-label="Voting">@if($program->isVotingOpen())<span class="badge badge-green">Dibuka</span>@else<span class="badge badge-gray">Ditutup</span>@endif @if($program->voting_ends_at)<span class="sub">s.d. {{ $program->voting_ends_at->translatedFormat('j M Y') }}</span>@endif</td>
                    <td data-label="Hasil suara">@if($results['total'])<span class="strong">{{ $results['agree'] }} setuju ({{ number_format($results['agree_percent'], 1, ',', '.') }}%)</span><span class="sub">{{ $results['disagree'] }} tidak setuju ({{ number_format($results['disagree_percent'], 1, ',', '.') }}%) · {{ $results['total'] }} suara</span>@else<span class="muted">Belum ada suara</span>@endif</td>
                    <td class="cell-actions">
                        <div class="row-actions">
                            <a class="icon-btn" href="{{ route('admin.programs.edit', $program) }}" title="Ubah" aria-label="Ubah {{ $program->title }}"><x-icon name="pencil" /></a>
                            <form method="POST" action="{{ route('admin.programs.destroy', $program) }}" data-confirm-title="Hapus rencana program?" data-confirm="Program “{{ $program->title }}” beserta {{ $results['total'] }} suara warga akan dihapus. Catatan penghapusan tetap tersimpan di riwayat.">@csrf @method('DELETE')<button class="icon-btn icon-btn-danger" type="submit" title="Hapus" aria-label="Hapus {{ $program->title }}"><x-icon name="trash-2" /></button></form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6"><div class="empty"><x-icon name="clipboard-list" /><strong>Belum ada rencana program</strong><p>Klik Tambah program untuk membuat yang pertama.</p></div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <x-pagination :paginator="$programs" label="program" />
</section>
<x-confirm-dialog />
@endsection
