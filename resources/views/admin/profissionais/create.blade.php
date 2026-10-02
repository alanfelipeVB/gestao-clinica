<x-layouts.app title="Novo profissional">
    <x-page-header titulo="Novo profissional" subtitulo="O profissional poderá acessar o sistema com o e-mail e a senha informados." />

    <form method="POST" action="{{ route('admin.profissionais.store') }}" novalidate>
        @csrf
        @include('admin.profissionais._form')
    </form>
</x-layouts.app>
