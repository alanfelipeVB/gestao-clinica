{{-- Mensagens de feedback: session('sucesso'), session('erro'), session('aviso') e erros de validação. --}}

@if (session('sucesso'))
    <div class="alert alert-success alert-dismissible fade show d-flex align-items-start gap-2" role="alert" data-auto-dismiss>
        <i class="bi bi-check-circle-fill mt-1"></i>
        <div>{{ session('sucesso') }}</div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
    </div>
@endif

@if (session('aviso'))
    <div class="alert alert-warning alert-dismissible fade show d-flex align-items-start gap-2" role="alert">
        <i class="bi bi-exclamation-triangle-fill mt-1"></i>
        <div>{{ session('aviso') }}</div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
    </div>
@endif

@if (session('erro'))
    <div class="alert alert-danger alert-dismissible fade show d-flex align-items-start gap-2" role="alert">
        <i class="bi bi-x-circle-fill mt-1"></i>
        <div>{{ session('erro') }}</div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show d-flex align-items-start gap-2" role="alert">
        <i class="bi bi-x-circle-fill mt-1"></i>
        <div>
            <strong>Verifique os campos abaixo:</strong>
            <ul class="mb-0 mt-1 ps-3">
                @foreach ($errors->all() as $erro)
                    <li>{{ $erro }}</li>
                @endforeach
            </ul>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
    </div>
@endif
