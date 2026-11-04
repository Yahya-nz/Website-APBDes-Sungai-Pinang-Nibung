@php
    $money = fn ($value) => 'Rp'.number_format((float) $value, 2, ',', '.');
    $date = fn ($value) => \Carbon\Carbon::parse($value)->locale('id')->translatedFormat('j F Y');
    $totals = $report['totals'];
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Laporan Realisasi APBDes {{ $report['label'] }}</title>
<style>
    @page { margin: 26px 30px 34px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 9.5px; color: #0f172a; }
    .kop { border-bottom: 3px double #1e3a8a; padding-bottom: 8px; margin-bottom: 12px; text-align: center; }
    .kop h1 { margin: 0; font-size: 15px; color: #1e3a8a; letter-spacing: .5px; }
    .kop p { margin: 2px 0 0; font-size: 10px; }
    h2 { font-size: 11.5px; margin: 14px 0 6px; color: #1e3a8a; }
    .meta { width: 100%; margin-bottom: 6px; }
    .meta td { padding: 2px 0; font-size: 10px; }
    .demo { padding: 6px 10px; margin-bottom: 8px; border: 1px dashed #d97706; background: #fff7e6; color: #92400e; font-size: 9.5px; }
    table.data { width: 100%; border-collapse: collapse; }
    table.data th { background: #1e3a8a; color: #fff; font-size: 9px; padding: 5px 6px; text-align: left; }
    table.data td { padding: 4px 6px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
    table.data tr.group td { background: #eff6ff; font-weight: bold; color: #1e3a8a; }
    table.data tfoot td { font-weight: bold; background: #f1f5ff; border-top: 1px solid #93c5fd; }
    table.data .r, .r { text-align: right; white-space: nowrap; }
    .summary { width: 100%; margin: 6px 0 4px; border-collapse: separate; border-spacing: 6px 0; }
    .summary td { width: 25%; padding: 7px 9px; background: #f1f5ff; border-radius: 4px; }
    .summary span { display: block; font-size: 8.5px; color: #475569; }
    .summary strong { font-size: 11.5px; }
    .sign { width: 100%; margin-top: 26px; }
    .sign td { width: 50%; text-align: center; font-size: 10px; vertical-align: top; }
    .footer { position: fixed; bottom: -22px; left: 0; right: 0; font-size: 8px; color: #64748b; }
    .page-break { page-break-before: always; }
</style>
</head>
<body>
    <div class="footer">Laporan otomatis Dashboard Monitoring APBDes Desa Sungai Pinang Nibung · dibuat {{ $date($report['generated_at']) }}</div>
    <div class="kop">
        <h1>PEMERINTAH DESA SUNGAI PINANG NIBUNG</h1>
        <p>Kecamatan Sungai Pinang, Kabupaten Ogan Ilir</p>
        <p><strong>LAPORAN REALISASI ANGGARAN PENDAPATAN DAN BELANJA DESA — {{ strtoupper($report['label']) }}</strong></p>
    </div>
    <table class="meta"><tr><td>Periode: {{ $date($report['start']) }} s.d. {{ $date($report['end']) }}</td><td style="text-align:right">Total pendapatan (rencana): {{ $money($report['income_total']) }}</td></tr></table>
    @if($report['is_demo'])<div class="demo"><strong>CONTOH.</strong> Angka anggaran berasal dari baliho APBDes {{ $report['year'] }}, tetapi realisasi pada laporan ini adalah data contoh dan bukan data resmi desa.</div>@endif

    <table class="summary"><tr>
        <td><span>Anggaran belanja</span><strong>{{ $money($totals['budget']) }}</strong></td>
        <td><span>Realisasi periode ini</span><strong>{{ $money($totals['period']) }}</strong></td>
        <td><span>Realisasi s.d. periode</span><strong>{{ $money($totals['cumulative']) }} ({{ number_format($totals['percent'], 1, ',', '.') }}%)</strong></td>
        <td><span>Sisa anggaran</span><strong>{{ $money($totals['remaining']) }}</strong></td>
    </tr></table>

    <h2>A. Rekap per bidang</h2>
    <table class="data">
        <thead><tr><th>No</th><th>Bidang</th><th class="r">Anggaran</th><th class="r">Realisasi periode ini</th><th class="r">Realisasi s.d. periode</th><th class="r">Sisa</th><th class="r">%</th></tr></thead>
        <tbody>
        @foreach($report['categories'] as $category)
            <tr><td>{{ $loop->iteration }}</td><td>{{ $category['name'] }}</td><td class="r">{{ $money($category['budget']) }}</td><td class="r">{{ $money($category['period']) }}</td><td class="r">{{ $money($category['cumulative']) }}</td><td class="r">{{ $money($category['remaining']) }}</td><td class="r">{{ number_format($category['percent'], 1, ',', '.') }}</td></tr>
        @endforeach
        </tbody>
        <tfoot><tr><td></td><td>Jumlah</td><td class="r">{{ $money($totals['budget']) }}</td><td class="r">{{ $money($totals['period']) }}</td><td class="r">{{ $money($totals['cumulative']) }}</td><td class="r">{{ $money($totals['remaining']) }}</td><td class="r">{{ number_format($totals['percent'], 1, ',', '.') }}</td></tr></tfoot>
    </table>

    <h2>B. Rincian per kegiatan</h2>
    <table class="data">
        <thead><tr><th>Kegiatan</th><th>Volume</th><th>Sumber dana</th><th class="r">Anggaran</th><th class="r">Periode ini</th><th class="r">S.d. periode</th><th class="r">%</th></tr></thead>
        <tbody>
        @foreach($report['activities'] as $category => $activities)
            <tr class="group"><td colspan="7">{{ $category }}</td></tr>
            @foreach($activities as $activity)
                <tr><td>{{ $activity['description'] }}</td><td>{{ $activity['volume'] }}</td><td>{{ $activity['funding_source'] }}</td><td class="r">{{ $money($activity['budget']) }}</td><td class="r">{{ $money($activity['period']) }}</td><td class="r">{{ $money($activity['cumulative']) }}</td><td class="r">{{ number_format($activity['percent'], 1, ',', '.') }}</td></tr>
            @endforeach
        @endforeach
        </tbody>
    </table>

    <div class="page-break"></div>
    <h2>C. Daftar transaksi {{ $report['label'] }}</h2>
    <table class="data">
        <thead><tr><th>Tanggal</th><th>Bidang</th><th>Kegiatan</th><th>Keterangan</th><th class="r">Nominal</th></tr></thead>
        <tbody>
        @forelse($report['transactions'] as $item)
            <tr><td>{{ \Carbon\Carbon::parse($item['date'])->format('d/m/Y') }}</td><td>{{ $item['category'] }}</td><td>{{ $item['activity'] }}</td><td>{{ $item['description'] }}{{ $item['is_demo'] ? ' (contoh)' : '' }}</td><td class="r">{{ $money($item['amount']) }}</td></tr>
        @empty
            <tr><td colspan="5">Tidak ada transaksi pada periode ini.</td></tr>
        @endforelse
        </tbody>
        @if(count($report['transactions']))<tfoot><tr><td colspan="4">Jumlah realisasi periode ini</td><td class="r">{{ $money($totals['period']) }}</td></tr></tfoot>@endif
    </table>

    <table class="sign"><tr>
        <td>Mengetahui,<br>Kepala Desa Sungai Pinang Nibung<br><br><br><br><strong>KAMALUDIN</strong></td>
        <td>Sungai Pinang Nibung, {{ $date($report['generated_at']) }}<br>Pengelola Data / Operator Desa<br><br><br><br><strong>................................</strong></td>
    </tr></table>
</body>
</html>
