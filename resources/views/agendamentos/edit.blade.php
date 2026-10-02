<x-layouts.app title="Editar agendamento">
    <x-page-header titulo="Editar agendamento"
                   :subtitulo="$agendamento->inicio->format('d/m/Y').' · '.$agendamento->horario()" />

    <form method="POST" action="{{ route('agendamentos.update', $agendamento) }}" novalidate>
        @csrf
        @method('PUT')
        @include('agendamentos._form')
    </form>
</x-layouts.app>
