<x-layouts.app title="Editar tutorial">
    <x-page-header :titulo="$tutorial->titulo" :subtitulo="$tutorial->publicado ? 'Publicado' : 'Rascunho'" />

    <div class="card mb-3">
        <div class="card-body">
            <video class="w-100 rounded bg-dark" style="max-height: 320px;" controls preload="metadata"
                   src="{{ route('tutoriais.video', $tutorial) }}"></video>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.tutoriais.update', $tutorial) }}" enctype="multipart/form-data" novalidate>
        @csrf
        @method('PUT')
        @include('admin.tutoriais._form')
    </form>
</x-layouts.app>
