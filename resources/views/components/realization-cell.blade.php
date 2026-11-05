@props(['row'])
@if($row['realization_counted'] ?? false)
    @php $percent = $row['realization_percent'] ?? 0; @endphp
    <div class="mini-progress"><x-progress :value="$percent" :color="$percent >= 100 ? 'green' : 'blue'" :label="'Realisasi '.$row['description']" /><span>{{ number_format($percent, 0, ',', '.') }}%</span></div>
@else
    <span class="badge badge-gray" title="Realisasi dihitung setelah kegiatan diverifikasi">Belum dihitung</span>
@endif
