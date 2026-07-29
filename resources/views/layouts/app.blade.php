<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Laundry Manager') }} @hasSection('title')- @yield('title')@endif</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div class="d-flex flex-column flex-md-row">
        <nav class="lms-sidebar d-flex flex-column">
            <div class="brand">Laundry Manager</div>
            <ul class="nav flex-column flex-grow-1">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('customers.*') ? 'active' : '' }}" href="{{ route('customers.index') }}">Customers</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('services.*') ? 'active' : '' }}" href="{{ route('services.index') }}">Services</a>
                </li>
                @if (class_exists(\App\Models\Order::class) && auth()->check() && auth()->user()->can('viewAny', \App\Models\Order::class))
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('orders.*') ? 'active' : '' }}" href="{{ route('orders.index') }}">Orders</a>
                </li>
                @endif
                @if (\Illuminate\Support\Facades\Route::has('payments.index') && auth()->check())
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('payments.*') ? 'active' : '' }}" href="{{ route('payments.index') }}">Payments</a>
                </li>
                @endif
                @if (\Illuminate\Support\Facades\Route::has('deliveries.index') && auth()->check())
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('deliveries.*') ? 'active' : '' }}" href="{{ route('deliveries.index') }}">Deliveries</a>
                </li>
                @endif
                @if (\Illuminate\Support\Facades\Route::has('reports.index') && auth()->check())
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}" href="{{ route('reports.index') }}">Reports</a>
                </li>
                @endif
                @if (\Illuminate\Support\Facades\Route::has('inventory.index') && auth()->check())
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('inventory.*') ? 'active' : '' }}" href="{{ route('inventory.index') }}">Inventory</a>
                </li>
                @endif
                @if (\Illuminate\Support\Facades\Route::has('expenses.index') && auth()->check())
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('expenses.*') ? 'active' : '' }}" href="{{ route('expenses.index') }}">Expenses</a>
                </li>
                @endif
                @auth
                @if(auth()->user()->isAdmin())
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}">Users</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.locations.*') ? 'active' : '' }}" href="{{ route('admin.locations.index') }}">Locations</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}" href="{{ route('admin.settings.index') }}">Settings</a>
                </li>
                @endif
                @if(auth()->user()->hasRole(\App\Enums\UserRole::OWNER, \App\Enums\UserRole::MANAGER, \App\Enums\UserRole::ACCOUNTANT))
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('audit-logs.*') ? 'active' : '' }}" href="{{ route('audit-logs.index') }}">Audit Logs</a>
                </li>
                @endif
                @endauth
            </ul>
        </nav>

        <div class="lms-content">
            <div class="lms-topbar d-flex justify-content-between align-items-center px-4 py-2">
                <div>@yield('header')</div>
                @auth
                <div class="d-flex align-items-center gap-3">
                    <span class="text-muted small">{{ auth()->user()->name }} &middot; {{ auth()->user()->role->label() }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-secondary">Logout</button>
                    </form>
                </div>
                @endauth
            </div>

            <main class="p-4">
                @if (session('status'))
                    <div class="alert alert-success">{{ session('status') }}</div>
                @endif
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
