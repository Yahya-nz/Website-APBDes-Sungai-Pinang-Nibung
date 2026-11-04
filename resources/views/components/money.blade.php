@props(['value' => 0, 'decimals' => null])
@php
    $amount = (float) $value;
    $decimals ??= fmod($amount, 1.0) === 0.0 ? 0 : 2;
@endphp
<span {{ $attributes->merge(['class' => 'money']) }}>Rp{{ number_format($amount, $decimals, ',', '.') }}</span>
