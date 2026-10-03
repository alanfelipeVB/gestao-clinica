<aside id="sidebar" class="app-sidebar offcanvas-lg offcanvas-start text-white" tabindex="-1" aria-label="Menu principal">
    <div class="d-flex align-items-center justify-content-between px-3 py-3 border-bottom border-white border-opacity-10">
        <a href="{{ route('dashboard') }}" class="d-flex align-items-center gap-2 text-white text-decoration-none min-w-0">
            @if ($marca['logo'])
                <img src="{{ $marca['logo'] }}" alt="" class="bg-white rounded-2 p-1 flex-shrink-0" style="height: 2.25rem; max-width: 4.5rem; object-fit: contain;">
            @else
                <i class="bi bi-calendar2-heart fs-4"></i>
            @endif
            <span class="fw-semibold">{{ $marca['nome'] }}</span>
        </a>
        <button type="button" class="btn-close btn-close-white d-lg-none" data-bs-dismiss="offcanvas"
                data-bs-target="#sidebar" aria-label="Fechar menu"></button>
    </div>

    <nav class="px-2 pb-4">
        <div class="sidebar-secao">Geral</div>
        <ul class="nav flex-column gap-1">
            <x-sidebar-link rota="dashboard" icone="speedometer2">Dashboard</x-sidebar-link>
            <x-sidebar-link rota="agenda" icone="calendar3">Agenda</x-sidebar-link>
            <x-sidebar-link rota="agendamentos.index" icone="journal-check">Agendamentos</x-sidebar-link>
            <x-sidebar-link rota="salas.index" icone="door-open">Salas</x-sidebar-link>
            <x-sidebar-link rota="relatorio.index" icone="bar-chart-line">Relatório</x-sidebar-link>
            <x-sidebar-link rota="tutoriais.index" icone="play-btn">Tutoriais</x-sidebar-link>
        </ul>

        @if (auth()->user()?->isAdmin())
            <div class="sidebar-secao">Administração</div>
            <ul class="nav flex-column gap-1">
                <x-sidebar-link rota="admin.profissionais.index" icone="people">Profissionais</x-sidebar-link>
                <x-sidebar-link rota="admin.salas.index" icone="building-gear">Gerenciar salas</x-sidebar-link>
                <x-sidebar-link rota="admin.tutoriais.index" icone="collection-play">Gerenciar tutoriais</x-sidebar-link>
                <x-sidebar-link rota="admin.pagina-inicial.edit" icone="window">Página inicial</x-sidebar-link>
                <x-sidebar-link rota="admin.configuracoes.edit" icone="gear">Configurações</x-sidebar-link>
            </ul>
        @endif
    </nav>
</aside>
