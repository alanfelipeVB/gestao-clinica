<x-layouts.app title="Nova sala">
    <x-page-header titulo="Nova sala" subtitulo="A sala ficará disponível para agendamento assim que for cadastrada." />

    <form method="POST" action="{{ route('admin.salas.store') }}" novalidate>
        @csrf
        @include('admin.salas._form')
    </form>
</x-layouts.app>
