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
    @stack('styles')
</head>
<body>
    <x-layouts.sidebar />

    <div class="app-main d-flex flex-column min-vh-100">
        <header class="app-topbar sticky-top">
            <div class="d-flex align-items-center gap-2 px-3 px-lg-4 py-2">
                <button class="btn btn-link text-body d-lg-none p-1" type="button"
                        data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-controls="sidebar"
                        aria-label="Abrir menu">
                    <i class="bi bi-list fs-4"></i>
                </button>

                <span class="fw-semibold text-truncate">{{ $title }}</span>

                @auth
                    <div class="dropdown ms-auto">
                        <button class="btn btn-link text-body text-decoration-none dropdown-toggle d-flex align-items-center gap-2"
                                type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="rounded-circle bg-primary-subtle text-primary-emphasis fw-semibold d-inline-flex align-items-center justify-content-center"
                                  style="width: 2rem; height: 2rem;">
                                {{ mb_strtoupper(mb_substr(auth()->user()->nome, 0, 1)) }}
                            </span>
                            <span class="d-none d-sm-inline">{{ auth()->user()->nome }}</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                            <li><h6 class="dropdown-header">{{ auth()->user()->perfil->label() }}</h6></li>
                            @if (Route::has('perfil.edit'))
                                <li>
                                    <a class="dropdown-item" href="{{ route('perfil.edit') }}">
                                        <i class="bi bi-person me-2"></i>Meu perfil
                                    </a>
                                </li>
                            @endif
                            @if (Route::has('logout'))
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="dropdown-item text-danger">
                                            <i class="bi bi-box-arrow-right me-2"></i>Sair
                                        </button>
                                    </form>
                                </li>
                            @endif
                        </ul>
                    </div>
                @endauth
            </div>
        </header>

        <main class="flex-grow-1 px-3 px-lg-4 py-4">
            <x-flash />

            {{ $slot }}
        </main>

        <footer class="px-3 px-lg-4 py-3 small text-secondary">
            &copy; {{ date('Y') }} {{ config('app.name') }}
        </footer>
    </div>

    @stack('scripts')
</body>
</html>
