@extends('layouts.app')

@section('title', 'Tentang portal')

@section('content')
<div class="page-heading"><div><div class="page-kicker">Tentang</div><h1>Portal APBDes Sungai Pinang Nibung</h1><p>Desa Sungai Pinang Nibung, Kecamatan Sungai Pinang, Kabupaten Ogan Ilir.</p></div></div>

<section class="card about-intro"><div class="card-body"><h2>Apa isi portal ini?</h2><p>Dashboard ini menyajikan informasi Anggaran Pendapatan dan Belanja Desa Sungai Pinang Nibung dalam bentuk ringkasan, grafik, dan rincian yang dapat ditelusuri. Tujuannya sederhana: membantu masyarakat melihat sumber pendapatan dan rencana peruntukan anggaran dengan lebih jelas.</p><div class="about-location"><x-icon name="map-pin" /><span>Desa Sungai Pinang Nibung · Kecamatan Sungai Pinang · Kabupaten Ogan Ilir</span></div></div></section>

<div class="about-features">
    <section class="card"><div class="card-body"><span class="metric-icon"><x-icon name="chart-no-axes-combined" /></span><h3>Ringkasan</h3><p>Ringkasan dan visualisasi memperlihatkan komposisi pendapatan serta alokasi belanja.</p><a class="text-link" href="{{ route('dashboard') }}">Buka dashboard <x-icon name="arrow-up-right" /></a></div></section>
    <section class="card"><div class="card-body"><span class="metric-icon"><x-icon name="search" /></span><h3>Rincian anggaran</h3><p>Cari uraian, pilih kategori dan tahun, lalu unduh data untuk dipelajari lebih lanjut.</p><a class="text-link" href="{{ route('budgets.index') }}">Lihat rincian anggaran <x-icon name="arrow-up-right" /></a></div></section>
    <section class="card"><div class="card-body"><span class="metric-icon"><x-icon name="shield-check" /></span><h3>Kualitas data</h3><p>Informasi sumber, pembaruan, dan status verifikasi memberi konteks pada setiap angka.</p><a class="text-link" href="{{ route('quality') }}">Periksa kualitas data <x-icon name="arrow-up-right" /></a></div></section>
</div>

<section class="card about-reading"><div class="card-header"><div><h2>Istilah yang sering muncul</h2><p>Tiga istilah dasar untuk membaca APBDes.</p></div><x-icon name="book-open" /></div><div class="card-body glossary-grid"><article><span>01</span><h3>Pendapatan</h3><p>Rencana penerimaan desa dari berbagai sumber, seperti Dana Desa, Alokasi Dana Desa, dan pendapatan asli desa.</p></article><article><span>02</span><h3>Belanja</h3><p>Rencana penggunaan dana untuk bidang pemerintahan, pembangunan, pembinaan, pemberdayaan, dan kebutuhan desa lainnya.</p></article><article><span>03</span><h3>Realisasi</h3><p>Nilai penerimaan atau pengeluaran yang sudah terlaksana. Angka ini membutuhkan data pendukung yang berbeda dari rencana anggaran.</p></article></div></section>

<section class="card quality-note"><div class="card-body"><div class="quality-note-icon"><x-icon name="sprout" /></div><div><h3>Batasan penggunaan</h3><p>Portal ini membantu penyajian informasi. Dokumen resmi pemerintah desa tetap menjadi rujukan utama. Data berlabel demo hanya digunakan untuk mencoba fitur dan tidak menggambarkan keuangan desa yang sebenarnya.</p></div></div></section>
@endsection
