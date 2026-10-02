<x-layouts.app title="Editar profissional">
    <x-page-header :titulo="$profissional->nome"
                   :subtitulo="'Cadastrado em '.$profissional->created_at->format('d/m/Y').' · '.($profissional->ativo ? 'Ativo' : 'Inativo')" />

    <form method="POST" action="{{ route('admin.profissionais.update', $profissional) }}" novalidate>
        @csrf
        @method('PUT')
        @include('admin.profissionais._form')
    </form>
</x-layouts.app>
