<x-layouts.app title="Novo tutorial">
    <x-page-header titulo="Novo tutorial" subtitulo="Envie um vídeo para os profissionais assistirem." />

    <form method="POST" action="{{ route('admin.tutoriais.store') }}" enctype="multipart/form-data" novalidate>
        @csrf
        @include('admin.tutoriais._form')
    </form>
</x-layouts.app>
