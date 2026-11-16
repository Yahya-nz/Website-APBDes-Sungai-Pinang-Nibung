@props(['results'])
<div {{ $attributes->merge(['class' => 'vote-result']) }}>
    @if($results['total'] > 0)
        <div class="vote-row">
            <div class="vote-head"><span><x-icon name="thumbs-up" /> Setuju</span><strong>{{ $results['agree'] }} suara · {{ number_format($results['agree_percent'], 1, ',', '.') }}%</strong></div>
            <x-progress :value="$results['agree_percent']" color="green" label="Persentase setuju" />
        </div>
        <div class="vote-row">
            <div class="vote-head"><span><x-icon name="thumbs-down" /> Tidak setuju</span><strong>{{ $results['disagree'] }} suara · {{ number_format($results['disagree_percent'], 1, ',', '.') }}%</strong></div>
            <x-progress :value="$results['disagree_percent']" color="red" label="Persentase tidak setuju" />
        </div>
        <p class="hint">Total {{ $results['total'] }} suara warga.</p>
    @else
        <p class="hint">Belum ada suara. Jadilah yang pertama menyampaikan pendapat.</p>
    @endif
</div>
