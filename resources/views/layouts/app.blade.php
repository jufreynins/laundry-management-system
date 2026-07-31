<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Laundry Manager') }} @hasSection('title')- @yield('title')@endif</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="gtl-shell" data-page="{{ request()->route()?->getName() }}">
<a class="skip-link" href="#main-content">Skip to main content</a>

@php
    $navGroups = [
        [
            'label' => 'General',
            'items' => [
                ['route' => 'dashboard', 'match' => 'dashboard', 'text' => 'Dashboard', 'icon' => 'dashboard'],
            ],
        ],
        [
            'label' => 'Operations',
            'items' => array_filter([
                ['route' => 'customers.index', 'match' => 'customers.*', 'text' => 'Customers', 'icon' => 'users'],
                ['route' => 'services.index', 'match' => 'services.*', 'text' => 'Services', 'icon' => 'tag'],
                (\Illuminate\Support\Facades\Route::has('orders.index') && auth()->check() && auth()->user()->can('viewAny', \App\Models\Order::class))
                    ? ['route' => 'orders.index', 'match' => 'orders.*', 'text' => 'Orders', 'icon' => 'cart']
                    : null,
                (\Illuminate\Support\Facades\Route::has('deliveries.index') && auth()->check())
                    ? ['route' => 'deliveries.index', 'match' => 'deliveries.*', 'text' => 'Deliveries', 'icon' => 'map']
                    : null,
            ]),
        ],
        [
            'label' => 'Finance & Stock',
            'items' => array_filter([
                (\Illuminate\Support\Facades\Route::has('payments.index') && auth()->check())
                    ? ['route' => 'payments.index', 'match' => 'payments.*', 'text' => 'Payments', 'icon' => 'price']
                    : null,
                (\Illuminate\Support\Facades\Route::has('expenses.index') && auth()->check())
                    ? ['route' => 'expenses.index', 'match' => 'expenses.*', 'text' => 'Expenses', 'icon' => 'receipt']
                    : null,
                (\Illuminate\Support\Facades\Route::has('inventory.index') && auth()->check())
                    ? ['route' => 'inventory.index', 'match' => 'inventory.*', 'text' => 'Inventory', 'icon' => 'files']
                    : null,
                (\Illuminate\Support\Facades\Route::has('suppliers.index') && auth()->check())
                    ? ['route' => 'suppliers.index', 'match' => 'suppliers.*', 'text' => 'Suppliers', 'icon' => 'shop']
                    : null,
                (\Illuminate\Support\Facades\Route::has('reports.index') && auth()->check())
                    ? ['route' => 'reports.index', 'match' => 'reports.*', 'text' => 'Reports', 'icon' => 'charts']
                    : null,
            ]),
        ],
        [
            'label' => 'Admin',
            'items' => array_filter([
                (auth()->check() && auth()->user()->isAdmin())
                    ? ['route' => 'admin.users.index', 'match' => 'admin.users.*', 'text' => 'Users', 'icon' => 'profile']
                    : null,
                (auth()->check() && auth()->user()->isAdmin())
                    ? ['route' => 'admin.locations.index', 'match' => 'admin.locations.*', 'text' => 'Locations', 'icon' => 'layout']
                    : null,
                (auth()->check() && auth()->user()->isAdmin())
                    ? ['route' => 'admin.settings.index', 'match' => 'admin.settings.*', 'text' => 'Settings', 'icon' => 'settings']
                    : null,
                (auth()->check() && auth()->user()->hasRole(\App\Enums\UserRole::OWNER, \App\Enums\UserRole::MANAGER, \App\Enums\UserRole::ACCOUNTANT))
                    ? ['route' => 'audit-logs.index', 'match' => 'audit-logs.*', 'text' => 'Audit Logs', 'icon' => 'pages']
                    : null,
            ]),
        ],
    ];

    $icons = [
        'dashboard' => '<svg class="icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="4" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="10" width="7" height="11" rx="1.5"/></svg>',
        'users' => '<svg class="icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="8" r="4"/><path d="M5 20c0-3.9 3.1-7 7-7s7 3.1 7 7"/></svg>',
        'tag' => '<svg class="icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20 13l-7 7a2 2 0 01-2.83 0L3 12.83V4h8.83L20 12.17a2 2 0 010 2.83z"/><circle cx="7.5" cy="7.5" r="1.5"/></svg>',
        'cart' => '<svg class="icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="9" cy="21" r="1.5"/><circle cx="20" cy="21" r="1.5"/><path d="M1 1h4l2.7 13.4a2 2 0 002 1.6h9.7a2 2 0 002-1.6L23 6H6"/></svg>',
        'map' => '<svg class="icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"/><line x1="8" y1="2" x2="8" y2="18"/><line x1="16" y1="6" x2="16" y2="22"/></svg>',
        'price' => '<svg class="icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><line x1="12" y1="2" x2="12" y2="22"/><path d="M16 6H9.5a3.5 3.5 0 100 7h5a3.5 3.5 0 010 7H7"/></svg>',
        'receipt' => '<svg class="icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M5 21V3h14v18l-3-2-3 2-3-2-3 2-2-2z"/><path d="M9 8h6M9 12h6M9 16h4"/></svg>',
        'files' => '<svg class="icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 7a2 2 0 012-2h4l2 2h7a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/></svg>',
        'shop' => '<svg class="icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 9l1-5h16l1 5M3 9v10a2 2 0 002 2h14a2 2 0 002-2V9M3 9h18"/><path d="M9 13a3 3 0 006 0"/></svg>',
        'charts' => '<svg class="icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4 19V5M8 19v-8M12 19V9M16 19v-5M20 19v-9"/></svg>',
        'profile' => '<svg class="icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
        'layout' => '<svg class="icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 9v12"/></svg>',
        'settings' => '<svg class="icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3M5.6 5.6l2.1 2.1M16.3 16.3l2.1 2.1M5.6 18.4l2.1-2.1M16.3 7.7l2.1-2.1"/></svg>',
        'pages' => '<svg class="icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="3" width="20" height="18" rx="2"/><path d="M2 8h20"/></svg>',
    ];
@endphp

@auth
<aside class="sidebar" aria-label="Primary navigation">
    <div class="sidebar-brand">
        <div class="brand-icon">L</div>
        <div class="brand-name">{{ config('app.name', 'Laundry Manager') }}</div>
    </div>
    <nav class="sidebar-nav">
        @foreach ($navGroups as $group)
            @continue(empty($group['items']))
            <div class="nav-group">
                <div class="nav-label">{{ $group['label'] }}</div>
                @foreach ($group['items'] as $item)
                    <a class="nav-link {{ request()->routeIs($item['match']) ? 'active' : '' }}"
                       href="{{ route($item['route']) }}"
                       @if (request()->routeIs($item['match'])) aria-current="page" @endif>
                        {!! $icons[$item['icon']] ?? '' !!}
                        <span class="nav-text">{{ $item['text'] }}</span>
                    </a>
                @endforeach
            </div>
        @endforeach
    </nav>
    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}<span class="online"></span></div>
            <div class="sidebar-user-info">
                <div class="name">{{ auth()->user()->name }}</div>
                <div class="role">{{ auth()->user()->role->label() }}</div>
            </div>
        </div>
    </div>
</aside>

<header class="topbar">
    <div class="topbar-left">
        <button class="sidebar-toggle" type="button" aria-label="Open menu" aria-controls="sidebar" aria-expanded="false">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
        <nav class="breadcrumb" aria-label="Breadcrumb">
            <span class="current" aria-current="page">@yield('header', 'Dashboard')</span>
        </nav>
    </div>
    <div class="topbar-right">
        <button class="tb-btn theme-toggle" type="button" title="Toggle theme" aria-label="Toggle theme" aria-pressed="false">
            <svg class="theme-icon-light" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/></svg>
            <svg class="theme-icon-dark" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
        </button>
        <span class="tb-username" style="font-size:13px;color:var(--text-muted);margin:0 4px;">{{ auth()->user()->name }} &middot; {{ auth()->user()->role->label() }}</span>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-outline" style="padding:6px 14px;font-size:13px;">Logout</button>
        </form>
    </div>
</header>
@endauth

<main id="main-content" tabindex="-1" class="main">
<div class="page-wrapper">

    <div class="page-header">
        <div class="page-header-row">
            <div><h1 class="page-title">@yield('header', config('app.name', 'Laundry Manager'))</h1></div>
        </div>
    </div>

    @if (session('status'))
        <div class="alert alert-success"><div class="alert-body">{{ session('status') }}</div></div>
    @endif
    @if ($errors->any())
        <div class="alert alert-error">
            <div class="alert-body">
                <ul style="margin:0;padding-left:18px;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <div class="@yield('content-wrapper-class', 'gtl-content')">
        @yield('content')
    </div>
</div>
</main>

<footer class="footer">
    <span>{{ config('app.name', 'Laundry Manager') }}</span>
    <span>&copy; {{ date('Y') }}</span>
</footer>
</body>
</html>
