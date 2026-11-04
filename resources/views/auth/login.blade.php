@extends('layouts.guest')
@section('title', 'Masuk perangkat desa')
@section('content')
<div class="auth">
    <aside class="auth-aside">
        <a href="{{ route('dashboard') }}" class="auth-brand"><span class="emblem"><x-icon name="landmark" /></span><span class="brand-text"><strong>Desa Sungai Pinang Nibung</strong><span>Dashboard Monitoring APBDes</span></span></a>
        <div class="auth-copy">
            <h2>Ruang kerja perangkat desa</h2>
            <p>Masuk untuk mengelola data APBDes, mencatat realisasi, dan menanggapi masukan warga.</p>
            <ul>
                <li><x-icon name="database" /> Kelola dan impor data anggaran</li>
                <li><x-icon name="receipt-text" /> Catat pengeluaran, warga langsung dapat notifikasi</li>
                <li><x-icon name="file-text" /> Unduh laporan bulanan dan semester</li>
                <li><x-icon name="message-square-text" /> Tanggapi pengaduan warga</li>
            </ul>
        </div>
        <x-village-art class="auth-art" />
    </aside>
    <main class="auth-main" id="main">
        <a href="{{ route('dashboard') }}" class="auth-back"><x-icon name="arrow-left" /> Kembali ke dashboard</a>
        <div class="auth-card">
            <span class="auth-mobile-brand"><span class="emblem" style="width: 36px; height: 36px"><x-icon name="landmark" /></span> APBDes Sungai Pinang Nibung</span>
            <h1>Selamat datang kembali</h1>
            <p class="auth-lead">Masuk dengan akun perangkat desa.</p>
            <form action="{{ route('login.store') }}" method="POST" class="auth-form">
                @csrf
                <div class="field">
                    <label for="email">Email</label>
                    <div class="input-icon"><x-icon name="mail" /><input class="input" id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" placeholder="nama@desa.id" required autofocus @error('email') aria-invalid="true" aria-describedby="email-error" @enderror></div>
                    @error('email')<span id="email-error" class="field-error">{{ $message }}</span>@enderror
                </div>
                <div class="field">
                    <label for="password">Kata sandi</label>
                    <div class="input-icon"><x-icon name="lock-keyhole" /><input class="input" id="password" name="password" type="password" autocomplete="current-password" placeholder="Masukkan kata sandi" required data-caps-check @error('password') aria-invalid="true" aria-describedby="password-error" @enderror><button class="eye-btn" type="button" data-toggle-password="password" aria-label="Tampilkan kata sandi" aria-pressed="false"><x-icon name="eye" /></button></div>
                    <span class="caps-hint" data-caps-warning hidden><x-icon name="arrow-big-up" /> Caps Lock sedang aktif</span>
                    @error('password')<span id="password-error" class="field-error">{{ $message }}</span>@enderror
                </div>
                <label class="check"><input type="checkbox" name="remember" value="1" @checked(old('remember'))><span>Ingat saya di perangkat ini</span></label>
                <button type="submit" class="btn btn-primary btn-block">Masuk <x-icon name="arrow-right" /></button>
            </form>
            <p class="auth-help"><x-icon name="info" /> Warga tidak perlu login. Untuk menyampaikan masukan, gunakan menu <a class="link" href="{{ route('complaints.create') }}">Pengaduan Warga</a>.</p>
        </div>
    </main>
</div>
@endsection
