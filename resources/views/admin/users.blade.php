@extends('layouts.app')
@section('title', 'Pengguna & Hak Akses')
@section('heading', 'Pengguna & Hak Akses')
@section('content')
<div class="page-head" data-reveal>
    <div>
        <nav class="crumbs" aria-label="Lokasi"><a href="{{ route('admin.index') }}">Dashboard pengelola</a><span>/</span><span>Pengguna</span></nav>
        <h1>Pengguna & Hak Akses</h1>
        <p>Hanya akun pengelola yang dapat mengimpor dan memperbarui data. Warga tidak perlu akun untuk melihat dashboard.</p>
    </div>
</div>

<section class="grid grid-7-5">
    <article class="card" data-reveal>
        <header class="card-head"><div><h2>Akun perangkat desa <span class="badge badge-blue">{{ $users->where('is_admin', true)->count() }} aktif</span></h2><p>Setiap perubahan data tercatat atas nama akun yang melakukannya.</p></div></header>
        <div class="table-wrap">
            <table class="table stack">
                <thead><tr><th>Nama</th><th>Akses</th><th>Aktivitas terakhir</th><th><span class="sr-only">Aksi</span></th></tr></thead>
                <tbody>
                @foreach($users as $user)
                    @php $isSelf = $user->is(auth()->user()); @endphp
                    <tr style="--row: {{ $loop->index }}">
                        <td class="cell-main"><span class="user-cell"><span class="avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span><span><span class="strong">{{ $user->name }}</span>@if($isSelf) <span class="badge badge-blue">Anda</span>@endif<span class="sub">{{ $user->email }}</span></span></span></td>
                        <td data-label="Akses">@if($user->is_admin)<span class="badge status badge-green">Pengelola data</span>@else<span class="badge status badge-gray">Akses dicabut</span>@endif</td>
                        <td data-label="Aktivitas terakhir">{{ $user->last_activity_at ? $user->last_activity_at->timezone('Asia/Jakarta')->translatedFormat('j M Y, H.i').' WIB' : 'Belum ada' }}</td>
                        <td class="cell-actions" style="text-align: right">
                            @unless($isSelf)
                                <form method="POST" action="{{ route('admin.users.access', $user) }}" @if($user->is_admin) data-confirm-title="Cabut akses pengelola?" data-confirm-button="Ya, cabut akses" data-confirm="{{ $user->name }} tidak akan bisa lagi masuk ke halaman pengelola. Akunnya tidak dihapus, sehingga riwayat aktivitasnya tetap terbaca dan aksesnya bisa dipulihkan." @endif>
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="access" value="{{ $user->is_admin ? 0 : 1 }}">
                                    <button class="btn btn-sm {{ $user->is_admin ? 'btn-secondary' : 'btn-primary' }}" type="submit"><x-icon :name="$user->is_admin ? 'user-x' : 'user-check'" /> {{ $user->is_admin ? 'Cabut akses' : 'Pulihkan akses' }}</button>
                                </form>
                            @endunless
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <footer class="card-foot"><span>Akun tidak dihapus agar riwayat aktivitasnya tetap terbaca.</span><a class="link" href="{{ route('admin.activities.index', ['aksi' => 'user']) }}">Riwayat pengguna <x-icon name="arrow-right" /></a></footer>
    </article>

    <article class="card" data-reveal>
        <header class="card-head"><div><h2>Tambah pengelola</h2><p>Untuk perangkat desa yang ikut memasukkan dan memeriksa data.</p></div></header>
        <form class="card-body form-stack" method="POST" action="{{ route('admin.users.store') }}">
            @csrf
            <div class="field"><label for="user-name">Nama lengkap</label><input class="input" id="user-name" name="name" maxlength="100" value="{{ old('name') }}" required autocomplete="off" placeholder="Misalnya: Kaur Keuangan">@error('name')<span class="field-error">{{ $message }}</span>@enderror</div>
            <div class="field"><label for="user-email">Email</label><input class="input" id="user-email" type="email" name="email" maxlength="255" value="{{ old('email') }}" required autocomplete="off" placeholder="nama@desa.id">@error('email')<span class="field-error">{{ $message }}</span>@enderror</div>
            <div class="field"><label for="user-password">Kata sandi awal</label><input class="input" id="user-password" type="password" name="password" required autocomplete="new-password">@error('password')<span class="field-error">{{ $message }}</span>@enderror</div>
            <div class="field"><label for="user-password-confirmation">Ulangi kata sandi awal</label><input class="input" id="user-password-confirmation" type="password" name="password_confirmation" required autocomplete="new-password"></div>
            <div class="form-actions"><span class="hint">Minimal 10 karakter, berisi huruf dan angka. Minta pemilik akun menggantinya setelah masuk.</span><button class="btn btn-primary" type="submit"><x-icon name="user-plus" /> Tambah akun</button></div>
        </form>
    </article>
</section>
<x-confirm-dialog />
@endsection
