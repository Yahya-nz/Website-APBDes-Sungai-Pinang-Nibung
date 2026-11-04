@php
    $selectedYear = $payload['year'] ?? 2026;
    $isBudgetPage = request()->routeIs('budgets.*');
    $navigation = [
        ['label' => 'Ringkasan', 'icon' => 'layout-grid', 'href' => route('dashboard', ['year' => $selectedYear]), 'active' => request()->routeIs('dashboard')],
        ['label' => 'Pendapatan', 'icon' => 'arrow-down-to-line', 'href' => route('budgets.index', ['type' => 'income', 'year' => $selectedYear]), 'active' => $isBudgetPage && request('type') === 'income'],
        ['label' => 'Belanja', 'icon' => 'arrow-up-from-line', 'href' => route('budgets.index', ['type' => 'expense', 'year' => $selectedYear]), 'active' => $isBudgetPage && request('type') === 'expense'],
        ['label' => 'Semua rincian', 'icon' => 'table-2', 'href' => route('budgets.index', ['year' => $selectedYear]), 'active' => $isBudgetPage && ! in_array(request('type'), ['income', 'expense'], true)],
        ['label' => 'Sumber data', 'icon' => 'file-check-2', 'href' => route('quality', ['year' => $selectedYear]), 'active' => request()->routeIs('quality')],
    ];
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0d5163">
    <meta name="description" content="Portal APBDes Desa Sungai Pinang Nibung: ringkasan pendapatan, belanja per bidang, dan rincian anggaran desa beserta sumber datanya.">
    <title>@yield('title', 'Ringkasan') · APBDes Sungai Pinang Nibung</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <script>try { if (localStorage.getItem('apbdes.sidebar') === 'collapsed') document.documentElement.classList.add('sidebar-collapsed'); } catch {}</script>
    <script>if ('IntersectionObserver' in window && !matchMedia('(prefers-reduced-motion: reduce)').matches) { document.documentElement.classList.add('js-reveal'); setTimeout(() => document.querySelectorAll('[data-reveal]').forEach((el) => el.classList.add('is-visible')), 3000); }</script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <a class="skip-link" href="#main">Langsung ke isi halaman</a>
    <div class="sidebar-overlay" data-sidebar-close></div>

    <aside class="sidebar" id="sidebar" aria-label="Navigasi utama">
        <div class="sidebar-head">
            <a href="{{ route('dashboard') }}" class="brand" title="APBDes Desa Sungai Pinang Nibung">
                <x-logo class="brand-logo" />
                <span class="brand-text"><strong>APBDes</strong><span>Sungai Pinang Nibung</span></span>
            </a>
            <button class="icon-button sidebar-dismiss" type="button" data-sidebar-close aria-label="Tutup navigasi"><x-icon name="x" /></button>
        </div>

        <nav class="sidebar-nav" aria-label="Informasi anggaran">
            <span class="nav-heading">Anggaran {{ $selectedYear }}</span>
            @foreach($navigation as $item)
                <a class="nav-link {{ $item['active'] ? 'is-active' : '' }}" href="{{ $item['href'] }}" title="{{ $item['label'] }}" @if($item['active']) aria-current="page" @endif>
                    <x-icon :name="$item['icon']" /><span class="nav-text">{{ $item['label'] }}</span>
                </a>
            @endforeach

            <span class="nav-heading">Lainnya</span>
            <a class="nav-link {{ request()->routeIs('about') ? 'is-active' : '' }}" href="{{ route('about') }}" title="Tentang portal" @if(request()->routeIs('about')) aria-current="page" @endif><x-icon name="info" /><span class="nav-text">Tentang portal</span></a>
            <a class="nav-link {{ request()->routeIs('admin.*', 'login') ? 'is-active' : '' }}" href="{{ route('admin.index', ['year' => $selectedYear]) }}" title="Kelola data" @if(request()->routeIs('admin.*', 'login')) aria-current="page" @endif><x-icon name="pencil-ruler" /><span class="nav-text">Kelola data</span>@guest<x-icon name="lock" class="icon nav-lock" />@endguest</a>
        </nav>

        <div class="sidebar-foot">
            <p class="sidebar-region">Kec. Sungai Pinang<br>Kab. Ogan Ilir, Sumatera Selatan</p>
            <button class="collapse-button" type="button" data-sidebar-collapse aria-controls="sidebar" aria-expanded="true">
                <x-icon name="chevrons-left" /><span class="nav-text">Ciutkan menu</span>
            </button>
        </div>
    </aside>

    <div class="app-main">
        <header class="topbar">
            <button class="icon-button menu-button" type="button" data-sidebar-open aria-controls="sidebar" aria-expanded="false" aria-label="Buka navigasi"><x-icon name="menu" /></button>
            <form class="topbar-search" action="{{ route('budgets.index') }}" method="GET" role="search">
                <input type="hidden" name="year" value="{{ $selectedYear }}">
                <x-icon name="search" />
                <label class="sr-only" for="global-search">Cari kegiatan, kategori, atau sumber</label>
                <input id="global-search" name="q" type="search" placeholder="Cari kegiatan…" value="{{ $isBudgetPage ? request('q') : '' }}" autocomplete="off" data-global-search>
                <kbd aria-hidden="true">/</kbd>
            </form>
            <div class="topbar-end">
                <button class="profile-trigger" type="button" popovertarget="profile-menu" aria-controls="profile-menu" aria-expanded="false" data-profile-trigger>
                    <span class="avatar" aria-hidden="true">@auth{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}@else<x-icon name="user-round" />@endauth</span>
                    <span class="profile-name">{{ auth()->check() ? auth()->user()->name : 'Pengunjung' }}</span>
                    <x-icon name="chevron-down" class="icon profile-chevron" />
                </button>
                <div class="profile-menu" id="profile-menu" popover aria-label="Menu akun">
                    <div class="profile-menu-head">
                        <strong>{{ auth()->check() ? auth()->user()->name : 'Akses publik' }}</strong>
                        <span>{{ auth()->check() ? auth()->user()->email : 'Anda melihat data tanpa login.' }}</span>
                    </div>
                    @auth
                        <a class="menu-item" href="{{ route('admin.index', ['year' => $selectedYear]) }}"><x-icon name="pencil-ruler" /> Kelola data</a>
                        <form method="POST" action="{{ route('logout') }}">@csrf<button class="menu-item" type="submit"><x-icon name="log-out" /> Keluar akun</button></form>
                    @else
                        <a class="menu-item" href="{{ route('login') }}"><x-icon name="log-in" /> Masuk pengelola</a>
                    @endauth
                </div>
            </div>
        </header>

        <main id="main" tabindex="-1">
            @if(session('success'))
                <div class="toast" role="status" data-toast><x-icon name="circle-check" /><span>{{ session('success') }}</span><button class="icon-button" type="button" data-toast-close aria-label="Tutup pesan"><x-icon name="x" /></button></div>
            @endif
            @if($errors->any())
                <div class="alert alert-error" role="alert" tabindex="-1" data-error-summary><x-icon name="circle-alert" /><div><strong>Ada isian yang perlu diperbaiki.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div></div>
            @endif

            @yield('content')

            <footer class="site-footer">
                <div>
                    <strong>Pemerintah Desa Sungai Pinang Nibung</strong>
                    <span>Kecamatan Sungai Pinang, Kabupaten Ogan Ilir, Sumatera Selatan</span>
                </div>
                <div class="site-footer-links">
                    <a href="{{ route('about') }}">Tentang portal</a>
                    <a href="{{ route('quality', ['year' => $selectedYear]) }}">Sumber data</a>
                    <a href="{{ route('export', ['year' => $selectedYear]) }}">Unduh CSV {{ $selectedYear }}</a>
                </div>
            </footer>
        </main>
    </div>
    @stack('scripts')
</body>
</html>
