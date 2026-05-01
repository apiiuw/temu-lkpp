<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title }} | TemuLKPP</title>
        <link rel="icon" type="image/png" href="{{ asset('img/icon/logo/logoIcon.png') }}">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700,800" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="h-screen overflow-hidden bg-[linear-gradient(145deg,#f4f1eb_0%,#fffdf8_48%,#efe5d3_100%)] text-stone-900">
        <div class="flex h-screen">
            @include('partials.sidebar')

            <main class="min-w-0 flex-1 overflow-y-auto">
                @yield('container')
            </main>
        </div>

        @stack('scripts')
    </body>
</html>
