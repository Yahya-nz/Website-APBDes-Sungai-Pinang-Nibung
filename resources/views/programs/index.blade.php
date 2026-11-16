@extends('layouts.app')
@section('title', 'Rencana Program Desa')
@section('heading', 'Rencana Program Desa')
@section('content')
@php
    $statusClass = ['proposed' => 'badge-blue', 'planned' => 'badge-amber', 'ongoing' => 'badge-green', 'done' => 'badge-gray'];
@endphp
<div class="page-head" data-reveal>
    <div>
        <nav class="crumbs" aria-label="Lokasi"><a href="{{ route('dashboard') }}">Dashboard</a><span>/</span><span>Rencana program</span></nav>
        <h1>Rencana Program Desa</h1>
        <p>Program dan kegiatan yang sedang direncanakan pemerintah desa. Sampaikan pendapat Anda: setuju atau tidak setuju.</p>
    </div>
</div>

<div class="notice notice-info" data-reveal><x-icon name="info" /><span>Voting tidak perlu login. Setiap perangkat punya satu suara per program, dan pilihan bisa diubah selama voting masih dibuka. Hasil voting menjadi bahan pertimbangan pemerintah desa, bukan keputusan akhir.</span></div>

@error('choice')<div class="alert" role="alert"><x-icon name="circle-alert" /><div>{{ $message }}</div></div>@enderror

<section class="card section-card" data-reveal>
    <nav class="tabs" aria-label="Status program">
        <a class="tab {{ ! $status ? 'is-active' : '' }}" href="{{ route('programs.index') }}">Semua <span>{{ $counts->sum() }}</span></a>
        @foreach(\App\Models\Program::STATUSES as $key => $label)
            <a class="tab {{ $status === $key ? 'is-active' : '' }}" href="{{ route('programs.index', ['status' => $key]) }}">{{ $label }} <span>{{ $counts[$key] ?? 0 }}</span></a>
        @endforeach
    </nav>
</section>

<section class="grid grid-2">
    @forelse($programs as $program)
        @php $results = $program->results(); $mine = $myVotes[$program->id] ?? null; $open = $program->isVotingOpen(); @endphp
        <article class="card program-card" id="program-{{ $program->id }}" data-reveal>
            <header class="card-head">
                <div>
                    <h2>{{ $program->title }}</h2>
                    <p>{{ collect([$program->category, $program->location, 'Rencana '.$program->year])->filter()->join(' · ') }}</p>
                </div>
                <span class="badge status {{ $statusClass[$program->status] ?? 'badge-gray' }}">{{ \App\Models\Program::STATUSES[$program->status] ?? $program->status }}</span>
            </header>
            <div class="card-body program-body">
                <p class="program-text">{{ $program->description }}</p>
                @if($program->estimated_budget !== null)<p class="program-budget"><span>Perkiraan anggaran</span> <x-money :value="$program->estimated_budget" /></p>@endif
                <x-vote-result :results="$results" />
            </div>
            <footer class="card-foot program-foot">
                @if($open)
                    <form class="vote-form" method="POST" action="{{ route('programs.vote', $program) }}">
                        @csrf
                        @if($status)<input type="hidden" name="status_filter" value="{{ $status }}">@endif
                        <button class="btn btn-sm vote-btn {{ $mine === 'agree' ? 'is-chosen' : '' }}" type="submit" name="choice" value="agree" aria-pressed="{{ $mine === 'agree' ? 'true' : 'false' }}"><x-icon name="thumbs-up" /> Setuju</button>
                        <button class="btn btn-sm vote-btn is-against {{ $mine === 'disagree' ? 'is-chosen' : '' }}" type="submit" name="choice" value="disagree" aria-pressed="{{ $mine === 'disagree' ? 'true' : 'false' }}"><x-icon name="thumbs-down" /> Tidak setuju</button>
                    </form>
                    <span>{{ $mine ? 'Pilihan Anda: '.\App\Models\Program::CHOICES[$mine] : 'Anda belum memilih' }}@if($program->voting_ends_at) · dibuka s.d. {{ $program->voting_ends_at->translatedFormat('j M Y') }}@endif</span>
                @else
                    <span><x-icon name="lock-keyhole" class="icon" style="display: inline; width: 14px; height: 14px; vertical-align: -2px" /> Voting ditutup{{ $mine ? ' · pilihan Anda: '.\App\Models\Program::CHOICES[$mine] : '' }}</span>
                @endif
            </footer>
        </article>
    @empty
        <div class="card" style="grid-column: 1 / -1"><div class="empty"><x-icon name="clipboard-list" /><strong>Belum ada rencana program</strong><p>{{ $status ? 'Tidak ada program dengan status ini.' : 'Rencana program desa akan tampil di sini setelah ditambahkan perangkat desa.' }}</p></div></div>
    @endforelse
</section>
@if($programs->hasPages())<section class="card section-card"><x-pagination :paginator="$programs" label="program" /></section>@endif
@endsection
