@props(['years', 'selected', 'id' => 'year-select', 'action' => null, 'keep' => []])
<form method="GET" @if($action) action="{{ $action }}" @endif class="year-select">
    @foreach($keep as $name => $value)
        @if(filled($value))<input type="hidden" name="{{ $name }}" value="{{ $value }}">@endif
    @endforeach
    <label for="{{ $id }}">Tahun anggaran</label>
    <select id="{{ $id }}" name="year" data-auto-submit>
        @foreach($years as $year)<option value="{{ $year }}" @selected($year == $selected)>{{ $year }}</option>@endforeach
    </select>
    <x-icon name="chevron-down" class="icon year-select-chevron" />
    <noscript><button class="button button-quiet" type="submit">Tampilkan</button></noscript>
</form>
