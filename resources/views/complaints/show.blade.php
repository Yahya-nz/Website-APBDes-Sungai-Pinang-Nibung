@extends('layouts.app')
@section('title', 'Status Pengaduan')
@section('heading', 'Status Pengaduan')
@section('content')
@php
    $order = array_keys(\App\Models\Complaint::STATUSES);
    $statusClass = ['new' => 'badge-red', 'in_progress' => 'badge-amber', 'resolved' => 'badge-green'];
    $filterLink = fn (array $params) => route('complaints.show', array_filter(array_merge(['tiket' => $ticket ?: null, 'status' => $status, 'jenis' => $topic], $params))).'#tiket-saya';
    $recoveryErrors = $errors->getBag('recovery');
@endphp
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
        <header class="card-head"><div><h2>Simpan nomor tiket</h2><p>Nomor ini dipakai untuk melihat tanggapan, karena pesan dikirim tanpa akun.</p></div></header>
        <div class="card-body" style="display: grid; gap: 12px; justify-items: start">
            <span class="ticket"><x-icon name="ticket" /> {{ $complaint->ticket }}</span>
            <div class="page-actions">
                <a class="btn btn-primary btn-sm" href="{{ route('complaints.receipt', ['tiket' => $complaint->ticket]) }}"><x-icon name="download" /> Unduh bukti tiket</a>
                <button class="btn btn-secondary btn-sm" type="button" data-copy="{{ $complaint->ticket }}"><x-icon name="copy" /> <span data-copy-label>Salin nomor</span></button>
            </div>
            <p class="hint">Tiket ini juga otomatis tersimpan di daftar <a class="link" href="#tiket-saya">Tiket saya</a> pada perangkat ini. Nama dan kontak Anda tidak ditampilkan di sini.</p>
        </div>
        <footer class="card-foot"><a class="link" href="{{ route('complaints.create') }}">Kirim pesan lain <x-icon name="arrow-right" /></a></footer>
    </article>
</section>
@elseif($ticket !== '')
    <div class="card section-card" data-reveal><div class="empty"><x-icon name="search-x" /><strong>Tiket {{ $ticket }} tidak ditemukan</strong><p>Periksa kembali penulisan nomor tiket, misalnya ADU-2611-H8NT, atau pulihkan lewat kontak dan PIN di bawah.</p></div></div>
@endif

<section class="grid grid-7-5">
    <article class="card" id="tiket-saya" data-reveal>
        <header class="card-head"><div><h2>Tiket saya <span class="badge badge-blue">{{ $savedTotal }}</span></h2><p>Tiket yang pernah dikirim, dibuka, atau dipulihkan di perangkat ini.</p></div></header>
        <nav class="tabs" aria-label="Filter status tiket">
            <a class="tab {{ ! $status ? 'is-active' : '' }}" href="{{ $filterLink(['status' => null]) }}">Semua <span>{{ $savedTotal }}</span></a>
            @foreach(\App\Models\Complaint::STATUSES as $key => $label)
                <a class="tab {{ $status === $key ? 'is-active' : '' }}" href="{{ $filterLink(['status' => $key]) }}">{{ $label }} <span>{{ $savedCounts[$key] ?? 0 }}</span></a>
            @endforeach
        </nav>
        <form class="filters" method="GET" action="{{ route('complaints.show') }}#tiket-saya">
            @if($ticket)<input type="hidden" name="tiket" value="{{ $ticket }}">@endif
            @if($status)<input type="hidden" name="status" value="{{ $status }}">@endif
            <label class="sr-only" for="filter-topic">Jenis pesan</label>
            <select class="select" id="filter-topic" name="jenis" data-auto-submit><option value="">Semua jenis pesan</option>@foreach(\App\Models\Complaint::TOPICS as $key => $label)<option value="{{ $key }}" @selected($topic === $key)>{{ $label }}</option>@endforeach</select>
            <noscript><button class="btn btn-secondary" type="submit">Terapkan</button></noscript>
            @if($status || $topic)<a class="btn btn-ghost" href="{{ $filterLink(['status' => null, 'jenis' => null]) }}">Hapus filter</a>@endif
        </form>
        @forelse($saved as $item)
            <div class="list-row saved-ticket">
                <span class="list-icon tone-blue"><x-icon name="ticket" /></span>
                <div><a class="list-title" href="{{ route('complaints.show', ['tiket' => $item->ticket]) }}">{{ $item->ticket }} · {{ \App\Models\Complaint::TOPICS[$item->topic] }}</a><p class="list-meta">Dikirim {{ $item->created_at->timezone('Asia/Jakarta')->translatedFormat('j M Y') }} · {{ $item->response ? 'sudah ada tanggapan' : 'belum ada tanggapan' }}</p></div>
                <div class="row-actions">
                    <span class="badge status {{ $statusClass[$item->status] ?? 'badge-gray' }}">{{ \App\Models\Complaint::STATUSES[$item->status] ?? $item->status }}</span>
                    <form method="POST" action="{{ route('complaints.forget', $item->ticket) }}">@csrf @method('DELETE')<button class="icon-btn" type="submit" title="Hapus dari daftar perangkat ini" aria-label="Hapus {{ $item->ticket }} dari daftar"><x-icon name="x" /></button></form>
                </div>
            </div>
        @empty
            <div class="empty"><x-icon name="ticket" /><strong>{{ $savedTotal ? 'Tidak ada tiket dengan filter ini' : 'Belum ada tiket tersimpan' }}</strong><p>{{ $savedTotal ? 'Coba pilih status atau jenis lain.' : 'Tiket otomatis tersimpan setelah Anda mengirim atau membuka sebuah tiket.' }}</p></div>
        @endforelse
        <footer class="card-foot"><span>Daftar ini hanya tersimpan di browser perangkat ini.</span><a class="link" href="{{ route('complaints.create') }}">Kirim pesan baru <x-icon name="arrow-right" /></a></footer>
    </article>

    <article class="card" id="pulihkan" data-reveal>
        <header class="card-head"><div><h2>Lupa nomor tiket?</h2><p>Pulihkan dengan No. HP / email dan PIN yang Anda isi saat mengirim pesan.</p></div></header>
        <form class="card-body form-stack" method="POST" action="{{ route('complaints.recover') }}">
            @csrf
            @if($recoveryErrors->any())<div class="alert" style="margin: 0"><x-icon name="circle-alert" /><div>{{ $recoveryErrors->first() }}</div></div>@endif
            <div class="field"><label for="recover-contact">No. HP / email</label><input class="input" id="recover-contact" name="contact" maxlength="100" value="{{ old('contact') }}" required autocomplete="off" placeholder="Sama seperti saat mengirim">@error('contact')<span class="field-error">{{ $message }}</span>@enderror</div>
            <div class="field"><label for="recover-pin">PIN pemulihan</label><input class="input" id="recover-pin" type="password" name="recovery_pin" inputmode="numeric" pattern="[0-9]{4,6}" minlength="4" maxlength="6" required autocomplete="off" placeholder="4–6 angka">@error('recovery_pin')<span class="field-error">{{ $message }}</span>@enderror</div>
            <div class="form-actions"><span class="hint">Penulisan nomor HP bebas, misalnya 0812… atau +62 812….</span><button class="btn btn-primary" type="submit"><x-icon name="key-round" /> Pulihkan tiket</button></div>
        </form>
        <footer class="card-foot" style="display: block; line-height: 1.6">Tidak mengisi PIN saat mengirim? Hubungi kantor desa dengan menyebutkan nama, kontak, dan isi pesan agar perangkat desa membantu mencarikan nomor tiketnya.</footer>
    </article>
</section>
@endsection
