@extends('layouts.app')
@section('title', 'Tentang portal')
@section('content')
<header class="page-header" data-reveal>
    <div>
        <p class="page-kicker">Tentang</p>
        <h1>Portal APBDes Sungai Pinang Nibung</h1>
        <p class="page-lead">Desa Sungai Pinang Nibung, Kecamatan Sungai Pinang, Kabupaten Ogan Ilir.</p>
    </div>
</header>

<div class="about">
    <article class="prose" data-reveal>
        <p class="prose-lead">Portal ini menampilkan Anggaran Pendapatan dan Belanja Desa (APBDes) dalam bentuk yang mudah dibaca: ringkasan, grafik, dan tabel rincian yang bisa dicari serta diunduh.</p>
        <p>Warga bisa melihat dari mana pendapatan desa berasal, ke bidang apa saja dana direncanakan, dan dokumen apa yang menjadi dasarnya. Setiap baris anggaran mencantumkan sumber dan status pemeriksaannya, sehingga pembaca tahu angka mana yang sudah dicek dan mana yang belum.</p>

        <h2>Yang bisa dilakukan di sini</h2>
        <ul class="link-list">
            <li><a href="{{ route('dashboard') }}"><strong>Ringkasan</strong><span>Total pendapatan dan belanja, sumber pendapatan, dan belanja per bidang.</span><x-icon name="arrow-right" /></a></li>
            <li><a href="{{ route('budgets.index') }}"><strong>Rincian anggaran</strong><span>Cari kegiatan, saring per bidang atau status, lalu unduh sebagai CSV.</span><x-icon name="arrow-right" /></a></li>
            <li><a href="{{ route('quality') }}"><strong>Sumber data</strong><span>Daftar dataset beserta status pemeriksaannya.</span><x-icon name="arrow-right" /></a></li>
        </ul>

        <h2>Istilah yang sering muncul</h2>
        <dl class="glossary">
            <dt>Pendapatan</dt>
            <dd>Rencana penerimaan desa, misalnya Dana Desa (DD) dari APBN, Alokasi Dana Desa (ADD) dari kabupaten, bagi hasil pajak, dan pendapatan asli desa.</dd>
            <dt>Belanja</dt>
            <dd>Rencana penggunaan dana, dikelompokkan ke lima bidang: penyelenggaraan pemerintahan, pembangunan, pembinaan kemasyarakatan, pemberdayaan masyarakat, dan penanggulangan bencana.</dd>
            <dt>Realisasi</dt>
            <dd>Dana yang benar-benar sudah diterima atau dibelanjakan. Angka ini berbeda dari rencana dan baru ditampilkan setelah diverifikasi.</dd>
            <dt>Surplus / defisit</dt>
            <dd>Selisih rencana pendapatan dan belanja. Defisit biasanya ditutup dengan pembiayaan, misalnya sisa anggaran tahun sebelumnya.</dd>
        </dl>
    </article>

    <aside class="about-side" data-reveal>
        <div class="callout">
            <h2>Batasan</h2>
            <p>Portal ini membantu penyajian informasi, bukan pengganti dokumen resmi. Rujukan utama tetap Peraturan Desa tentang APBDes yang ditetapkan pemerintah desa.</p>
            <p>Data berlabel <span class="demo-tag">contoh</span> hanya untuk mencoba fitur dan tidak menggambarkan keuangan desa yang sebenarnya.</p>
        </div>
        <div class="callout callout-plain">
            <h2>Untuk pengelola</h2>
            <p>Perangkat desa yang ditugaskan dapat masuk untuk menambah, memperbarui, dan mengimpor data anggaran.</p>
            <a class="text-link" href="{{ route('login') }}">Masuk pengelola <x-icon name="arrow-right" /></a>
        </div>
    </aside>
</div>
@endsection
