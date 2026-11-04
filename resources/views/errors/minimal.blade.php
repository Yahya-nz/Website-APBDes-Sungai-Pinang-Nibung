@extends('layouts.guest')
@section('title', $title)
@section('content')
<main class="error-page" id="main">
    <span class="emblem" style="width: 52px; height: 52px; margin-bottom: 8px"><x-icon name="landmark" /></span>
    <p class="error-code">{{ $code }}</p>
    <h1>{{ $title }}</h1>
    <p>{{ $message }}</p>
    <a class="btn btn-primary" href="{{ route('dashboard') }}">Kembali ke dashboard</a>
</main>
@endsection
