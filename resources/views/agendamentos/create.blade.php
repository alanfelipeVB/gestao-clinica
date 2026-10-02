<x-layouts.app title="Novo agendamento">
    <x-page-header titulo="Novo agendamento" subtitulo="Escolha a sala, a data e o horário." />

    <form method="POST" action="{{ route('agendamentos.store') }}" novalidate>
        @csrf
        @include('agendamentos._form')
    </form>
</x-layouts.app>
