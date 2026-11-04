<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0d5163">
    <title>@yield('title') · APBDes Sungai Pinang Nibung</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <script>if ('IntersectionObserver' in window && !matchMedia('(prefers-reduced-motion: reduce)').matches) { document.documentElement.classList.add('js-reveal'); setTimeout(() => document.querySelectorAll('[data-reveal]').forEach((el) => el.classList.add('is-visible')), 3000); }</script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="guest">
    @yield('content')
</body>
</html>
