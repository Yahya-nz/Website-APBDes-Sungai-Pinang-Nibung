# Dashboard Monitoring APBDes Desa Sungai Pinang Nibung

Website monitoring Anggaran Pendapatan dan Belanja Desa (APBDes) Desa Sungai
Pinang Nibung, Kecamatan Sungai Pinang, Kabupaten Ogan Ilir. Tampilannya
mengikuti mockup pada proposal capstone, dan angka anggarannya disalin dari
baliho APBDes 2026 desa (pendapatan dan belanja masing-masing
Rp549.108.437,83).

![Dashboard](docs/screenshots/01-dashboard.jpg)

## Fitur

Untuk masyarakat (tanpa login):

- Dashboard: kartu total pendapatan, belanja, pembiayaan, dan realisasi;
  donat komposisi pendapatan; grafik batang belanja per bidang; tabel rincian
  pendapatan dan belanja; progress rencana vs realisasi; pengeluaran terbaru.
- APBDes per menu Pendapatan, Belanja, Pembiayaan, dan Rincian Kegiatan.
  Belanja dikelompokkan per bidang dan sub bidang seperti di baliho, lengkap
  dengan volume, sumber dana, dan persentase realisasi.
- Monitoring realisasi: grafik realisasi bulanan dibanding rencana, progress
  per bidang dan per kegiatan, serta daftar transaksi per bulan.
- Grafik & Analisis: komposisi pendapatan, belanja per sumber dana, rencana vs
  realisasi, realisasi bulanan, status verifikasi, dan kegiatan terbesar.
- Laporan otomatis bulanan atau semester yang bisa diunduh sebagai PDF (dengan
  kop desa dan kolom tanda tangan) atau Excel (sheet Rekap, Kegiatan,
  Transaksi).
- Notifikasi pengeluaran baru lewat ikon lonceng. Penanda belum dibaca
  disimpan di perangkat, dan daftar diperbarui otomatis setiap menit.
- Pengaduan, pertanyaan, dan saran warga tanpa akun. Warga mendapat nomor
  tiket untuk melihat tanggapan, bisa mengunduh bukti tiket, dan tiketnya
  tersimpan di daftar "Tiket saya" pada perangkat itu (bisa disaring per
  status dan jenis pesan). Bila nomor tiket hilang, tiket dipulihkan dengan
  No. HP/email dan PIN yang diisi saat mengirim. Dilindungi pembatas kiriman,
  pembatas percobaan pemulihan, dan jebakan bot.
- Pencarian kegiatan (tekan `/`): semua kata harus cocok, hasil diurutkan
  dari yang paling relevan, kata kunci ditandai.
- Status verifikasi data beserta sumber datasetnya.

Untuk perangkat desa (login):

- Dashboard Pengelola (halaman pertama setelah login): ringkasan data,
  daftar hal yang perlu ditindaklanjuti (pengaduan baru, realisasi melebihi
  anggaran, hasil pemeriksaan otomatis), pengaduan yang belum selesai,
  dan riwayat terbaru.
- Pengelolaan Data APBDes mengikuti mockup halaman pengelola di proposal:
  1. impor CSV/Excel, 2. informasi sumber data (periode, nama sumber, dan
  tanggal pembaruan wajib dicatat), 3. pemeriksaan otomatis dan status
  verifikasi (bisa ditetapkan untuk banyak baris sekaligus), 4. riwayat
  dataset (ubah informasi sumber, atau hapus untuk membatalkan impor yang
  salah), lalu tabel anggaran untuk tambah, ubah, dan hapus. Uraian ganda
  pada bidang dan tahun yang sama ditolak.
- Catat realisasi per kegiatan. Peringatan muncul bila realisasi melebihi
  anggaran, dan pengeluaran langsung tampil di notifikasi warga.
- Kotak masuk pengaduan dengan status Baru, Diproses, Selesai beserta
  tanggapan.
- Pengguna & hak akses: tambah akun perangkat desa, cabut atau pulihkan
  aksesnya (akun tidak dihapus supaya riwayatnya tetap terbaca).
- Riwayat aktivitas lengkap dengan saringan jenis, dan ganti kata sandi.

| Peran | Bisa apa |
| --- | --- |
| Masyarakat | Melihat semua halaman publik, mengunduh laporan dan CSV, mengirim dan mengecek pengaduan. |
| Perangkat desa | Semua di atas, ditambah mengelola data, memverifikasi, mencatat realisasi, menanggapi pengaduan, dan menambah akun perangkat desa lain. |

## Tentang data

- Anggaran (rencana) asli dari baliho APBDes 2026. Nama kegiatan disalin
  dengan perbaikan salah ketik yang jelas; empat baris "Belanja barang dan
  jasa perlengkapan kantor" dibedakan dengan sumber dananya.
- Realisasi, sisa anggaran, dan penyerapan hanya dihitung dari kegiatan yang
  sudah terverifikasi. Kegiatan yang belum diverifikasi tetap tampil dengan
  label statusnya dan keterangan "belum dihitung", termasuk di laporan PDF dan
  Excel.

## Teknologi

- Laravel 13 (PHP 8.4+), Blade, MySQL; tes memakai SQLite in-memory
- Vite, CSS biasa, Chart.js, ikon Lucide, huruf Plus Jakarta Sans (tanpa CDN)
- PhpSpreadsheet untuk impor dan laporan Excel, laravel-dompdf untuk PDF

Struktur singkat: `app/Services/BudgetDashboard.php` perhitungan ringkasan,
realisasi, dan grafik; `app/Services/ReportBuilder.php` dan
`ReportSpreadsheet.php` laporan; `app/Support/BudgetSearch.php` pencarian;
`database/seeders/BalihoApbdes2026.php` data baliho; `resources/views`
tampilan; `resources/css/app.css` dan `resources/js/app.js` gaya dan
interaksi; `tests/` pengujian.

## Menjalankan di komputer sendiri

Perlu PHP 8.4+ (pdo_mysql, mbstring, fileinfo, openssl, gd, zip, xml, dom),
MySQL, Composer, dan Node.js 20.19+.

```sh
composer install
npm install
npm run build
cp .env.example .env
php artisan key:generate
```

Buat database kosong `apbdes_nibung`, sesuaikan `DB_*` serta
`APBDES_ADMIN_EMAIL` dan `APBDES_ADMIN_PASSWORD` di `.env`, lalu:

```sh
php artisan migrate --seed
php artisan serve
```

Buka http://127.0.0.1:8000. Halaman perangkat desa ada di `/kelola` (menu
Kelola Data). Akun untuk pengujian ada di [AKUN-UJI.md](AKUN-UJI.md). Saat mengubah tampilan, jalankan `npm run dev`
bersamaan dengan `php artisan serve`.

Tes otomatis (butuh pdo_sqlite):

```sh
php artisan test
```

## Impor data

Template diunduh dari halaman Pengelolaan Data. Saat impor, periode
anggaran, nama/jenis sumber, dan tanggal pembaruan wajib diisi; nilainya
tercatat di Riwayat Dataset dan dipakai untuk baris yang kolom sumbernya
kosong. Kolom: `year`, `type`
(`income`/`expense`), `category`, `sub_category`, `description`, `volume`,
`amount`, `funding_source`, `source`, `verification_status`, `source_date`.
Nominal ditulis tanpa Rp dan tanpa titik ribuan, misalnya `25000000`.

<details>
<summary>Tangkapan layar lain</summary>

![Profil desa](docs/screenshots/02-profil-desa.jpg)
![Pendapatan](docs/screenshots/03-pendapatan.jpg)
![Belanja](docs/screenshots/04-belanja.jpg)
![Pembiayaan](docs/screenshots/05-pembiayaan.jpg)
![Realisasi anggaran](docs/screenshots/06-realisasi-anggaran.jpg)
![Grafik dan analisis](docs/screenshots/07-grafik-analisis.jpg)
![Laporan](docs/screenshots/08-laporan.jpg)
![Laporan PDF](docs/screenshots/09-laporan-pdf.jpg)
![Pengaduan warga](docs/screenshots/10-pengaduan-warga.jpg)
![Status pengaduan](docs/screenshots/11-status-pengaduan.jpg)
![Status verifikasi](docs/screenshots/12-status-verifikasi.jpg)
![Notifikasi](docs/screenshots/13-notifikasi.jpg)
![Menu diringkas](docs/screenshots/14-menu-diringkas.jpg)
![Masuk perangkat desa](docs/screenshots/15-masuk-perangkat-desa.jpg)
![Pengelolaan data](docs/screenshots/16-kelola-data.jpg)
![Formulir anggaran](docs/screenshots/17-formulir-anggaran.jpg)
![Catat realisasi](docs/screenshots/18-catat-realisasi.jpg)
![Pengaduan masuk](docs/screenshots/19-pengaduan-masuk.jpg)
![Pengaturan](docs/screenshots/20-pengaturan.jpg)
![Dashboard di ponsel](docs/screenshots/21-ponsel-dashboard.jpg)
![Menu di ponsel](docs/screenshots/22-ponsel-menu.jpg)
![Belanja di ponsel](docs/screenshots/23-ponsel-belanja.jpg)
![Hasil pencarian](docs/screenshots/24-hasil-pencarian.jpg)
![Konfirmasi hapus](docs/screenshots/25-konfirmasi-hapus.jpg)
![Dashboard pengelola](docs/screenshots/26-dashboard-pengelola.jpg)
![Verifikasi beberapa baris](docs/screenshots/27-verifikasi-massal.jpg)
![Pengguna dan hak akses](docs/screenshots/28-pengguna.jpg)
![Riwayat aktivitas](docs/screenshots/29-riwayat-aktivitas.jpg)
![Tiket saya dan pemulihan tiket](docs/screenshots/30-tiket-saya-pemulihan.jpg)

</details>
