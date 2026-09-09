<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>@yield('title', 'KANTO GOODS')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/laravel.css') }}">
    <link rel="stylesheet" href="{{ asset('css/features.css') }}">
    <link rel="stylesheet" href="{{ asset('css/modules.css') }}">
    @stack('head')
</head>
<body>
@auth
    <div class="shell">
        <aside>
            <a class="logo" href="{{ route('dashboard') }}"><b>KG</b><span>KANTO GOODS<small>Inventory &amp; POS</small></span></a>
            <nav>
                <a @class(['active' => request()->routeIs('dashboard')]) href="{{ route('dashboard') }}">Overview</a>
                <a @class(['active' => request()->routeIs('pos.*')]) href="{{ route('pos.index') }}">Point of Sale</a>
                <a @class(['active' => request()->routeIs('products.*')]) href="{{ route('products.index') }}">Product Catalog</a>
                <a @class(['active' => request()->routeIs('inventory.*')]) href="{{ route('inventory.status') }}">Stock Status</a>
                <a @class(['active' => request()->routeIs('reports.*')]) href="{{ route('reports.index') }}">Sales Reports</a>
                <a @class(['active' => request()->routeIs('closings.*')]) href="{{ route('closings.index') }}">Daily Closing @if(auth()->user()->isAdmin() && \App\Models\AdminNotification::whereNull('read_at')->exists())<i class="nav-dot"></i>@endif</a>
                @if(auth()->user()->isAdmin())
                    <a @class(['active' => request()->routeIs('users.*')]) href="{{ route('users.index') }}">Users</a>
                    <a @class(['active' => request()->routeIs('roles.*')]) href="{{ route('roles.index') }}">Roles &amp; Permissions</a>
                @endif
            </nav>
            <div class="profile"><strong>{{ auth()->user()->name }}</strong><small>{{ ucfirst(auth()->user()->role) }}</small><form method="post" action="{{ route('logout') }}">@csrf<button class="link">Sign out</button></form></div>
        </aside>
        <main>
            <header class="module-header">
                <div class="module-heading"><span class="module-eyebrow">KANTO GOODS</span><h1>@yield('heading', 'Dashboard')</h1><p>@yield('subtitle')</p></div>
                <div class="module-header-tools">@yield('header-search') @yield('actions')</div>
            </header>
@else
    <main class="public">
@endauth
        @if(session('success'))<div class="alert success">{{ session('success') }}</div>@endif
        @if($errors->any())<div class="alert error"><b>Please fix the following:</b><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        @yield('content')
    </main>
@auth
    </div>
@endauth
@stack('scripts')
</body>
</html>
