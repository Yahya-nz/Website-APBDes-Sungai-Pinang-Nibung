@props(['value' => 0, 'decimals' => 0])
<span {{ $attributes->merge(['class' => 'money']) }}>Rp{{ number_format((float) $value, $decimals, ',', '.') }}</span>
