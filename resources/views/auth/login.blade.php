@extends('layouts.guest')
@section('title', 'Masuk pengelola')
@section('content')
<div class="auth">
    <aside class="auth-aside">
        <a href="{{ route('dashboard') }}" class="auth-brand">
            <span class="auth-brand-icon"><x-icon name="landmark" /></span>
            <span>APBDes<small>Sungai Pinang Nibung</small></span>
        </a>
        <div class="auth-aside-copy">
            <h2>Ruang pengelola data anggaran desa</h2>
            <p>Tempat perangkat desa memasukkan, memeriksa, dan memperbarui data APBDes yang tampil di portal.</p>
            <ul>
                <li><x-icon name="database" /> Tambah data satu per satu atau impor dari Excel</li>
                <li><x-icon name="shield-check" /> Tandai data yang sudah dicek ke dokumen</li>
                <li><x-icon name="history" /> Setiap perubahan tercatat di riwayat</li>
            </ul>
        </div>
        <svg class="auth-art" viewBox="0 0 440 210" fill="none" aria-hidden="true"><circle class="auth-art-sun" cx="307" cy="91" r="58" fill="#c9eaa0" opacity=".12"/><circle class="auth-art-sun" cx="307" cy="91" r="42" stroke="#c9eaa0" opacity=".35"/><path class="auth-art-hill" d="M18 204C87 147 135 147 199 165C262 184 302 120 433 143M3 221C89 159 146 171 211 185C280 199 322 145 442 160" stroke="#97bb8e" opacity=".38"/><path class="auth-art-line" d="M77 182V114L129 78L181 114V176M65 115L129 68L193 115M116 179V139H142V179M90 126H105V143H90V126ZM153 126H168V143H153V126Z" stroke="#c9eaa0" stroke-width="1.5" stroke-linejoin="round"/><path class="auth-art-line" d="M210 170V108L250 79L290 108V155M200 109L250 69L300 109M237 166V130H260V161M217 114H230V129H217V114ZM272 114H283V129H272V114Z" stroke="#c9eaa0" stroke-width="1.5" stroke-linejoin="round"/><g class="auth-art-plant"><path d="M339 150V76M339 125C317 121 311 111 312 99C329 99 340 108 339 125ZM340 105C362 101 368 91 367 79C350 79 339 88 340 105ZM340 85C323 82 319 73 320 63C333 63 341 71 340 85ZM340 70C355 59 355 49 350 39C338 47 334 57 340 70Z" stroke="#c9eaa0" stroke-width="1.5"/></g><path d="M368 145V125M368 135C382 132 385 125 384 117C374 117 367 123 368 135Z" stroke="#c9eaa0" stroke-width="1.5"/></svg>
    </aside>

    <main class="auth-main" id="main">
        <a href="{{ route('dashboard') }}" class="auth-back"><x-icon name="arrow-left" /> Kembali ke portal</a>
        <div class="auth-card">
            <span class="auth-mobile-brand"><x-icon name="landmark" /> APBDes Sungai Pinang Nibung</span>
            <h1>Selamat datang kembali</h1>
            <p class="auth-lead">Masuk dengan akun pengelola yang diberikan pemerintah desa.</p>

            <form action="{{ route('login.store') }}" method="POST" class="login-form auth-form">
                @csrf
                <div class="auth-field">
                    <label for="email">Email</label>
                    <div class="auth-input"><x-icon name="mail" /><input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" placeholder="nama@desa.id" required autofocus @error('email') aria-invalid="true" aria-describedby="email-error" @enderror></div>
                    @error('email')<span id="email-error" class="auth-error"><x-icon name="circle-alert" /> {{ $message }}</span>@enderror
                </div>
                <div class="auth-field">
                    <label for="password">Kata sandi</label>
                    <div class="auth-input"><x-icon name="lock-keyhole" /><input id="password" name="password" type="password" autocomplete="current-password" placeholder="Masukkan kata sandi" required data-caps-check @error('password') aria-invalid="true" aria-describedby="password-error" @enderror><button class="auth-eye" type="button" data-toggle-password="password" aria-label="Tampilkan kata sandi" aria-pressed="false"><x-icon name="eye" /></button></div>
                    <span class="auth-hint" data-caps-warning hidden><x-icon name="arrow-big-up" /> Caps Lock sedang aktif</span>
                    @error('password')<span id="password-error" class="auth-error"><x-icon name="circle-alert" /> {{ $message }}</span>@enderror
                </div>
                <label class="auth-check"><input type="checkbox" name="remember" value="1" @checked(old('remember'))><span>Ingat saya di perangkat ini</span></label>
                <button type="submit" class="auth-submit">Masuk <x-icon name="arrow-right" /></button>
            </form>
            <p class="auth-help"><x-icon name="info" /> Lupa kata sandi? Hubungi operator desa untuk mengatur ulang akun.</p>
        </div>
    </main>
</div>
@endsection
