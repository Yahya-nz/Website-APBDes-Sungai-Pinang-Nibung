@props(['value' => 0, 'color' => 'blue', 'size' => null, 'label' => null])
@php $value = max(0, min(100, (float) $value)); @endphp
<div {{ $attributes->merge(['class' => 'progress progress-'.$color.($size ? ' progress-'.$size : '')]) }} role="progressbar" aria-valuenow="{{ round($value, 1) }}" aria-valuemin="0" aria-valuemax="100" @if($label) aria-label="{{ $label }}" @endif><span style="--value: {{ $value }}%"></span></div>
