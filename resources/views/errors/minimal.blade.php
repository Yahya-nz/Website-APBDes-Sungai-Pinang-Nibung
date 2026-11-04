@extends('layouts.guest')
@section('title', $title)
@section('content')
<main class="error-page" id="main">
    <x-logo class="brand-logo" />
    <p class="error-code">{{ $code }}</p>
    <h1>{{ $title }}</h1>
    <p>{{ $message }}</p>
    <a class="button button-primary" href="{{ route('dashboard') }}">Kembali ke ringkasan</a>
</main>
@endsection
