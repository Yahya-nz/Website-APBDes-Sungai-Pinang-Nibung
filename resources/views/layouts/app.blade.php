@php
    $selectedYear = $payload['year'] ?? 2026;
    $years = $payload['years'] ?? null;
    $isBudgetPage = request()->routeIs('budgets.*');
    $budgetType = in_array(request('type'), ['income', 'expense'], true) ? request('type') : null;
    $apbdesLinks = [
        ['label' => 'Pendapatan', 'href' => route('budgets.index', ['type' => 'income', 'year' => $selectedYear]), 'active' => $isBudgetPage && $budgetType === 'income'],
        ['label' => 'Belanja', 'href' => route('budgets.index', ['type' => 'expense', 'year' => $selectedYear]), 'active' => $isBudgetPage && $budgetType === 'expense'],
        ['label' => 'Pembiayaan', 'href' => route('financing', ['year' => $selectedYear]), 'active' => request()->routeIs('financing')],
        ['label' => 'Rincian Kegiatan', 'href' => route('budgets.index', ['year' => $selectedYear]), 'active' => $isBudgetPage && ! $budgetType],
    ];
    $apbdesActive = collect($apbdesLinks)->contains('active', true);
    $mainLinks = [
        ['label' => 'Dashboard', 'icon' => 'layout-dashboard', 'route' => 'dashboard'],
        ['label' => 'Profil Desa', 'icon' => 'landmark', 'route' => 'profile'],
    ];
    $monitorLinks = [
        ['label' => 'Realisasi Anggaran', 'icon' => 'chart-no-axes-column-increasing', 'route' => 'realizations.index'],
        ['label' => 'Grafik & Analisis', 'icon' => 'chart-pie', 'route' => 'analytics'],
        ['label' => 'Laporan', 'icon' => 'file-text', 'route' => 'reports.index'],
    ];
    $serviceLinks = [
        ['label' => 'Pengaduan Warga', 'icon' => 'message-square-text', 'route' => 'complaints.create', 'active' => request()->routeIs('complaints.*')],
        ['label' => 'Status Verifikasi', 'icon' => 'shield-check', 'route' => 'quality'],
        ['label' => 'Tentang Portal', 'icon' => 'info', 'route' => 'about'],
    ];
    $canManage = auth()->user()?->can('manage-data') ?? false;
    $newComplaints = $canManage ? \App\Models\Complaint::where('status', 'new')->count() : 0;
    $adminLinks = [
        ['label' => 'Dashboard Pengelola', 'icon' => 'gauge', 'href' => route('admin.index', ['year' => $selectedYear]), 'active' => request()->routeIs('admin.index')],
        ['label' => 'Pengelolaan Data', 'icon' => 'database', 'href' => route('admin.budgets.index', ['year' => $selectedYear]), 'active' => request()->routeIs('admin.budgets.*')],
        ['label' => 'Catat Realisasi', 'icon' => 'receipt-text', 'href' => route('admin.realizations.index', ['year' => $selectedYear]), 'active' => request()->routeIs('admin.realizations.*')],
        ['label' => 'Pengaduan Masuk', 'icon' => 'inbox', 'href' => route('admin.complaints.index'), 'active' => request()->routeIs('admin.complaints.*'), 'badge' => $newComplaints],
        ['label' => 'Pengguna', 'icon' => 'users-round', 'href' => route('admin.users.index'), 'active' => request()->routeIs('admin.users.*')],
        ['label' => 'Riwayat Aktivitas', 'icon' => 'history', 'href' => route('admin.activities.index'), 'active' => request()->routeIs('admin.activities.*')],
        ['label' => 'Pengaturan', 'icon' => 'settings', 'href' => route('admin.settings'), 'active' => request()->routeIs('admin.settings*')],
    ];
    $notifications = $notificationFeed['items'] ?? [];
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0f2342">
    <meta name="description" content="Dashboard Monitoring APBDes Desa Sungai Pinang Nibung: pendapatan, belanja, realisasi anggaran, laporan, dan pengaduan warga.">
    <title>@yield('title', 'Dashboard') · APBDes Sungai Pinang Nibung</title>
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
            <a href="{{ route('dashboard') }}" class="emblem" title="Dashboard"><x-icon name="landmark" /></a>
            <a href="{{ route('dashboard') }}" class="brand-text"><strong>Desa Sungai Pinang Nibung</strong><span>Kec. Sungai Pinang, Kab. Ogan Ilir</span></a>
            <button class="icon-btn sidebar-close" type="button" data-sidebar-close aria-label="Tutup menu"><x-icon name="x" /></button>
        </div>

        <nav class="sidebar-nav" aria-label="Menu">
            <span class="nav-label">Menu utama</span>
            @foreach($mainLinks as $link)
                @php $active = request()->routeIs($link['route']); @endphp
                <a class="nav-link {{ $active ? 'is-active' : '' }}" href="{{ route($link['route'], ['year' => $selectedYear]) }}" title="{{ $link['label'] }}" @if($active) aria-current="page" @endif><x-icon :name="$link['icon']" /><span class="nav-text">{{ $link['label'] }}</span></a>
            @endforeach
            <button type="button" class="nav-link nav-group {{ $apbdesActive ? 'is-active' : '' }}" data-nav-group aria-expanded="{{ $apbdesActive || ! request()->routeIs('dashboard') ? 'true' : 'false' }}" aria-controls="apbdes-menu" title="APBDes {{ $selectedYear }}">
                <x-icon name="wallet-cards" /><span class="nav-text">APBDes {{ $selectedYear }}</span><x-icon name="chevron-down" class="icon nav-chevron" />
            </button>
            <div class="subnav" id="apbdes-menu" @if(! $apbdesActive && request()->routeIs('dashboard')) hidden @endif>
                @foreach($apbdesLinks as $link)
                    <a href="{{ $link['href'] }}" class="{{ $link['active'] ? 'is-active' : '' }}" @if($link['active']) aria-current="page" @endif>{{ $link['label'] }}</a>
                @endforeach
            </div>
            @foreach($monitorLinks as $link)
                @php $active = request()->routeIs($link['route']); @endphp
                <a class="nav-link {{ $active ? 'is-active' : '' }}" href="{{ route($link['route'], ['year' => $selectedYear]) }}" title="{{ $link['label'] }}" @if($active) aria-current="page" @endif><x-icon :name="$link['icon']" /><span class="nav-text">{{ $link['label'] }}</span></a>
            @endforeach

            <span class="nav-label">Layanan warga</span>
            @foreach($serviceLinks as $link)
                @php $active = $link['active'] ?? request()->routeIs($link['route']); @endphp
                <a class="nav-link {{ $active ? 'is-active' : '' }}" href="{{ route($link['route']) }}" title="{{ $link['label'] }}" @if($active) aria-current="page" @endif><x-icon :name="$link['icon']" /><span class="nav-text">{{ $link['label'] }}</span></a>
            @endforeach

            <span class="nav-label">Perangkat desa</span>
            @if($canManage)
                @foreach($adminLinks as $link)
                    <a class="nav-link {{ $link['active'] ? 'is-active' : '' }}" href="{{ $link['href'] }}" title="{{ $link['label'] }}" @if($link['active']) aria-current="page" @endif><x-icon :name="$link['icon']" /><span class="nav-text">{{ $link['label'] }}</span>@if($link['badge'] ?? 0)<span class="nav-badge" aria-label="{{ $link['badge'] }} baru">{{ $link['badge'] }}</span>@endif</a>
                @endforeach
            @else
                <a class="nav-link {{ request()->routeIs('admin.*') ? 'is-active' : '' }}" href="{{ route('admin.index') }}" title="Kelola Data"><x-icon name="lock-keyhole" /><span class="nav-text">Kelola Data</span></a>
            @endif
        </nav>

        <div class="sidebar-foot">
            <div class="sidebar-card"><strong>Tahun anggaran {{ $selectedYear }}</strong>Sumber: baliho APBDes desa</div>
            <button class="collapse-btn" type="button" data-sidebar-collapse aria-controls="sidebar" aria-expanded="true" title="Ringkas menu"><x-icon name="chevrons-left" /><span class="nav-text">Ringkas menu</span></button>
        </div>
    </aside>
    <div class="sidebar-flyout" id="apbdes-flyout" popover aria-label="Menu APBDes">
        @foreach($apbdesLinks as $link)<a href="{{ $link['href'] }}" class="{{ $link['active'] ? 'is-active' : '' }}">{{ $link['label'] }}</a>@endforeach
    </div>

    <div class="app">
        <header class="topbar">
            <button class="icon-btn menu-btn" type="button" data-sidebar-open aria-controls="sidebar" aria-expanded="false" aria-label="Buka menu"><x-icon name="menu" /></button>
            <div class="topbar-title"><strong>@yield('heading', 'Dashboard Monitoring APBDes')</strong><span>Desa Sungai Pinang Nibung, Kecamatan Sungai Pinang, Kabupaten Ogan Ilir</span></div>
            <div class="topbar-actions">
                @if($years)
                    <form method="GET" class="year-picker">
                        @foreach(request()->except(['year', 'page']) as $name => $value)
                            @if(is_string($value))<input type="hidden" name="{{ $name }}" value="{{ $value }}">@endif
                        @endforeach
                        <label for="year-picker">Tahun anggaran</label>
                        <select id="year-picker" name="year" data-auto-submit>@foreach($years as $year)<option value="{{ $year }}" @selected($year == $selectedYear)>{{ $year }}</option>@endforeach</select>
                    </form>
                @endif

                <button class="bell-btn" type="button" popovertarget="bell-panel" aria-label="Notifikasi" data-bell>
                    <x-icon name="bell" /><span class="bell-dot" data-bell-count hidden></span>
                </button>
                <div class="bell-panel" id="bell-panel" popover aria-label="Notifikasi" data-bell-panel data-feed-url="{{ route('notifications') }}">
                    <div class="bell-head"><strong>Notifikasi</strong><button type="button" data-bell-read>Tandai sudah dibaca</button></div>
                    <div class="bell-list" data-bell-list>
                        @forelse($notifications as $item)
                            <a class="bell-item" href="{{ $item['url'] }}" data-notification-id="{{ $item['id'] }}">
                                <span class="bell-icon {{ $item['kind'] === 'complaint' ? 'is-complaint' : '' }}"><x-icon :name="$item['kind'] === 'complaint' ? 'message-square-text' : 'receipt-text'" /></span>
                                <span><span class="bell-title">{{ $item['title'] }}</span><span class="bell-detail">{{ $item['detail'] }}</span><span class="bell-date">{{ \Carbon\Carbon::parse($item['date'])->translatedFormat('j F Y') }}</span></span>
                            </a>
                        @empty
                            <p class="bell-empty">Belum ada pengeluaran yang dicatat.</p>
                        @endforelse
                    </div>
                    <a class="bell-foot" href="{{ route('realizations.index', ['year' => $selectedYear]) }}">Lihat semua realisasi</a>
                </div>

                <button class="profile-btn" type="button" popovertarget="profile-menu" aria-controls="profile-menu" aria-expanded="false" data-profile-trigger>
                    <span class="avatar" aria-hidden="true">@auth{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}@else<x-icon name="user-round" />@endauth</span>
                    <span class="profile-name"><strong>{{ auth()->check() ? auth()->user()->name : 'Pengunjung' }}</strong><span>{{ $canManage ? 'Perangkat desa' : (auth()->check() ? 'Akses pengelola dicabut' : 'Masyarakat') }}</span></span>
                    <x-icon name="chevron-down" />
                </button>
                <div class="profile-menu" id="profile-menu" popover aria-label="Menu akun">
                    <div class="profile-menu-head"><strong>{{ auth()->check() ? auth()->user()->name : 'Akses masyarakat' }}</strong><span>{{ auth()->check() ? auth()->user()->email : 'Melihat data tanpa login.' }}</span></div>
                    @auth
                        @if($canManage)
                            <a class="menu-item" href="{{ route('admin.index', ['year' => $selectedYear]) }}"><x-icon name="gauge" /> Dashboard pengelola</a>
                            <a class="menu-item" href="{{ route('admin.budgets.index', ['year' => $selectedYear]) }}"><x-icon name="database" /> Pengelolaan data</a>
                            <a class="menu-item" href="{{ route('admin.settings') }}"><x-icon name="settings" /> Pengaturan akun</a>
                        @endif
                        <form method="POST" action="{{ route('logout') }}">@csrf<button class="menu-item" type="submit"><x-icon name="log-out" /> Keluar akun</button></form>
                    @else
                        <a class="menu-item" href="{{ route('complaints.create') }}"><x-icon name="message-square-text" /> Kirim pengaduan</a>
                        <a class="menu-item" href="{{ route('login') }}"><x-icon name="log-in" /> Masuk pengelola</a>
                    @endauth
                </div>
            </div>
        </header>

        <main id="main" tabindex="-1">
            @if(session('success'))
                <div class="toast" role="status" data-toast><x-icon name="circle-check" /><span>{{ session('success') }}</span><button class="icon-btn" type="button" data-toast-close aria-label="Tutup pesan"><x-icon name="x" /></button></div>
            @endif
            @if($errors->any() && ! old('_budget_form') && ! old('_dataset_form'))
                <div class="alert" role="alert" tabindex="-1" data-error-summary><x-icon name="circle-alert" /><div><strong>Ada isian yang perlu diperbaiki.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div></div>
            @endif

            @yield('content')

            <footer class="site-footer">
                <span><strong>Pemerintah Desa Sungai Pinang Nibung</strong> · Kecamatan Sungai Pinang, Kabupaten Ogan Ilir · “Desa Membangun Indonesia”</span>
                <nav aria-label="Tautan bawah"><a href="{{ route('reports.index') }}">Laporan</a><a href="{{ route('complaints.create') }}">Pengaduan</a><a href="{{ route('export', ['year' => $selectedYear]) }}">Unduh CSV</a><a href="{{ route('about') }}">Tentang portal</a></nav>
            </footer>
        </main>
    </div>
    @stack('scripts')
</body>
</html>
