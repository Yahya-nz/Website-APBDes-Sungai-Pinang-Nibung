@php
    $showSource = $showSource ?? true;
    $search = $payload['search'] ?? new \App\Support\BudgetSearch('');
@endphp
<div class="table-wrap">
    <table class="data-table stack-on-mobile">
        <thead>
            <tr>
                <th>Komponen / kegiatan</th>
                <th>Kategori</th>
                <th class="text-right">Anggaran</th>
                <th>Status data</th>
                @if($showSource)<th>Sumber</th>@endif
            </tr>
        </thead>
        <tbody>
        @forelse($budgetRows as $row)
            <tr style="--row: {{ $loop->index }}">
                <td data-label="Kegiatan">
                    <div class="table-component">
                        <span class="table-icon {{ $row['type'] === 'income' ? 'income' : 'expense' }}"><x-icon :name="$row['type'] === 'income' ? 'arrow-down-left' : 'arrow-up-right'" /></span>
                        <div>
                            <strong>{{ $search->highlight($row['description']) }}</strong>
                            <small>{{ $row['type'] === 'income' ? 'Pendapatan' : 'Belanja' }} <span>·</span> {{ $row['year'] }} @if($row['is_demo'])<span class="demo-inline">Contoh</span>@endif</small>
                        </div>
                    </div>
                </td>
                <td data-label="Kategori"><span class="category-label">{{ $search->highlight($row['category']) }}</span></td>
                <td data-label="Anggaran" class="text-right table-amount"><x-money :value="$row['amount']" /></td>
                <td data-label="Status"><x-status :status="$row['verification_status']" /></td>
                @if($showSource)
                    <td data-label="Sumber" class="source-cell">{{ $search->highlight($row['source']) }}<small>{{ \Carbon\Carbon::parse($row['updated_at'])->locale('id')->translatedFormat('d M Y') }}</small></td>
                @endif
            </tr>
        @empty
            <tr class="empty-row">
                <td colspan="{{ $showSource ? 5 : 4 }}">
                    <div class="empty-state">
                        <x-icon name="search-x" />
                        <h3>@if(! $search->isEmpty())Tidak ada hasil untuk “{{ implode(' ', $search->terms()) }}”@else Tidak ada data yang cocok @endif</h3>
                        <p>@if(! $search->isEmpty())Semua kata harus muncul di uraian, kategori, atau sumber. Coba kurangi kata atau pakai kata yang lebih umum, misalnya “jalan” atau “posyandu”.@else Coba ubah atau hapus filter yang sedang dipakai.@endif</p>
                        <a href="{{ route('budgets.index', ['year' => $payload['year'] ?? null]) }}" class="btn btn-secondary btn-sm">Atur ulang filter</a>
                    </div>
                </td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>
