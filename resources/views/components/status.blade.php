@props(['status' => 'unverified'])
@php
    $labels = ['verified' => 'Terverifikasi', 'unverified' => 'Belum diverifikasi', 'unavailable' => 'Belum tersedia'];
    $status = array_key_exists($status, $labels) ? $status : 'unverified';
@endphp
<span {{ $attributes->merge(['class' => 'status status-'.$status]) }}>{{ $labels[$status] }}</span>
