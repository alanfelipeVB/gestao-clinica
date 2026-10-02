<x-layouts.app title="Dashboard">
    <x-page-header
        :titulo="'Olá, '.Str::before(auth()->user()->nome, ' ').'!'"
        subtitulo="Bem-vindo(a) ao sistema de agendamento de salas." />

    <div class="card">
        <div class="card-body text-secondary">
            <i class="bi bi-info-circle me-1"></i>
            O painel com indicadores e próximos agendamentos será disponibilizado em breve.
        </div>
    </div>
</x-layouts.app>
