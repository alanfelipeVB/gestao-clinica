@props(['title' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ? $title.' · ' : '' }}{{ $marca['nome'] }}</title>

    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div class="guest-wrapper d-flex align-items-center justify-content-center p-3">
        <div class="w-100" style="max-width: 420px;">
            <div class="text-center text-white mb-4">
                @if ($marca['logo'])
                    <img src="{{ $marca['logo'] }}" alt="Logo {{ $marca['nome'] }}" class="bg-white rounded-3 p-2 shadow-sm" style="max-height: 5rem; max-width: 12rem; object-fit: contain;">
                @else
                    <i class="bi bi-calendar2-heart display-5"></i>
                @endif
                <h1 class="h4 mt-2 mb-0">{{ $marca['nome'] }}</h1>
                <p class="text-white-50 small mb-0">Agendamento de salas</p>
            </div>

            <div class="card shadow">
                <div class="card-body p-4">
                    <x-flash />

                    {{ $slot }}
                </div>
            </div>

            <p class="text-center mt-3 mb-0">
                <a href="{{ route('inicio') }}" class="link-light small"><i class="bi bi-arrow-left me-1"></i>Voltar ao site</a>
            </p>
        </div>
    </div>
</body>
</html>
