<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?? 'Inscripción' }} — TDG</title>

        <link rel="icon" href="/favicon.ico" sizes="any">

        @vite(['resources/css/event-registration.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="event-registration-body">
        {{ $slot }}
        @livewireScripts
    </body>
</html>
