<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>
        <link rel="icon" type="image/png" href="{{ asset('images/logo-lavanderia-exclusiva.png') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-slate-900 antialiased">
        <div class="min-h-screen flex flex-col items-center justify-center px-4 py-8">
            <div class="brand-card-soft flex items-center gap-3 rounded-full px-5 py-3">
                <a href="/">
                    <div class="flex items-center gap-3">
                        <x-application-logo class="h-12 w-auto" />
                        <div class="hidden text-left sm:block">
                            <p class="font-display text-base text-slate-900">Lavandería Exclusiva</p>
                            <p class="text-xs uppercase tracking-[0.25em] text-sky-700">Experiencia y calidad a su servicio</p>
                        </div>
                    </div>
                </a>
            </div>

            <div class="brand-card mt-8 w-full overflow-hidden px-6 py-6 sm:max-w-md sm:px-8 sm:py-8">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
