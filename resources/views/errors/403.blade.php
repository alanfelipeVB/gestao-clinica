<x-layouts.guest title="Acesso negado">
    <div class="text-center">
        <i class="bi bi-shield-lock display-4 text-danger"></i>
        <h2 class="h5 mt-3">Acesso negado</h2>
        <p class="text-secondary">
            {{ $exception->getMessage() ?: 'Você não tem permissão para acessar esta página.' }}
        </p>
        <a href="{{ url('/dashboard') }}" class="btn btn-primary">
            <i class="bi bi-house me-1"></i>Voltar ao início
        </a>
    </div>
</x-layouts.guest>
