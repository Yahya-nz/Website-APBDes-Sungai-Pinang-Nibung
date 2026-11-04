@php
    $selectedYear = $payload['year'] ?? 2026;
    $isBudgetPage = request()->routeIs('budgets.*');
    $budgetType = in_array(request('type'), ['income', 'expense'], true) ? request('type') : null;
    $dashboardActive = request()->routeIs('dashboard') || ($isBudgetPage && $budgetType);
    $dashboardLinks = [
        ['label' => 'Ringkasan', 'href' => route('dashboard', ['year' => $selectedYear]), 'active' => request()->routeIs('dashboard')],
        ['label' => 'Pendapatan', 'href' => route('budgets.index', ['type' => 'income', 'year' => $selectedYear]), 'active' => $isBudgetPage && $budgetType === 'income'],
        ['label' => 'Belanja', 'href' => route('budgets.index', ['type' => 'expense', 'year' => $selectedYear]), 'active' => $isBudgetPage && $budgetType === 'expense'],
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
    <script>try { if (localStorage.getItem('nibung.sidebar') === 'collapsed') document.documentElement.classList.add('sidebar-collapsed'); } catch {}</script>
    <script>if ('IntersectionObserver' in window && !matchMedia('(prefers-reduced-motion: reduce)').matches) { document.documentElement.classList.add('js-reveal'); setTimeout(() => document.querySelectorAll('[data-reveal]').forEach((el) => el.classList.add('is-visible')), 3000); }</script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <a class="skip-link" href="#main">Langsung ke isi halaman</a>
    <div class="sidebar-overlay" data-sidebar-close></div>

    <aside class="sidebar" id="sidebar" aria-label="Navigasi utama">
        <button class="sidebar-edge-toggle" type="button" data-sidebar-toggle data-sidebar-edge aria-controls="sidebar" aria-expanded="true" aria-label="Ringkas sidebar"><x-icon name="chevron-left" /></button>
        <div class="sidebar-content">
            <button class="icon-button sidebar-close" type="button" data-sidebar-close aria-label="Tutup menu"><x-icon name="x" /></button>
            <a href="{{ route('dashboard') }}" class="brand" title="APBDes Sungai Pinang Nibung">
                <span class="sidebar-symbol"><x-icon name="landmark" /></span>
                <span class="brand-copy">APBDes<small>Sungai Pinang Nibung</small></span>
            </a>
            <form class="sidebar-search" action="{{ route('budgets.index') }}" method="GET" role="search">
                <input type="hidden" name="year" value="{{ $selectedYear }}">
                <x-icon name="search" />
                <label class="sr-only" for="sidebar-search">Cari kegiatan, bidang, atau sumber</label>
                <input id="sidebar-search" name="q" type="search" placeholder="Cari anggaran" value="{{ $isBudgetPage ? request('q') : '' }}" autocomplete="off" data-global-search>
                <kbd aria-hidden="true" data-shortcut-hint>⌘ K</kbd>
            </form>
            <button type="button" class="sidebar-search-compact" data-sidebar-search-open aria-label="Buka pencarian anggaran" title="Cari anggaran"><x-icon name="search" /></button>
            <span class="sidebar-section-label">UTAMA</span>
            <nav class="sidebar-navigation" aria-label="Informasi anggaran">
                <div class="sidebar-dashboard-group">
                    <button type="button" class="nav-item nav-group-toggle {{ $dashboardActive ? 'active' : '' }}" data-nav-group-toggle aria-controls="dashboard-submenu" aria-expanded="true" title="Dashboard">
                        <x-icon name="layout-dashboard" /><span class="nav-item-label">Dashboard</span><x-icon name="chevron-down" class="icon nav-group-chevron" />
                    </button>
                    <div class="sidebar-submenu" id="dashboard-submenu">
                        @foreach($dashboardLinks as $link)
                            <a class="sidebar-subitem {{ $link['active'] ? 'active' : '' }}" href="{{ $link['href'] }}" @if($link['active']) aria-current="page" @endif>{{ $link['label'] }}</a>
                        @endforeach
                    </div>
                </div>
                <a class="nav-item {{ $isBudgetPage && ! $budgetType ? 'active' : '' }}" href="{{ route('budgets.index', ['year' => $selectedYear]) }}" title="Rincian anggaran" @if($isBudgetPage && ! $budgetType) aria-current="page" @endif><x-icon name="files" /><span class="nav-item-label">Rincian anggaran</span></a>
                <a class="nav-item {{ request()->routeIs('quality') ? 'active' : '' }}" href="{{ route('quality', ['year' => $selectedYear]) }}" title="Kualitas data" @if(request()->routeIs('quality')) aria-current="page" @endif><x-icon name="shield-check" /><span class="nav-item-label">Kualitas data</span></a>
                <a class="nav-item {{ request()->routeIs('admin.*', 'login') ? 'active' : '' }}" href="{{ route('admin.index', ['year' => $selectedYear]) }}" title="Kelola data" @if(request()->routeIs('admin.*', 'login')) aria-current="page" @endif><x-icon name="sliders-horizontal" /><span class="nav-item-label">Kelola data</span></a>
            </nav>
            <div class="sidebar-footer">
                <a class="nav-item {{ request()->routeIs('about') ? 'active' : '' }}" href="{{ route('about') }}" title="Tentang portal" @if(request()->routeIs('about')) aria-current="page" @endif><x-icon name="info" /><span class="nav-item-label">Tentang portal</span></a>
                <div class="sidebar-caption"><span>Kabupaten Ogan Ilir</span><span>Tahun anggaran {{ $selectedYear }}</span></div>
            </div>
        </div>
    </aside>
    <div class="sidebar-flyout" id="dashboard-flyout" popover aria-label="Menu Dashboard">
        @foreach($dashboardLinks as $link)
            <a class="flyout-link {{ $link['active'] ? 'active' : '' }}" href="{{ $link['href'] }}">{{ $link['label'] }}</a>
        @endforeach
    </div>

    <div class="app-main">
        <header class="topbar">
            <div class="topbar-left">
                <button class="icon-button sidebar-toggle" type="button" data-sidebar-toggle aria-controls="sidebar" aria-expanded="true" aria-label="Sembunyikan sidebar"><x-icon name="panel-left-close" /></button>
                <span class="breadcrumb"><span class="crumb-root">Portal transparansi</span><span class="crumb-sep" aria-hidden="true">/</span><strong>@yield('title', 'Ringkasan')</strong></span>
            </div>
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
                    <a href="{{ route('quality', ['year' => $selectedYear]) }}">Kualitas data</a>
                    <a href="{{ route('export', ['year' => $selectedYear]) }}">Unduh CSV {{ $selectedYear }}</a>
                </div>
            </footer>
        </main>
    </div>
    @stack('scripts')
</body>
</html>
