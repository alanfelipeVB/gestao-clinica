@props(['title' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ? $title.' · ' : '' }}{{ config('app.name') }}</title>

    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div class="guest-wrapper d-flex align-items-center justify-content-center p-3">
        <div class="w-100" style="max-width: 420px;">
            <div class="text-center text-white mb-4">
                <i class="bi bi-calendar2-heart display-5"></i>
                <h1 class="h4 mt-2 mb-0">{{ config('app.name') }}</h1>
                <p class="text-white-50 small mb-0">Agendamento de salas</p>
            </div>

            <div class="card shadow">
                <div class="card-body p-4">
                    <x-flash />

                    {{ $slot }}
                </div>
            </div>
        </div>
    </div>
</body>
</html>
