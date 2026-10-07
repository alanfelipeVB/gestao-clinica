@php
    $campo = fn (string $nome) => old($nome, $textos[$nome]);
    $erro = fn (string $nome) => $errors->has($nome) ? 'is-invalid' : '';
@endphp

<x-layouts.app title="Página inicial">
    <x-page-header titulo="Página inicial" subtitulo="Conteúdo da página pública da clínica.">
        <x-slot:acoes>
            <a href="{{ route('inicio') }}" target="_blank" rel="noopener" class="btn btn-outline-primary">
                <i class="bi bi-box-arrow-up-right me-1"></i>Ver página
            </a>
        </x-slot:acoes>
    </x-page-header>

    <form method="POST" action="{{ route('admin.pagina-inicial.update') }}" enctype="multipart/form-data" novalidate>
        @csrf
        @method('PUT')

        <div class="card mb-3">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-image me-1"></i>Identidade</div>
            <div class="card-body row g-3 align-items-start">
                <div class="col-12 col-md-4 text-center">
                    <div class="border rounded-3 p-3 bg-body-tertiary d-flex align-items-center justify-content-center" style="min-height: 8rem;">
                        @if ($urlLogo)
                            <img src="{{ $urlLogo }}" alt="Logo atual" style="max-height: 7rem; max-width: 100%; object-fit: contain;">
                        @else
                            <span class="text-secondary small"><i class="bi bi-image fs-2 d-block"></i>Sem logo</span>
                        @endif
                    </div>
                </div>
                <div class="col-12 col-md-8">
                    <label for="logo" class="form-label">{{ $urlLogo ? 'Trocar logo' : 'Logo' }}</label>
                    <input type="file" id="logo" name="logo" accept="image/png,image/jpeg,image/webp" class="form-control {{ $erro('logo') }}">
                    @error('logo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <div class="form-text">PNG, JPG ou WebP, até 2 MB. Prefira fundo transparente. Aparece no site, no login e no menu do sistema.</div>

                    @if ($urlLogo)
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" id="remover_logo" name="remover_logo" value="1">
                            <label class="form-check-label" for="remover_logo">Remover logo atual</label>
                        </div>
                    @endif

                    <label for="favicon" class="form-label mt-3">Favicon (ícone da aba do navegador)</label>
                    <div class="d-flex align-items-center gap-2">
                        <img src="{{ $urlFavicon ?? $urlLogo ?? asset('favicon.svg') }}" alt="Favicon atual" class="border rounded bg-white p-1"
                             style="width: 2.5rem; height: 2.5rem; object-fit: contain;">
                        <input type="file" id="favicon" name="favicon" accept="image/png,image/webp,.ico" class="form-control {{ $erro('favicon') }}">
                    </div>
                    @error('favicon') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    <div class="form-text">
                        PNG, WebP ou ICO, quadrado (ex.: 512×512), até 512 KB.
                        @unless ($urlFavicon)
                            Sem favicon próprio, é usada a logo{{ $urlLogo ? '' : ' (ou o ícone padrão do sistema)' }}.
                        @endunless
                    </div>
                    @if ($urlFavicon)
                        <div class="form-check mt-1">
                            <input class="form-check-input" type="checkbox" id="remover_favicon" name="remover_favicon" value="1">
                            <label class="form-check-label" for="remover_favicon">Remover favicon (volta a usar a logo)</label>
                        </div>
                    @endif

                    <label for="site_nome" class="form-label mt-3">Nome da clínica</label>
                    <input type="text" id="site_nome" name="site_nome" value="{{ $campo('site_nome') }}" maxlength="100" class="form-control {{ $erro('site_nome') }}">
                    @error('site_nome') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-type me-1"></i>Textos</div>
            <div class="card-body row g-3">
                <div class="col-12">
                    <label for="site_titulo" class="form-label">Título de destaque</label>
                    <input type="text" id="site_titulo" name="site_titulo" value="{{ $campo('site_titulo') }}" maxlength="150" class="form-control {{ $erro('site_titulo') }}">
                    @error('site_titulo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-12">
                    <label for="site_subtitulo" class="form-label">Subtítulo</label>
                    <input type="text" id="site_subtitulo" name="site_subtitulo" value="{{ $campo('site_subtitulo') }}" maxlength="300" class="form-control {{ $erro('site_subtitulo') }}">
                    @error('site_subtitulo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-12">
                    <label for="site_sobre" class="form-label">Sobre a clínica</label>
                    <textarea id="site_sobre" name="site_sobre" rows="5" maxlength="3000" class="form-control {{ $erro('site_sobre') }}"
                              placeholder="Conte a história da clínica, especialidades, diferenciais...">{{ $campo('site_sobre') }}</textarea>
                    @error('site_sobre') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <div class="form-text">Deixe em branco para ocultar a seção.</div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-telephone me-1"></i>Contato</div>
            <div class="card-body row g-3">
                <div class="col-12">
                    <label for="site_endereco" class="form-label">Endereço</label>
                    <input type="text" id="site_endereco" name="site_endereco" value="{{ $campo('site_endereco') }}" maxlength="255" class="form-control {{ $erro('site_endereco') }}">
                    @error('site_endereco') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-12 col-md-6">
                    <label for="site_telefone" class="form-label">Telefone</label>
                    <input type="tel" id="site_telefone" name="site_telefone" value="{{ $campo('site_telefone') }}" maxlength="20" placeholder="(00) 0000-0000" class="form-control {{ $erro('site_telefone') }}">
                    @error('site_telefone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-12 col-md-6">
                    <label for="site_whatsapp" class="form-label">WhatsApp da clínica</label>
                    <input type="tel" id="site_whatsapp" name="site_whatsapp" value="{{ $campo('site_whatsapp') }}" maxlength="20" placeholder="(00) 90000-0000" class="form-control {{ $erro('site_whatsapp') }}">
                    @error('site_whatsapp') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <div class="form-text">Exibe o botão "Fale conosco".</div>
                </div>
                <div class="col-12 col-md-6">
                    <label for="site_email" class="form-label">E-mail</label>
                    <input type="email" id="site_email" name="site_email" value="{{ $campo('site_email') }}" maxlength="255" class="form-control {{ $erro('site_email') }}">
                    @error('site_email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-12 col-md-6">
                    <label for="site_instagram" class="form-label">Instagram</label>
                    <div class="input-group has-validation">
                        <span class="input-group-text">@</span>
                        <input type="text" id="site_instagram" name="site_instagram" value="{{ $campo('site_instagram') }}" maxlength="31" class="form-control {{ $erro('site_instagram') }}">
                        @error('site_instagram') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="col-12">
                    <label for="site_horario" class="form-label">Horário de atendimento</label>
                    <input type="text" id="site_horario" name="site_horario" value="{{ $campo('site_horario') }}" maxlength="255"
                           placeholder="Ex.: Segunda a sexta, 8h às 19h · Sábado, 8h às 12h" class="form-control {{ $erro('site_horario') }}">
                    @error('site_horario') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-lg me-1"></i>Salvar página inicial
            </button>
        </div>
    </form>
</x-layouts.app>
