<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>ViLoCare - @yield('page_title', 'Dashboard')</title>

    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="{{ asset('css/theme.css') }}" rel="stylesheet" />
    @stack('styles')
</head>

<body>
<div class="app-shell">
    @include('layouts.sidebar')
    <div class="app-main">
        <nav class="app-topbar">
            <div class="topbar-left">
                <button class="sidebar-toggle" type="button" id="sidebarToggle" aria-label="Toggle navigation">||</button>
                <h1 class="topbar-title">@yield('page_title', 'Dashboard')</h1>
            </div>
            <div class="topbar-user">
                <span class="user-chip">
                    <span class="user-dot"></span>
                    {{ auth()->user()->username }}
                </span>
                <a href="{{ url('/logout') }}" class="btn-logout">Logout</a>
            </div>
        </nav>
        <div class="sidebar-backdrop" id="sidebarBackdrop"></div>
        <main class="app-content container-fluid">
            @if(session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif
            @yield('content')
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    (function () {
        var body = document.body;
        var toggle = document.getElementById('sidebarToggle');
        var backdrop = document.getElementById('sidebarBackdrop');

        if (toggle) {
            toggle.addEventListener('click', function () {
                body.classList.toggle('sidebar-open');
            });
        }

        if (backdrop) {
            backdrop.addEventListener('click', function () {
                body.classList.remove('sidebar-open');
            });
        }

        window.addEventListener('resize', function () {
            if (window.innerWidth > 991) {
                body.classList.remove('sidebar-open');
            }
        });
    })();
</script>
@stack('scripts')

</body>
</html>
