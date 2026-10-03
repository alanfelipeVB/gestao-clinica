@props(['icone', 'titulo'])

<div class="col-12 col-sm-6 col-lg-4">
    <div class="d-flex gap-3 p-3 h-100 rounded-3 bg-body-tertiary">
        <span class="rounded-circle bg-primary-subtle text-primary-emphasis d-inline-flex align-items-center justify-content-center fs-5 flex-shrink-0"
              style="width: 2.75rem; height: 2.75rem;">
            <i class="bi bi-{{ $icone }}"></i>
        </span>
        <div class="min-w-0">
            <div class="small text-secondary">{{ $titulo }}</div>
            <div class="text-break">{{ $slot }}</div>
        </div>
    </div>
</div>
