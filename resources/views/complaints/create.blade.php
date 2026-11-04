@extends('layouts.app')
@section('title', 'Pengaduan Warga')
@section('heading', 'Pengaduan & Masukan Warga')
@section('content')
<div class="page-head" data-reveal>
    <div>
        <nav class="crumbs" aria-label="Lokasi"><a href="{{ route('dashboard') }}">Dashboard</a><span>/</span><span>Pengaduan</span></nav>
        <h1>Pengaduan & Masukan Warga</h1>
        <p>Sampaikan pertanyaan, saran, atau pengaduan tentang anggaran desa. Tidak perlu membuat akun.</p>
    </div>
</div>

<section class="grid grid-7-5">
    <article class="card" data-reveal>
        <header class="card-head"><div><h2>Kirim pesan</h2><p>Pesan dibaca perangkat desa dan dibalas melalui halaman cek status.</p></div></header>
        <form class="card-body" method="POST" action="{{ route('complaints.store') }}" style="display: grid; gap: 18px">
            @csrf
            <fieldset class="field" style="border: 0; margin: 0; padding: 0">
                <legend class="label" style="font-size: 13.5px; font-weight: 600; margin-bottom: 6px">Jenis pesan</legend>
                <div class="radio-cards">
                    @foreach(['question' => ['Pertanyaan', 'Ingin tahu tentang anggaran'], 'suggestion' => ['Saran', 'Usulan perbaikan'], 'complaint' => ['Pengaduan', 'Melaporkan masalah']] as $value => [$label, $hint])
                        <label class="radio-card"><input type="radio" name="topic" value="{{ $value }}" @checked(old('topic', 'question') === $value) required><strong>{{ $label }}</strong><span>{{ $hint }}</span></label>
                    @endforeach
                </div>
            </fieldset>
            <div class="field">
                <label for="complaint-activity">Kegiatan terkait <span class="optional">opsional</span></label>
                <select class="select" id="complaint-activity" name="budget_line_id">
                    <option value="">Tidak terkait kegiatan tertentu</option>
                    @foreach($activities->groupBy('category') as $category => $items)
                        <optgroup label="{{ $category }}">@foreach($items as $activity)<option value="{{ $activity->id }}" @selected((int) old('budget_line_id', $selected) === $activity->id)>{{ $activity->description }}</option>@endforeach</optgroup>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="complaint-message">Isi pesan</label>
                <textarea class="textarea" id="complaint-message" name="message" minlength="15" maxlength="2000" required placeholder="Contoh: Kapan pembangunan jalan rabat beton di RT 02 dimulai?">{{ old('message') }}</textarea>
                @error('message')<span class="field-error">{{ $message }}</span>@enderror
            </div>
            <div class="form-grid">
                <div class="field"><label for="complaint-name">Nama <span class="optional">opsional</span></label><input class="input" id="complaint-name" name="name" maxlength="100" value="{{ old('name') }}" placeholder="Boleh dikosongkan"></div>
                <div class="field"><label for="complaint-contact">No. HP / email <span class="optional">opsional</span></label><input class="input" id="complaint-contact" name="contact" maxlength="100" value="{{ old('contact') }}" placeholder="Hanya dilihat perangkat desa"></div>
            </div>
            <div class="honeypot" aria-hidden="true"><label for="complaint-website">Jangan diisi</label><input id="complaint-website" name="website" tabindex="-1" autocomplete="off"></div>
            <div class="form-actions"><span class="hint">Nama dan kontak tidak ditampilkan ke publik.</span><button class="btn btn-primary" type="submit"><x-icon name="send" /> Kirim pesan</button></div>
        </form>
    </article>
    <div style="display: grid; gap: 20px; align-content: start">
        <article class="card" data-reveal>
            <header class="card-head"><div><h2>Cek status pesan</h2><p>Masukkan nomor tiket yang didapat setelah mengirim.</p></div></header>
            <form class="card-body" method="GET" action="{{ route('complaints.show') }}" style="display: grid; gap: 12px">
                <label class="sr-only" for="ticket">Nomor tiket</label>
                <input class="input" id="ticket" name="tiket" placeholder="Contoh: ADU-2611-H8NT" required style="text-transform: uppercase; letter-spacing: 1px">
                <button class="btn btn-secondary" type="submit"><x-icon name="search" /> Cek status</button>
            </form>
        </article>
        <article class="card" data-reveal>
            <header class="card-head"><div><h2>Alur tindak lanjut</h2></div></header>
            <ul class="timeline">
                <li><span class="timeline-dot"><x-icon name="send" /></span><div><strong>Baru</strong><p>Pesan diterima dan masuk ke kotak pengaduan perangkat desa.</p></div></li>
                <li><span class="timeline-dot"><x-icon name="loader" /></span><div><strong>Diproses</strong><p>Perangkat desa memeriksa dan berkoordinasi.</p></div></li>
                <li><span class="timeline-dot"><x-icon name="circle-check" /></span><div><strong>Selesai</strong><p>Tanggapan ditulis dan bisa dibaca dengan nomor tiket.</p></div></li>
            </ul>
        </article>
    </div>
</section>
@endsection
