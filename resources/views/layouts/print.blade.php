<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>@yield('title', 'Imprimir')</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @stack('head')
    </head>
    <body class="@yield('body-class', 'bg-slate-100 text-slate-900 antialiased')">
        @yield('content')
        @stack('scripts')
    </body>
</html>
