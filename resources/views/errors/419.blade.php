<x-layouts.guest title="Sessão expirada">
    <div class="text-center">
        <i class="bi bi-hourglass-bottom display-4 text-warning"></i>
        <h2 class="h5 mt-3">Sessão expirada</h2>
        <p class="text-secondary">A página ficou aberta por muito tempo. Recarregue e tente novamente.</p>
        <a href="{{ url('/login') }}" class="btn btn-primary">
            <i class="bi bi-arrow-clockwise me-1"></i>Voltar
        </a>
    </div>
</x-layouts.guest>
