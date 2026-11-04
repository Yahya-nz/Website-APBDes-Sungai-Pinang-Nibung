@extends('layouts.app')
@section('title', 'Catat Realisasi')
@section('heading', 'Catat Realisasi Anggaran')
@section('content')
@php $summary = $payload['summary']; $rows = collect($payload['rows'])->where('type', 'expense')->keyBy('id'); @endphp
<div class="page-head" data-reveal>
    <div>
        <nav class="crumbs" aria-label="Lokasi"><span>Perangkat desa</span><span>/</span><span>Catat realisasi</span></nav>
        <h1>Catat Realisasi</h1>
        <p>Setiap pengeluaran yang dicatat langsung memperbarui grafik realisasi dan muncul di notifikasi warga.</p>
    </div>
    <div class="page-actions"><a class="btn btn-secondary" href="{{ route('realizations.index', ['year' => $payload['year']]) }}"><x-icon name="eye" /> Lihat halaman publik</a></div>
</div>

<section class="grid grid-5-7">
    <article class="card" data-reveal>
        <header class="card-head"><div><h2>Pengeluaran baru</h2><p>Isi sesuai kuitansi atau bukti pembayaran.</p></div></header>
        <form class="card-body" method="POST" action="{{ route('admin.realizations.store') }}" style="display: grid; gap: 16px">
            @csrf
            <div class="field">
                <label for="realization-activity">Kegiatan</label>
                <select class="select" id="realization-activity" name="budget_line_id" required>
                    <option value="">Pilih kegiatan</option>
                    @foreach($activities->groupBy('category') as $category => $items)
                        <optgroup label="{{ $category }}">
                            @foreach($items as $activity)
                                @php $row = $rows->get($activity->id); @endphp
                                <option value="{{ $activity->id }}" @selected((int) old('budget_line_id') === $activity->id)>{{ $activity->description }} — sisa Rp{{ number_format((float) $activity->amount - ($row['realization'] ?? 0), 0, ',', '.') }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
                @error('budget_line_id')<span class="field-error">{{ $message }}</span>@enderror
            </div>
            <div class="form-grid">
                <div class="field"><label for="realization-date">Tanggal</label><input class="input" id="realization-date" type="date" name="spent_on" max="{{ now()->toDateString() }}" value="{{ old('spent_on', now()->toDateString()) }}" required>@error('spent_on')<span class="field-error">{{ $message }}</span>@enderror</div>
                <div class="field"><label for="realization-amount">Nominal (Rp)</label><input class="input" id="realization-amount" type="number" name="amount" min="1" step="0.01" inputmode="decimal" value="{{ old('amount') }}" required data-money-preview><span class="money-preview" data-money-output></span>@error('amount')<span class="field-error">{{ $message }}</span>@enderror</div>
            </div>
            <div class="field"><label for="realization-description">Keterangan</label><input class="input" id="realization-description" name="description" maxlength="255" value="{{ old('description') }}" required placeholder="Misalnya: Pembayaran termin 1 pembangunan jalan"></div>
            <label class="check"><input type="checkbox" name="is_demo" value="1" @checked(old('is_demo'))><span>Tandai sebagai data contoh</span></label>
            <div class="form-actions"><span class="hint">Tercatat atas nama {{ auth()->user()->name }}</span><button class="btn btn-primary" type="submit"><x-icon name="check" /> Simpan pengeluaran</button></div>
        </form>
    </article>

    <article class="card" data-reveal>
        <header class="card-head"><div><h2>Pengeluaran tercatat</h2><p>{{ $transactions->total() }} transaksi · total <x-money :value="$summary['realization'] ?? 0" /> ({{ number_format($summary['absorption'] ?? 0, 1, ',', '.') }}% anggaran)</p></div></header>
        <div class="table-wrap">
            <table class="table stack">
                <thead><tr><th>Tanggal</th><th>Kegiatan</th><th class="text-right">Nominal</th><th><span class="sr-only">Aksi</span></th></tr></thead>
                <tbody>
                @forelse($transactions as $item)
                    <tr style="--row: {{ $loop->index }}">
                        <td data-label="Tanggal">{{ $item->spent_on->translatedFormat('j M Y') }}</td>
                        <td class="cell-main"><span class="strong">{{ $item->budgetLine?->description }}</span><span class="sub">{{ $item->description }}@if($item->is_demo) · <span class="badge badge-demo">contoh</span>@endif</span></td>
                        <td class="text-right amount" data-label="Nominal"><x-money :value="$item->amount" :decimals="2" /></td>
                        <td class="cell-actions" style="text-align: right"><form method="POST" action="{{ route('admin.realizations.destroy', $item) }}" data-confirm="Catatan pengeluaran Rp{{ number_format((float) $item->amount, 0, ',', '.') }} untuk {{ $item->budgetLine?->description }} akan dihapus.">@csrf @method('DELETE')<button class="icon-btn icon-btn-danger" type="submit" aria-label="Hapus pengeluaran" title="Hapus"><x-icon name="trash-2" /></button></form></td>
                    </tr>
                @empty
                    <tr><td colspan="4"><div class="empty"><x-icon name="receipt-text" /><p>Belum ada pengeluaran yang dicatat.</p></div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($transactions->hasPages())
            <div class="pagination"><span>Halaman {{ $transactions->currentPage() }} dari {{ $transactions->lastPage() }}</span><nav>@if($transactions->previousPageUrl())<a class="btn btn-secondary btn-sm" href="{{ $transactions->previousPageUrl() }}"><x-icon name="chevron-left" /> Sebelumnya</a>@endif @if($transactions->nextPageUrl())<a class="btn btn-secondary btn-sm" href="{{ $transactions->nextPageUrl() }}">Berikutnya <x-icon name="chevron-right" /></a>@endif</nav></div>
        @endif
    </article>
</section>
<x-confirm-dialog />
@endsection
