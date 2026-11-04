@php
    $showSource = $showSource ?? true;
    $search = $payload['search'] ?? new \App\Support\BudgetSearch('');
@endphp
<div class="table-scroll">
    <table class="table table-responsive">
        <thead>
            <tr>
                <th scope="col">Kegiatan / komponen</th>
                <th scope="col">Bidang / sumber</th>
                <th scope="col" class="num">Anggaran</th>
                <th scope="col">Status</th>
                @if($showSource)<th scope="col">Dokumen sumber</th>@endif
            </tr>
        </thead>
        <tbody>
        @forelse($budgetRows as $row)
            <tr style="--row: {{ $loop->index }}">
                <td data-label="Kegiatan">
                    <span class="row-title"><span class="type-dot type-{{ $row['type'] }}" title="{{ $row['type'] === 'income' ? 'Pendapatan' : 'Belanja' }}"></span>{{ $search->highlight($row['description']) }}</span>
                    <span class="row-sub">{{ $row['type'] === 'income' ? 'Pendapatan' : 'Belanja' }} · {{ $row['year'] }}@if($row['is_demo']) · <span class="demo-tag">contoh</span>@endif</span>
                </td>
                <td data-label="Bidang">{{ $search->highlight($row['category']) }}</td>
                <td data-label="Anggaran" class="num"><x-money :value="$row['amount']" /></td>
                <td data-label="Status"><x-status :status="$row['verification_status']" /></td>
                @if($showSource)
                    <td data-label="Sumber" class="source-cell">{{ $search->highlight($row['source']) }}<span class="row-sub">Diperbarui {{ \Carbon\Carbon::parse($row['updated_at'])->timezone('Asia/Jakarta')->translatedFormat('j M Y') }}</span></td>
                @endif
            </tr>
        @empty
            <tr class="is-empty-row">
                <td colspan="{{ $showSource ? 5 : 4 }}">
                    <div class="empty">
                        <x-icon name="search-x" />
                        <strong>@if(! $search->isEmpty())Tidak ada hasil untuk “{{ implode(' ', $search->terms()) }}”@else Tidak ada baris yang cocok @endif</strong>
                        <p>@if(! $search->isEmpty())Semua kata harus muncul di uraian, bidang, atau sumber. Coba kurangi kata atau pakai kata yang lebih umum, misalnya “jalan” atau “posyandu”.@else Coba ubah atau hapus filter yang sedang dipakai.@endif</p>
                        <a href="{{ route('budgets.index', ['year' => $payload['year'] ?? null]) }}" class="button button-outline button-small">Hapus filter</a>
                    </div>
                </td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>
