<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>{{ $title ?? 'Hệ thống CNCL' }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body {
            background: #f4f6f9;
        }

        .sidebar {
            width: 260px;
            min-height: 100vh;
            background: #1f2937;
            color: white;
            position: fixed;
            left: 0;
            top: 0;
        }

        .sidebar a {
            color: #d1d5db;
            display: block;
            padding: 12px 18px;
            text-decoration: none;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background: #374151;
            color: #fff;
        }

        .main-content {
            margin-left: 260px;
            padding: 20px;
        }

        .topbar {
            background: white;
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .card-box {
            background: white;
            border-radius: 10px;
            padding: 20px;
            border: 1px solid #e5e7eb;
        }
    </style>
</head>
<body>

@include('partials.sidebar')

<div class="main-content">
    @include('partials.topbar')

    @yield('content')
</div>

</body>
</html>