@props(['title' => null, 'descricao' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? $title.' · ' : '' }}{{ $marca['nome'] }}</title>
    <link rel="icon" href="{{ $marca['favicon']['url'] }}" @if ($marca['favicon']['tipo']) type="{{ $marca['favicon']['tipo'] }}" @endif>
    @if ($descricao)
        <meta name="description" content="{{ Str::limit($descricao, 160) }}">
    @endif

    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="site d-flex flex-column min-vh-100">
    <nav class="navbar navbar-expand-md bg-white border-bottom sticky-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2 fw-semibold" href="{{ route('inicio') }}">
                {{-- Com logo, só a logo; sem logo, ícone e nome da clínica. --}}
                @if ($marca['logo'])
                    <img src="{{ $marca['logo'] }}" alt="{{ $marca['nome'] }}" style="height: 3rem; max-width: 12rem; object-fit: contain;">
                @else
                    <i class="bi bi-calendar2-heart fs-3 text-primary"></i>
                    <span>{{ $marca['nome'] }}</span>
                @endif
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menu-site"
                    aria-controls="menu-site" aria-expanded="false" aria-label="Abrir menu">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="menu-site">
                <ul class="navbar-nav ms-auto align-items-md-center gap-md-2">
                    {{ $menu ?? '' }}
                    <li class="nav-item mt-2 mt-md-0">
                        @auth
                            <a href="{{ route('dashboard') }}" class="btn btn-primary">
                                <i class="bi bi-grid me-1"></i>Ir para o sistema
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="btn btn-outline-primary">
                                <i class="bi bi-box-arrow-in-right me-1"></i>Entrar
                            </a>
                        @endauth
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="flex-grow-1">
        {{ $slot }}
    </main>

    <footer class="site-rodape py-4">
        <div class="container d-flex flex-wrap justify-content-between gap-2 small">
            <span>&copy; {{ date('Y') }} {{ $marca['nome'] }}</span>
            <a href="{{ route('login') }}" class="link-light">Área dos profissionais</a>
        </div>
    </footer>
</body>
</html>
