@extends('layouts.guest')
@section('title', $title)
@section('content')
<main class="error-page" id="main">
    <span class="auth-brand-icon error-icon"><x-icon name="landmark" /></span>
    <p class="error-code">{{ $code }}</p>
    <h1>{{ $title }}</h1>
    <p>{{ $message }}</p>
    <a class="auth-submit error-button" href="{{ route('dashboard') }}">Kembali ke ringkasan</a>
</main>
@endsection
