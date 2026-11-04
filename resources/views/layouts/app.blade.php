<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#163d32">
    <meta name="description" content="Portal transparansi anggaran desa. Telusuri pendapatan, alokasi belanja, dan sumber data APBDes dengan lebih mudah.">
    <title>@yield('title', 'Ringkasan') · APBDes Sungai Pinang Nibung</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <script>try { if (localStorage.getItem('nibung.sidebar') === 'collapsed') document.documentElement.classList.add('sidebar-collapsed'); } catch {}</script>
    <script>if ('IntersectionObserver' in window && !matchMedia('(prefers-reduced-motion: reduce)').matches) { document.documentElement.classList.add('js-reveal'); setTimeout(() => document.querySelectorAll('[data-reveal]').forEach((el) => el.classList.add('is-visible')), 3000); }</script>
    @vite(['resources/css/app.css', 'resources/css/secondary.css', 'resources/js/app.js'])
</head>
<body>
    <a class="skip-link" href="#main">Langsung ke konten</a>
    <div class="sidebar-overlay" data-sidebar-close></div>
    <aside class="sidebar" id="sidebar" aria-label="Navigasi utama">
        <button class="sidebar-edge-toggle" type="button" data-sidebar-toggle data-sidebar-edge aria-controls="sidebar" aria-expanded="true" aria-label="Ringkas sidebar"><x-icon name="chevron-left" /></button>
        <div class="sidebar-content">
            <button class="icon-button sidebar-close" type="button" data-sidebar-dismiss aria-label="Tutup menu"><x-icon name="x" /></button>
            <a href="{{ route('dashboard') }}" class="brand" aria-label="Nibung, beranda" title="APBDes Sungai Pinang Nibung">
                <span class="sidebar-symbol"><x-icon name="landmark" /></span>
                <span class="brand-copy">APBDes<small>Sungai Pinang Nibung</small></span>
            </a>
            <form class="sidebar-search" action="{{ route('budgets.index') }}" method="GET" role="search">
                <input type="hidden" name="year" value="{{ $payload['year'] ?? 2026 }}">
                <x-icon name="search" />
                <label class="sr-only" for="sidebar-search">Cari anggaran</label>
                <input id="sidebar-search" name="q" type="search" placeholder="Cari anggaran" value="{{ request()->routeIs('budgets.*') ? request('q') : '' }}" autocomplete="off">
                <kbd aria-hidden="true" data-shortcut-hint>⌘ K</kbd>
            </form>
            <button type="button" class="sidebar-search-compact" data-sidebar-search-open aria-label="Buka pencarian anggaran" title="Cari anggaran"><x-icon name="search" /></button>
            <span class="sidebar-section-label">Menu utama</span>
            <nav class="sidebar-navigation" aria-label="Informasi anggaran">
                <div class="sidebar-dashboard-group">
                    <button type="button" class="nav-item nav-group-toggle {{ request()->routeIs('dashboard') || (request()->routeIs('budgets.*') && request('type')) ? 'active' : '' }}" data-nav-group-toggle aria-controls="dashboard-submenu" aria-expanded="true" title="Dashboard" aria-label="Dashboard">
                        <x-icon name="layout-dashboard" /><span class="nav-item-label">Dashboard</span><x-icon name="chevron-down" class="icon nav-group-chevron" />
                    </button>
                    <div class="sidebar-submenu" id="dashboard-submenu">
                        <a class="sidebar-subitem {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard', ['year' => $payload['year'] ?? 2026]) }}" @if(request()->routeIs('dashboard')) aria-current="page" @endif>Ringkasan</a>
                        <a class="sidebar-subitem {{ request()->routeIs('budgets.*') && request('type') === 'income' ? 'active' : '' }}" href="{{ route('budgets.index', ['type' => 'income', 'year' => $payload['year'] ?? 2026]) }}" @if(request()->routeIs('budgets.*') && request('type') === 'income') aria-current="page" @endif>Pendapatan</a>
                        <a class="sidebar-subitem {{ request()->routeIs('budgets.*') && request('type') === 'expense' ? 'active' : '' }}" href="{{ route('budgets.index', ['type' => 'expense', 'year' => $payload['year'] ?? 2026]) }}" @if(request()->routeIs('budgets.*') && request('type') === 'expense') aria-current="page" @endif>Belanja</a>
                    </div>
                </div>
                <a class="nav-item {{ request()->routeIs('budgets.*') && !request('type') ? 'active' : '' }}" href="{{ route('budgets.index', ['year' => $payload['year'] ?? 2026]) }}" title="Rincian anggaran" aria-label="Rincian anggaran" @if(request()->routeIs('budgets.*') && !request('type')) aria-current="page" @endif><x-icon name="files" /><span class="nav-item-label">Rincian anggaran</span></a>
                <a class="nav-item {{ request()->routeIs('quality') ? 'active' : '' }}" href="{{ route('quality', ['year' => $payload['year'] ?? 2026]) }}" title="Kualitas data" aria-label="Kualitas data" @if(request()->routeIs('quality')) aria-current="page" @endif><x-icon name="shield-check" /><span class="nav-item-label">Kualitas data</span></a>
                <a class="nav-item {{ request()->routeIs('admin.*', 'login') ? 'active' : '' }}" href="{{ route('admin.index', ['year' => $payload['year'] ?? 2026]) }}" title="Kelola data" aria-label="Kelola data" @if(request()->routeIs('admin.*', 'login')) aria-current="page" @endif><x-icon name="sliders-horizontal" /><span class="nav-item-label">Kelola data</span></a>
            </nav>
            <div class="sidebar-footer">
                <a class="nav-item {{ request()->routeIs('about') ? 'active' : '' }}" href="{{ route('about') }}" title="Tentang portal" aria-label="Tentang portal" @if(request()->routeIs('about')) aria-current="page" @endif><x-icon name="info" /><span class="nav-item-label">Tentang portal</span></a>
                <div class="sidebar-caption"><span>Kabupaten Ogan Ilir</span><span>Tahun anggaran {{ $payload['year'] ?? 2026 }}</span></div>
            </div>
        </div>
    </aside>
    <div class="sidebar-flyout" id="dashboard-flyout" popover aria-label="Menu Dashboard">
        <a class="flyout-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard', ['year' => $payload['year'] ?? 2026]) }}">Ringkasan</a>
        <a class="flyout-link {{ request()->routeIs('budgets.*') && request('type') === 'income' ? 'active' : '' }}" href="{{ route('budgets.index', ['type' => 'income', 'year' => $payload['year'] ?? 2026]) }}">Pendapatan</a>
        <a class="flyout-link {{ request()->routeIs('budgets.*') && request('type') === 'expense' ? 'active' : '' }}" href="{{ route('budgets.index', ['type' => 'expense', 'year' => $payload['year'] ?? 2026]) }}">Belanja</a>
    </div>
    <div class="app-main">
        <header class="topbar">
            <div class="topbar-left"><button class="icon-button sidebar-toggle" type="button" data-sidebar-toggle aria-controls="sidebar" aria-expanded="true" aria-label="Sembunyikan sidebar"><x-icon name="panel-left-close" /></button><span class="breadcrumb">Portal transparansi <span>/</span> <strong>@yield('title', 'Ringkasan')</strong></span></div>
            <div class="topbar-right">
                <button class="profile-trigger" type="button" popovertarget="profile-menu" aria-controls="profile-menu" aria-expanded="false" aria-label="Buka menu profil" data-profile-trigger>
                    <span class="profile-avatar">{{ auth()->check() ? mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) : 'P' }}</span>
                    <span class="profile-name">{{ auth()->check() ? auth()->user()->name : 'Pengunjung' }}</span>
                    <x-icon name="chevron-down" />
                </button>
                <div class="profile-menu" id="profile-menu" popover aria-label="Menu profil">
                    <div class="profile-menu-heading"><strong>{{ auth()->check() ? auth()->user()->name : 'Akses publik' }}</strong><span>{{ auth()->check() ? auth()->user()->email : 'Informasi anggaran desa' }}</span></div>
                    @auth
                        <a class="profile-menu-item" href="{{ route('admin.index', ['year' => $payload['year'] ?? 2026]) }}"><x-icon name="sliders-horizontal" /> Kelola data</a>
                        <form class="profile-logout" method="POST" action="{{ route('logout') }}">@csrf<button class="profile-menu-item" type="submit"><x-icon name="log-out" /> Keluar akun</button></form>
                    @else
                        <a class="profile-menu-item" href="{{ route('login') }}"><x-icon name="log-in" /> Masuk pengelola</a>
                    @endauth
                </div>
            </div>
        </header>
        <main id="main" tabindex="-1">
            @if(session('success'))<div class="flash flash-success" role="status"><x-icon name="circle-check" /><span>{{ session('success') }}</span><button class="icon-button" data-dismiss aria-label="Tutup pesan"><x-icon name="x" /></button></div>@endif
            @if($errors->any())<div class="flash flash-error" role="alert" tabindex="-1" data-error-summary><x-icon name="circle-alert" /><div><strong>Periksa kembali isianmu.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div></div>@endif
            @yield('content')
            <footer class="page-footer"><span><strong>Pemerintah Desa Sungai Pinang Nibung</strong> Kecamatan Sungai Pinang, Kabupaten Ogan Ilir</span><span><a href="{{ route('about') }}">Tentang portal</a> <span class="footer-dot">·</span> <a href="{{ route('export', ['year' => $payload['year'] ?? 2026]) }}">Unduh CSV {{ $payload['year'] ?? 2026 }}</a></span></footer>
        </main>
    </div>
    @stack('scripts')
</body>
</html>
