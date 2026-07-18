<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="robots" content="noindex, nofollow">
        <title>{{ isset($title) ? $title . ' - ' : '' }}{{ config('app.name', 'MetalThursday') }}</title>
        @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    </head>
    <body class="d-flex flex-column vh-100">
        <div class="flex-grow-1">
            @include('layouts.navigation')

            @if (isset($header))
                <header class="bg-dark shadow-sm">
                    <div class="container py-3">
                        {{ $header }}
                    </div>
                </header>
            @endif

            <main>
                <div class="container py-4">
                    {{ $slot }}
                </div>
            </main>
        </div>

        <footer class="py-3 text-center text-muted small bg-dark">
            &copy; {{ date('Y') }} MetalThursday. Todos os direitos reservados.
        </footer>

        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
            @csrf
        </form>

        @stack('page-scripts')
    </body>
</html>
