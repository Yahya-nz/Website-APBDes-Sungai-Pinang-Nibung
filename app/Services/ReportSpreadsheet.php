<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Laporan realisasi dalam format Excel: sheet Rekap per bidang, Kegiatan, dan Transaksi.
 */
class ReportSpreadsheet
{
    private const MONEY = '"Rp"#,##0.00;-"Rp"#,##0.00';

    public function make(array $report): Spreadsheet
    {
        $book = new Spreadsheet;
        $book->getProperties()->setCreator('Pemerintah Desa Sungai Pinang Nibung')->setTitle('Laporan Realisasi APBDes '.$report['label']);

        $this->summarySheet($book->getActiveSheet(), $report);
        $this->activitySheet($book->createSheet(), $report);
        $this->transactionSheet($book->createSheet(), $report);
        $book->setActiveSheetIndex(0);

        return $book;
    }

    private function summarySheet(Worksheet $sheet, array $report): void
    {
        $sheet->setTitle('Rekap');
        $this->heading($sheet, $report, 'F');
        $sheet->fromArray(['Bidang', 'Anggaran', 'Realisasi periode ini', 'Realisasi s.d. periode', 'Sisa anggaran', 'Penyerapan (%)'], null, 'A6');
        $row = 7;
        foreach ($report['categories'] as $category) {
            $sheet->fromArray([$category['name'], $category['budget'], $category['period'], $category['cumulative'], $category['remaining'], $category['percent'] / 100], null, "A{$row}");
            $row++;
        }
        $totals = $report['totals'];
        $sheet->fromArray(['Total belanja', $totals['budget'], $totals['period'], $totals['cumulative'], $totals['remaining'], $totals['percent'] / 100], null, "A{$row}");
        $this->table($sheet, "A6:F{$row}", "B7:E{$row}", "F7:F{$row}", $row);
        foreach (['A' => 48, 'B' => 20, 'C' => 22, 'D' => 22, 'E' => 20, 'F' => 16] as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }
    }

    private function activitySheet(Worksheet $sheet, array $report): void
    {
        $sheet->setTitle('Kegiatan');
        $this->heading($sheet, $report, 'H');
        $sheet->fromArray(['Bidang', 'Kegiatan', 'Volume', 'Sumber dana', 'Anggaran', 'Realisasi periode ini', 'Realisasi s.d. periode', 'Penyerapan (%)'], null, 'A6');
        $row = 7;
        foreach ($report['activities'] as $category => $activities) {
            foreach ($activities as $activity) {
                $description = $activity['counted'] ? $activity['description'] : $activity['description'].' (belum diverifikasi, tidak dihitung)';
                $sheet->fromArray([$category, $description, $activity['volume'], $activity['funding_source'], $activity['budget'], $activity['period'], $activity['cumulative'], $activity['percent'] === null ? null : $activity['percent'] / 100], null, "A{$row}", true);
                $row++;
            }
        }
        $this->table($sheet, 'A6:H'.max(6, $row - 1), 'E7:G'.max(7, $row - 1), 'H7:H'.max(7, $row - 1), null);
        foreach (['A' => 34, 'B' => 56, 'C' => 14, 'D' => 14, 'E' => 18, 'F' => 20, 'G' => 20, 'H' => 14] as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }
    }

    private function transactionSheet(Worksheet $sheet, array $report): void
    {
        $sheet->setTitle('Transaksi');
        $this->heading($sheet, $report, 'E');
        $sheet->fromArray(['Tanggal', 'Bidang', 'Kegiatan', 'Keterangan', 'Nominal'], null, 'A6');
        $row = 7;
        foreach ($report['transactions'] as $transaction) {
            $sheet->fromArray([Carbon::parse($transaction['date'])->format('d/m/Y'), $transaction['category'], $transaction['activity'], $transaction['description'].($transaction['counted'] ? '' : ' (tidak dihitung, kegiatan belum diverifikasi)'), $transaction['amount']], null, "A{$row}");
            $row++;
        }
        $this->table($sheet, 'A6:E'.max(6, $row - 1), 'E7:E'.max(7, $row - 1), null, null);
        foreach (['A' => 13, 'B' => 32, 'C' => 46, 'D' => 46, 'E' => 18] as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }
    }

    private function heading(Worksheet $sheet, array $report, string $lastColumn): void
    {
        $sheet->setCellValue('A1', 'Laporan Realisasi APBDes Desa Sungai Pinang Nibung');
        $sheet->setCellValue('A2', 'Kecamatan Sungai Pinang, Kabupaten Ogan Ilir');
        $sheet->setCellValue('A3', 'Periode: '.$report['label'].' ('.Carbon::parse($report['start'])->format('d/m/Y').' – '.Carbon::parse($report['end'])->format('d/m/Y').')');
        if ($report['uncounted']['activities'] !== []) {
            $sheet->setCellValue('A4', 'Catatan: '.count($report['uncounted']['activities']).' kegiatan belum diverifikasi ('.implode(', ', $report['uncounted']['activities']).') tidak dihitung dalam realisasi, sisa anggaran, dan penyerapan.');
            $sheet->getStyle('A4')->getFont()->setItalic(true)->getColor()->setRGB('B45309');
        }
        $sheet->mergeCells("A1:{$lastColumn}1");
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
    }

    private function table(Worksheet $sheet, string $range, string $moneyRange, ?string $percentRange, ?int $totalRow): void
    {
        $header = explode(':', $range)[0];
        $headerRow = preg_replace('/\D/', '', $header);
        $lastColumn = preg_replace('/\d/', '', explode(':', $range)[1]);
        $sheet->getStyle("A{$headerRow}:{$lastColumn}{$headerRow}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E3A8A']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ]);
        $sheet->getStyle($range)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('CBD5E1');
        $sheet->getStyle($moneyRange)->getNumberFormat()->setFormatCode(self::MONEY);
        if ($percentRange) {
            $sheet->getStyle($percentRange)->getNumberFormat()->setFormatCode('0.0%');
        }
        if ($totalRow) {
            $sheet->getStyle("A{$totalRow}:{$lastColumn}{$totalRow}")->applyFromArray(['font' => ['bold' => true], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EFF6FF']]]);
        }
        $sheet->freezePane('A'.((int) $headerRow + 1));
    }
}
