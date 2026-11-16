# Akun Uji

Akun pengelola dibuat otomatis saat menjalankan `php artisan migrate --seed`
(nilainya diambil dari `APBDES_ADMIN_EMAIL` dan `APBDES_ADMIN_PASSWORD` di `.env`).

| Peran | Alamat | Email | Kata sandi |
| --- | --- | --- | --- |
| Perangkat desa (pengelola data) | http://127.0.0.1:8000/login | `admin@apbdes.test` | `Nibung-2026!Mulai` |
| Masyarakat | http://127.0.0.1:8000 | tidak perlu akun | – |

## Yang bisa dicoba sebagai pengelola

1. Masuk lewat menu **Kelola Data** di samping, atau buka `/login`.
2. **Dashboard Pengelola**: ringkasan data dan daftar hal yang perlu ditindaklanjuti.
3. **Pengelolaan Data**: impor CSV/Excel (unduh templatenya), isi informasi
   sumber, lihat hasil pemeriksaan otomatis, centang beberapa baris lalu
   tetapkan status verifikasinya, dan ubah informasi sumber di Riwayat Dataset.
4. **Catat Realisasi**: tambah pengeluaran; pengeluaran baru muncul di lonceng
   notifikasi halaman publik.
5. **Pengaduan Masuk**: tanggapi pesan warga. Warga mengecek tanggapan dengan
   nomor tiket di menu Pengaduan Warga.
6. **Pengguna**: tambah akun perangkat desa lain, atau cabut aksesnya.
7. **Riwayat Aktivitas** dan **Pengaturan** (ganti kata sandi).

Realisasi, sisa anggaran, dan penyerapan hanya dihitung dari kegiatan yang
sudah terverifikasi. Coba ubah status baris "Insentif RT" untuk melihat
angkanya berubah di dashboard.

## Mencoba pemulihan tiket pengaduan (tanpa login)

1. Buka menu **Pengaduan Warga**, lalu klik **Lupa nomor tiket? Pulihkan di sini**.
2. Isi No. HP `0812-0000-1111` (boleh ditulis `+62 812 0000 1111`) dan PIN `1234`.
3. Tiket `ADU-2609-K7QM` muncul di daftar **Tiket saya**. Coba saring dengan
   tab status (Baru, Diproses, Selesai) dan pilihan jenis pesan.

Saat mengirim pesan baru, isi No. HP / email dan PIN pemulihan agar tiketnya
bisa dipulihkan dengan cara yang sama.

## Catatan

- Ganti kata sandi di menu **Pengaturan** sebelum portal dipakai di luar
  pengujian, atau ubah `APBDES_ADMIN_PASSWORD` di `.env` sebelum seeding.
- Untuk mengembalikan data ke kondisi awal: `php artisan migrate:fresh --seed`.
