<?php

namespace Database\Seeders;

/**
 * APBDes 2026 Desa Sungai Pinang Nibung sesuai baliho resmi desa.
 *
 * Nama kegiatan disalin dari baliho dengan perbaikan salah ketik yang jelas
 * (misalnya "Angggota" menjadi "Anggota"). Empat baris belanja kantor dengan
 * nama yang sama dibedakan dengan sumber dananya.
 */
final class BalihoApbdes2026
{
    public const TOTAL = '549108437.83';

    private const PEMERINTAHAN = 'Penyelenggaraan Pemerintahan Desa';

    private const PEMBANGUNAN = 'Pelaksanaan Pembangunan Desa';

    private const PEMBINAAN = 'Pembinaan Kemasyarakatan Desa';

    private const PEMBERDAYAAN = 'Pemberdayaan Masyarakat Desa';

    private const BENCANA = 'Penanggulangan Bencana';

    private const SILTAP = 'Penghasilan Tetap Kepala Desa dan Perangkat Desa';

    /**
     * @return list<array{0: string, 1: string, 2: string}>
     */
    public static function income(): array
    {
        return [
            ['Dana Desa', 'Dana Desa (DD)', '281267000.00'],
            ['Alokasi Dana Desa', 'Alokasi Dana Desa (ADD)', '253006000.00'],
            ['Bagi Hasil Pajak dan Retribusi', 'Bagi Hasil Pajak dan Retribusi Daerah (BHP)', '13735437.83'],
            ['Pendapatan Asli Desa', 'Pendapatan Asli Desa (PAD)', '1000000.00'],
            ['Bunga Bank', 'Bunga bank', '100000.00'],
        ];
    }

    /**
     * Kolom: bidang, sub-bidang, kegiatan, volume, anggaran, sumber dana, pola realisasi contoh.
     *
     * @return list<array{0: string, 1: ?string, 2: string, 3: string, 4: string, 5: string, 6: array<int, mixed>}>
     */
    public static function expense(): array
    {
        $janOct = range(1, 10);

        return [
            [self::PEMERINTAHAN, self::SILTAP, 'Penghasilan Tetap Kepala Desa', '12 bulan', '27000000.00', 'ADD', ['monthly', 12, $janOct, 5]],
            [self::PEMERINTAHAN, self::SILTAP, 'Penghasilan Tetap Sekretaris Desa', '12 bulan', '21600000.00', 'ADD', ['monthly', 12, $janOct, 5]],
            [self::PEMERINTAHAN, self::SILTAP, 'Penghasilan Tetap Kepala Urusan', '12 bulan', '39600000.00', 'ADD', ['monthly', 12, $janOct, 5]],
            [self::PEMERINTAHAN, self::SILTAP, 'Penghasilan Tetap Kepala Seksi', '12 bulan', '39600000.00', 'ADD', ['monthly', 12, $janOct, 5]],
            [self::PEMERINTAHAN, self::SILTAP, 'Penghasilan Tetap Kepala Dusun', '12 bulan', '24000000.00', 'ADD', ['monthly', 12, $janOct, 5]],
            [self::PEMERINTAHAN, 'Tunjangan BPD', 'Tunjangan Ketua BPD', '12 bulan', '10800000.00', 'ADD', ['monthly', 12, $janOct, 6]],
            [self::PEMERINTAHAN, 'Tunjangan BPD', 'Tunjangan Wakil Ketua BPD', '12 bulan', '9000000.00', 'ADD', ['monthly', 12, $janOct, 6]],
            [self::PEMERINTAHAN, 'Tunjangan BPD', 'Tunjangan Sekretaris BPD', '12 bulan', '8400000.00', 'ADD', ['monthly', 12, $janOct, 6]],
            [self::PEMERINTAHAN, 'Tunjangan BPD', 'Tunjangan Anggota BPD', '12 bulan', '28800000.00', 'ADD', ['monthly', 12, $janOct, 6]],
            [self::PEMERINTAHAN, null, 'Jaminan asuransi kecelakaan kerja dan kematian perangkat desa dan BPD', '17x12 bulan', '3427200.00', 'ADD', ['monthly', 12, $janOct, 10]],
            [self::PEMERINTAHAN, null, 'Jaminan asuransi kecelakaan kerja dan kematian pekerja rentan', '10x5 bulan', '840000.00', 'ADD', ['monthly', 5, range(1, 5), 10]],
            [self::PEMERINTAHAN, null, 'Belanja barang dan jasa perlengkapan kantor (ADD)', '12 bulan', '13338800.00', 'ADD', ['monthly', 12, $janOct, 20]],
            [self::PEMERINTAHAN, null, 'Belanja barang dan jasa perlengkapan kantor (BHP)', '12 bulan', '7553437.83', 'BHP', ['monthly', 12, $janOct, 20]],
            [self::PEMERINTAHAN, null, 'Belanja barang dan jasa perlengkapan kantor (Bunga Bank)', '12 bulan', '100000.00', 'Bunga Bank', ['once', '2026-06-30', 'Pembelian alat tulis kantor']],
            [self::PEMERINTAHAN, null, 'Belanja barang dan jasa perlengkapan kantor (PAD)', '12 bulan', '1000000.00', 'PAD', ['once', '2026-04-22', 'Pembelian perlengkapan kantor']],
            [self::PEMERINTAHAN, null, 'Operasional BPD', '12 bulan', '500000.00', 'ADD', ['monthly', 12, $janOct, 20]],
            [self::PEMERINTAHAN, null, 'Insentif RT', '12 bulan', '9600000.00', 'Dana Desa', ['monthly', 12, $janOct, 25]],
            [self::PEMERINTAHAN, null, 'Operasional pemerintah desa bersumber dari Dana Desa', '12 bulan', '8400000.00', 'Dana Desa', ['monthly', 12, $janOct, 20]],
            [self::PEMERINTAHAN, null, 'Sarana dan prasarana pemerintah desa', '1 unit', '25000000.00', 'Dana Desa', ['once', '2026-06-18', 'Pengadaan sarana dan prasarana kantor desa']],
            [self::PEMERINTAHAN, null, 'Administrasi kependudukan, pencatatan sipil, statistik dan kearsipan', '12 bulan', '6182000.00', 'BHP', ['monthly', 12, $janOct, 15]],
            [self::PEMERINTAHAN, null, 'Peningkatan kapasitas operator desa', '12 bulan', '3000000.00', 'Dana Desa', ['once', '2026-04-14', 'Pelatihan operator desa']],
            [self::PEMERINTAHAN, null, 'Penyelenggaraan musyawarah perencanaan desa/pembahasan APBDes', '12 bulan', '1250000.00', 'ADD', ['once', '2026-01-20', 'Musyawarah desa pembahasan APBDes']],
            [self::PEMERINTAHAN, null, 'Penyusunan dokumen keuangan desa dan laporan kepala desa', '12 bulan', '1250000.00', 'ADD', ['once', '2026-02-27', 'Penyusunan laporan keuangan dan LKPPD']],

            [self::PEMBANGUNAN, 'Pendidikan', 'Insentif guru Madrasah Diniyah', '12 bulan', '12000000.00', 'Dana Desa', ['monthly', 12, $janOct, 25]],
            [self::PEMBANGUNAN, 'Pendidikan', 'Insentif guru ngaji tradisional', '12 bulan', '12000000.00', 'Dana Desa', ['monthly', 12, $janOct, 25]],
            [self::PEMBANGUNAN, 'Kesehatan', 'Penyelenggaraan Posyandu (makanan tambahan, kelas ibu hamil, kelas lansia, insentif kader)', '12 bulan', '22284000.00', 'Dana Desa', ['monthly', 12, $janOct, 12]],
            [self::PEMBANGUNAN, 'Kesehatan', 'Penyuluhan dan pelatihan bidang kesehatan', '12 bulan', '1000000.00', 'Dana Desa', ['once', '2026-05-14', 'Penyuluhan kesehatan untuk kader dan masyarakat']],
            [self::PEMBANGUNAN, 'Kesehatan', 'Pengasuhan bersama Bina Keluarga Balita (BKB) dan insentif guru PAUD', '12 bulan', '6000000.00', 'Dana Desa', ['monthly', 12, $janOct, 25]],
            [self::PEMBANGUNAN, 'Pekerjaan Umum dan Penataan Ruang', 'Pembangunan jalan rabat beton', '75x1,5x0,15 m', '39777000.00', 'Dana Desa', ['terms', [['2026-07-08', 0.5, 'Termin 1 (50%) pembangunan jalan rabat beton'], ['2026-08-26', 0.5, 'Termin 2 (50%), pekerjaan jalan rabat beton selesai']]]],
            [self::PEMBANGUNAN, 'Kawasan Permukiman', 'Pembuatan sumur bor', '12 unit', '116956000.00', 'Dana Desa', ['terms', [['2026-08-12', 0.5, 'Termin 1: pembuatan 6 unit sumur bor'], ['2026-11-03', 0.25, 'Termin 2: pembuatan 3 unit sumur bor']]]],
            [self::PEMBANGUNAN, 'Perhubungan, Komunikasi, dan Informatika', 'Pengadaan dan pemasangan baliho APBDes', '3 unit', '1050000.00', 'Dana Desa', ['once', '2026-02-10', 'Pengadaan dan pemasangan 3 baliho APBDes 2026']],
            [self::PEMBANGUNAN, 'Perhubungan, Komunikasi, dan Informatika', 'Kuota internet', '1 paket', '4800000.00', 'Dana Desa', ['monthly', 12, range(1, 11), 3]],

            [self::PEMBINAAN, null, 'Belanja jasa honorarium Linmas desa', '8 bulan', '4800000.00', 'ADD', ['monthly', 8, range(3, 10), 28]],
            [self::PEMBINAAN, null, 'Biaya penyuluhan dan penerangan hukum', '1 paket', '500000.00', 'ADD', ['once', '2026-06-24', 'Penyuluhan hukum bagi warga']],
            [self::PEMBINAAN, null, 'Biaya operasional karang taruna', '1 paket', '1800000.00', 'ADD', ['once', '2026-08-14', 'Kegiatan karang taruna peringatan HUT RI']],
            [self::PEMBINAAN, null, 'Insentif lembaga adat', '7 bulan', '4900000.00', 'ADD', ['monthly', 7, range(4, 10), 28]],
            [self::PEMBINAAN, null, 'Operasional PKK', '1 paket', '2000000.00', 'ADD', ['once', '2026-03-11', 'Operasional kegiatan PKK']],

            [self::PEMBERDAYAAN, null, 'Penguatan ketahanan pangan tingkat desa (penanaman jagung pipil)', '1 paket', '5000000.00', 'Dana Desa', ['once', '2026-09-09', 'Pembelian benih dan pupuk jagung pipil']],

            [self::BENCANA, null, 'Bantuan Langsung Tunai (BLT)', '10 KPM', '24000000.00', 'Dana Desa', ['monthly', 12, range(1, 11), 2]],
        ];
    }
}
