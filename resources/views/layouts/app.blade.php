<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>ViLoCare Dashboard</title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}" />

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />

    <!-- Custom CSS -->
    <link href="{{ asset('dist/css/app.css') }}" rel="stylesheet" />
</head>

<body>

<div class="d-flex">

    <!-- Sidebar -->
    @include('layouts.sidebar')

    <!-- Main Content -->
    <div class="flex-grow-1">

        <!-- Navbar -->
        <nav class="navbar navbar-light bg-light px-3">
            <span class="navbar-brand">ViLoCare</span>

            <div>
                <span class="me-3">{{ auth()->user()->username }}</span>
                <a href="/logout" class="btn btn-danger btn-sm">Logout</a>
            </div>
        </nav>

        <!-- Page Content -->
        <div class="container mt-4">
            @if(session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif
            @yield('content')
        </div>

    </div>

</div>

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Your Chart.js script -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

</body>
</html>