<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ isset($title) ? $title . ' - ' : '' }}{{ config('app.name', 'MetalThursday') }}</title>
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="d-flex flex-column vh-100">
    <main class="flex-grow-1 d-flex align-items-center justify-content-center">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6 col-lg-5">
                    <div class="text-center mb-4">
                        <a href="{{ route('home') }}">
                            <img src="{{ asset('images/logo.png') }}" alt="MetalThursday" class="img-fluid">
                        </a>
                    </div>
                    <div class="card shadow-sm">
                        <div class="card-body p-4 p-md-5">
                            {{ $slot }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="py-3 text-center text-muted small">
        &copy; {{ date('Y') }} MetalThursday. Todos os direitos reservados.
    </footer>

    @stack('page-scripts')
</body>
</html>
