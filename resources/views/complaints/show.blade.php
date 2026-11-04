@extends('layouts.app')
@section('title', 'Status Pengaduan')
@section('heading', 'Status Pengaduan')
@section('content')
@php $order = array_keys(\App\Models\Complaint::STATUSES); @endphp
<div class="page-head" data-reveal>
    <div>
        <nav class="crumbs" aria-label="Lokasi"><a href="{{ route('complaints.create') }}">Pengaduan</a><span>/</span><span>Cek status</span></nav>
        <h1>Status Pengaduan</h1>
        <p>Pantau tindak lanjut pesan Anda dengan nomor tiket.</p>
    </div>
    <form class="page-actions" method="GET" action="{{ route('complaints.show') }}"><label class="sr-only" for="ticket-check">Nomor tiket</label><input class="input" id="ticket-check" name="tiket" value="{{ $ticket }}" placeholder="Nomor tiket" style="width: 220px; text-transform: uppercase"><button class="btn btn-secondary" type="submit"><x-icon name="search" /> Cek</button></form>
</div>

@if($complaint)
<section class="grid grid-7-5">
    <article class="card" data-reveal>
        <header class="card-head"><div><h2>{{ \App\Models\Complaint::TOPICS[$complaint->topic] }}</h2><p>Dikirim {{ $complaint->created_at->timezone('Asia/Jakarta')->translatedFormat('j F Y, H.i') }} WIB @if($complaint->budgetLine)· {{ $complaint->budgetLine->description }}@endif</p></div><span class="ticket">{{ $complaint->ticket }}</span></header>
        <div class="card-body" style="display: grid; gap: 16px">
            <ol class="status-steps">
                @foreach(\App\Models\Complaint::STATUSES as $key => $label)
                    @php $position = array_search($key, $order); $current = array_search($complaint->status, $order); @endphp
                    <li class="{{ $position < $current ? 'is-done' : ($position === $current ? 'is-current' : '') }}">{{ $label }}</li>
                @endforeach
            </ol>
            <div><p class="hint" style="margin-bottom: 6px">Pesan Anda</p><div class="message-box">{{ $complaint->message }}</div></div>
            @if($complaint->response)
                <div><p class="hint" style="margin-bottom: 6px">Tanggapan perangkat desa · {{ $complaint->responded_at?->timezone('Asia/Jakarta')->translatedFormat('j F Y') }}</p><div class="response-box">{{ $complaint->response }}</div></div>
            @else
                <div class="notice notice-info" style="margin: 0"><x-icon name="clock" /><p>Belum ada tanggapan. Perangkat desa akan membalas setelah pesan diperiksa.</p></div>
            @endif
        </div>
    </article>
    <article class="card" data-reveal>
        <header class="card-head"><div><h2>Simpan nomor tiket</h2><p>Nomor ini satu-satunya cara melihat tanggapan, karena pesan dikirim tanpa akun.</p></div></header>
        <div class="card-body"><span class="ticket"><x-icon name="ticket" /> {{ $complaint->ticket }}</span><p class="hint" style="margin-top: 12px">Tandai halaman ini atau tulis nomornya. Nama dan kontak Anda tidak ditampilkan di sini.</p></div>
        <footer class="card-foot"><a class="link" href="{{ route('complaints.create') }}">Kirim pesan lain <x-icon name="arrow-right" /></a></footer>
    </article>
</section>
@elseif($ticket !== '')
    <div class="card" data-reveal><div class="empty"><x-icon name="search-x" /><strong>Tiket {{ $ticket }} tidak ditemukan</strong><p>Periksa kembali penulisan nomor tiket, misalnya ADU-2611-H8NT.</p><a class="btn btn-primary btn-sm" href="{{ route('complaints.create') }}">Kirim pesan baru</a></div></div>
@else
    <div class="card" data-reveal><div class="empty"><x-icon name="ticket" /><strong>Masukkan nomor tiket</strong><p>Nomor tiket diberikan setelah Anda mengirim pesan.</p></div></div>
@endif
@endsection
