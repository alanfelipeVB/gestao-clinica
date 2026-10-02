<x-layouts.app title="Editar sala">
    <x-page-header :titulo="$sala->nome" :subtitulo="$sala->ativa ? 'Ativa' : 'Inativa'" />

    <form method="POST" action="{{ route('admin.salas.update', $sala) }}" novalidate>
        @csrf
        @method('PUT')
        @include('admin.salas._form')
    </form>
</x-layouts.app>
