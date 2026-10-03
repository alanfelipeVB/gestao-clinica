@php($assistiram = $profissionais->whereNotNull('assistido_em')->count())

<x-layouts.app title="Quem assistiu">
    <x-page-header :titulo="$tutorial->titulo"
                   :subtitulo="$assistiram.' de '.$profissionais->count().' profissionais ativos assistiram'">
        <x-slot:acoes>
            <a href="{{ route('admin.tutoriais.index') }}" class="btn btn-light">
                <i class="bi bi-arrow-left me-1"></i>Voltar
            </a>
        </x-slot:acoes>
    </x-page-header>

    <div class="card">
        <ul class="list-group list-group-flush">
            @forelse ($profissionais as $linha)
                <li class="list-group-item d-flex justify-content-between align-items-center gap-2">
                    <div>
                        <div class="fw-semibold">{{ $linha['profissional']->nome }}</div>
                        <div class="small text-secondary">{{ $linha['profissional']->profissao }}</div>
                    </div>
                    @if ($linha['assistido_em'])
                        <span class="badge text-bg-success">
                            <i class="bi bi-check2 me-1"></i>Assistido em {{ \Illuminate\Support\Carbon::parse($linha['assistido_em'])->format('d/m/Y H:i') }}
                        </span>
                    @else
                        <span class="badge text-bg-light border text-secondary">Ainda não assistiu</span>
                    @endif
                </li>
            @empty
                <li class="list-group-item text-center text-secondary py-4">Nenhum profissional ativo.</li>
            @endforelse
        </ul>
    </div>
</x-layouts.app>
