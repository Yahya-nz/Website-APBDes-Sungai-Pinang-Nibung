@extends('layouts.app')
@section('title', 'Tentang Portal')
@section('heading', 'Tentang Portal')
@section('content')
<div class="page-head" data-reveal>
    <div><h1>Tentang Dashboard Monitoring APBDes</h1><p>Portal keterbukaan anggaran Desa Sungai Pinang Nibung.</p></div>
</div>
<section class="grid grid-7-5">
    <article class="card" data-reveal>
        <header class="card-head"><div><h2>Untuk apa portal ini?</h2></div></header>
        <div class="card-body prose">
            <p>Dashboard ini menyajikan Anggaran Pendapatan dan Belanja Desa (APBDes) Desa Sungai Pinang Nibung dalam bentuk ringkasan, grafik, dan tabel yang mudah dibaca. Angka anggaran disalin dari baliho APBDes yang dipasang desa.</p>
            <p>Selain keterbukaan informasi, portal ini membantu perangkat desa memantau realisasi anggaran, menyusun laporan bulanan atau semester secara otomatis, dan menerima masukan warga.</p>
        </div>
        <div class="card-body" style="padding-top: 0">
            <dl class="dl">
                <dt>Pendapatan</dt><dd style="font-weight: 400">Rencana penerimaan desa: Dana Desa, Alokasi Dana Desa, bagi hasil pajak, pendapatan asli desa, dan bunga bank.</dd>
                <dt>Belanja</dt><dd style="font-weight: 400">Rencana penggunaan dana di lima bidang: pemerintahan, pembangunan, pembinaan kemasyarakatan, pemberdayaan masyarakat, dan penanggulangan bencana.</dd>
                <dt>Realisasi</dt><dd style="font-weight: 400">Dana yang benar-benar sudah dibelanjakan, dicatat per transaksi oleh perangkat desa.</dd>
                <dt>Pembiayaan</dt><dd style="font-weight: 400">Penerimaan atau pengeluaran untuk menutup defisit atau memanfaatkan surplus.</dd>
            </dl>
        </div>
    </article>
    <div style="display: grid; gap: 20px; align-content: start">
        <article class="card" data-reveal>
            <header class="card-head"><div><h2>Hak akses</h2></div></header>
            <div class="card-body">
                <div class="progress-item"><div class="progress-item-head"><strong><x-icon name="users-round" class="icon" style="display: inline; vertical-align: -3px; margin-right: 6px" />Masyarakat</strong><span class="badge badge-gray">Tanpa login</span></div><p class="hint">Melihat dashboard, rincian, realisasi, grafik, mengunduh laporan, dan mengirim pengaduan.</p></div>
                <div class="progress-item"><div class="progress-item-head"><strong><x-icon name="user-cog" class="icon" style="display: inline; vertical-align: -3px; margin-right: 6px" />Perangkat desa</strong><span class="badge badge-blue">Login</span></div><p class="hint">Mengelola data APBDes, mencatat realisasi, menanggapi pengaduan, dan mengatur akun.</p></div>
            </div>
        </article>
        <article class="card" data-reveal>
            <header class="card-head"><div><h2>Batasan</h2></div></header>
            <div class="card-body prose"><p>Portal ini bukan pengganti dokumen resmi. Rujukan utama tetap Peraturan Desa tentang APBDes. Realisasi, sisa anggaran, dan penyerapan hanya dihitung dari kegiatan yang sudah terverifikasi.</p></div>
        </article>
    </div>
</section>
@endsection
