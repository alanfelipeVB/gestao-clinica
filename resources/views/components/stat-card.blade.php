@props(['icone', 'rotulo', 'valor', 'href' => null])

<div class="card h-100">
    <div class="card-body d-flex align-items-center gap-3">
        <span class="rounded-3 bg-primary-subtle text-primary-emphasis d-inline-flex align-items-center justify-content-center fs-4 flex-shrink-0"
              style="width: 3rem; height: 3rem;">
            <i class="bi bi-{{ $icone }}"></i>
        </span>
        <div>
            <div class="text-secondary small">{{ $rotulo }}</div>
            <div class="fs-4 fw-semibold lh-sm">{{ $valor }}</div>
        </div>
        @if ($href)
            <a href="{{ $href }}" class="stretched-link" aria-label="{{ $rotulo }}"></a>
        @endif
    </div>
</div>
