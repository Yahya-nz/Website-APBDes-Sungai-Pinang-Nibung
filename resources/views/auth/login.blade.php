@extends('layouts.guest')
@section('title', 'Masuk pengelola')
@section('content')
<div class="login">
    <section class="login-side" aria-hidden="true">
        <a href="{{ route('dashboard') }}" class="brand brand-light" tabindex="-1">
            <x-logo class="brand-logo" />
            <span class="brand-text"><strong>APBDes</strong><span>Sungai Pinang Nibung</span></span>
        </a>
        <div class="login-side-copy">
            <h2>Ruang pengelola data anggaran desa</h2>
            <ul>
                <li>Tambah dan perbarui baris anggaran</li>
                <li>Impor dari template CSV atau Excel</li>
                <li>Tandai data yang sudah dicek ke dokumen sumber</li>
                <li>Setiap perubahan tercatat di riwayat</li>
            </ul>
        </div>
        <svg class="login-waves" viewBox="0 0 600 160" fill="none" preserveAspectRatio="none"><path d="M0 60c100-40 200-40 300 0s200 40 300 0" /><path d="M0 100c100-40 200-40 300 0s200 40 300 0" /><path d="M0 140c100-40 200-40 300 0s200 40 300 0" /></svg>
    </section>

    <main class="login-main" id="main">
        <a href="{{ route('dashboard') }}" class="login-back"><x-icon name="arrow-left" /> Kembali ke portal</a>
        <div class="login-card">
            <h1>Masuk pengelola</h1>
            <p class="muted">Gunakan akun yang diberikan pemerintah desa.</p>

            <form action="{{ route('login.store') }}" method="POST" class="login-form">
                @csrf
                <div class="field">
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" placeholder="nama@desa.id" required autofocus @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
                    @error('email')<span id="email-error" class="field-error">{{ $message }}</span>@enderror
                </div>
                <div class="field">
                    <label for="password">Kata sandi</label>
                    <div class="password-field">
                        <input id="password" name="password" type="password" autocomplete="current-password" required data-caps-check @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
                        <button class="password-toggle" type="button" data-toggle-password="password" aria-label="Tampilkan kata sandi" aria-pressed="false"><x-icon name="eye" /></button>
                    </div>
                    <span class="field-hint" data-caps-warning hidden><x-icon name="arrow-big-up" /> Caps Lock sedang aktif</span>
                    @error('password')<span id="password-error" class="field-error">{{ $message }}</span>@enderror
                </div>
                <label class="check"><input type="checkbox" name="remember" value="1" @checked(old('remember'))><span>Tetap masuk di perangkat ini</span></label>
                <button type="submit" class="button button-primary button-block">Masuk</button>
            </form>
            <p class="login-help">Lupa kata sandi? Hubungi operator desa untuk mengatur ulang akun.</p>
        </div>
    </main>
</div>
@endsection
