@props(['status' => 'unverified'])
@php
    $labels = ['verified' => ['Terverifikasi', 'badge-green'], 'unverified' => ['Belum diverifikasi', 'badge-amber'], 'unavailable' => ['Belum tersedia', 'badge-gray']];
    [$label, $class] = $labels[$status] ?? $labels['unverified'];
@endphp
<span {{ $attributes->merge(['class' => 'badge status '.$class]) }}>{{ $label }}</span>
