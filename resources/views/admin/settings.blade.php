@extends('layouts.app')
@section('title', 'Pengaturan')
@section('heading', 'Pengaturan Akun')
@section('content')
<div class="page-head" data-reveal>
    <div>
        <nav class="crumbs" aria-label="Lokasi"><span>Perangkat desa</span><span>/</span><span>Pengaturan</span></nav>
        <h1>Pengaturan Akun</h1>
        <p>Data akun perangkat desa yang sedang masuk.</p>
    </div>
</div>
<section class="grid grid-2">
    <article class="card" data-reveal>
        <header class="card-head"><div><h2>Akun</h2></div></header>
        <div class="card-body"><dl class="dl"><dt>Nama</dt><dd>{{ $user->name }}</dd><dt>Email</dt><dd>{{ $user->email }}</dd><dt>Peran</dt><dd>Perangkat desa (pengelola data)</dd><dt>Bergabung</dt><dd>{{ $user->created_at?->translatedFormat('j F Y') }}</dd></dl></div>
    </article>
    <article class="card" data-reveal>
        <header class="card-head"><div><h2>Ganti kata sandi</h2><p>Minimal 10 karakter, berisi huruf dan angka.</p></div></header>
        <form class="card-body" method="POST" action="{{ route('admin.settings.password') }}" style="display: grid; gap: 14px">
            @csrf @method('PUT')
            <div class="field"><label for="current-password">Kata sandi saat ini</label><input class="input" id="current-password" type="password" name="current_password" autocomplete="current-password" required>@error('current_password')<span class="field-error">{{ $message }}</span>@enderror</div>
            <div class="field"><label for="new-password">Kata sandi baru</label><input class="input" id="new-password" type="password" name="password" autocomplete="new-password" required>@error('password')<span class="field-error">{{ $message }}</span>@enderror</div>
            <div class="field"><label for="confirm-password">Ulangi kata sandi baru</label><input class="input" id="confirm-password" type="password" name="password_confirmation" autocomplete="new-password" required></div>
            <div class="form-actions"><span class="hint">Perubahan tercatat di riwayat aktivitas.</span><button class="btn btn-primary" type="submit"><x-icon name="key-round" /> Simpan kata sandi</button></div>
        </form>
    </article>
</section>
@endsection
