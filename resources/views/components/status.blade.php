@props(['status' => 'unverified'])
@php
    $labels = ['verified' => 'Terverifikasi', 'unverified' => 'Belum diverifikasi', 'unavailable' => 'Belum tersedia'];
@endphp
<span {{ $attributes->merge(['class' => 'status status-'.$status]) }}><span class="status-dot"></span>{{ $labels[$status] ?? 'Belum diverifikasi' }}</span>
