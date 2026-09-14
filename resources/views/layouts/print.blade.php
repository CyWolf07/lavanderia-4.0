<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        @include('layouts.app-context')
        <title>@yield('title', 'Imprimir')</title>
        @stack('head')
    </head>
    <body class="@yield('body-class')">
        @yield('content')
        @stack('scripts')
    </body>
</html>
