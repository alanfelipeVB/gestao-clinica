@php
    use App\Support\Whatsapp;

    $whatsappClinica = Whatsapp::link($textos['site_whatsapp'], "Olá! Vim pelo site da {$textos['site_nome']} e gostaria de mais informações.");
    $temContato = $textos['site_endereco'] || $textos['site_telefone'] || $textos['site_whatsapp']
        || $textos['site_email'] || $textos['site_instagram'] || $textos['site_horario'];
@endphp

<x-layouts.site :descricao="$textos['site_subtitulo']">
    <x-slot:menu>
        @if ($textos['site_sobre'])
            <li class="nav-item"><a class="nav-link" href="#sobre">Sobre</a></li>
        @endif
        @if ($profissionais->isNotEmpty())
            <li class="nav-item"><a class="nav-link" href="#profissionais">Equipe</a></li>
        @endif
        @if ($temContato)
            <li class="nav-item"><a class="nav-link" href="#contato">Contato</a></li>
        @endif
    </x-slot:menu>

    {{-- Destaque --}}
    <section class="site-hero text-white">
        <div class="container py-5">
            <div class="row align-items-center g-4 py-lg-4">
                <div class="col-lg-8">
                    <h1 class="display-5 fw-semibold mb-3">{{ $textos['site_titulo'] }}</h1>
                    <p class="lead text-white-50 mb-4">{{ $textos['site_subtitulo'] }}</p>
                    <div class="d-flex flex-wrap gap-2">
                        @if ($whatsappClinica)
                            <a href="{{ $whatsappClinica }}" target="_blank" rel="noopener" class="btn btn-light btn-lg">
                                <i class="bi bi-whatsapp me-1 text-success"></i>Fale conosco
                            </a>
                        @endif
                        @if ($profissionais->isNotEmpty())
                            <a href="#profissionais" class="btn btn-outline-light btn-lg">Conheça a equipe</a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Sobre --}}
    @if ($textos['site_sobre'])
        <section id="sobre" class="py-5">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        <h2 class="h3 mb-3">Sobre a clínica</h2>
                        <p class="text-secondary fs-5 mb-0" style="white-space: pre-line;">{{ $textos['site_sobre'] }}</p>
                    </div>
                </div>
            </div>
        </section>
    @endif

    {{-- Equipe --}}
    @if ($profissionais->isNotEmpty())
        <section id="profissionais" class="py-5 bg-body-tertiary border-top">
            <div class="container">
                <h2 class="h3 mb-1 text-center">Nossa equipe</h2>
                <p class="text-secondary text-center mb-4">Conheça os profissionais da {{ $textos['site_nome'] }}.</p>

                <div class="row g-4 justify-content-center">
                    @foreach ($profissionais as $profissional)
                        @php
                            $whatsapp = Whatsapp::link(
                                $profissional->whatsappPublico(),
                                "Olá, {$profissional->primeiroNome()}! Vim pelo site da {$textos['site_nome']} e gostaria de agendar um atendimento.",
                            );
                        @endphp
                        <div class="col-12 col-sm-6 col-lg-4">
                            <div class="card h-100 text-center">
                                <div class="card-body d-flex flex-column align-items-center">
                                    @if ($profissional->urlFoto())
                                        <img src="{{ $profissional->urlFoto() }}" alt="Foto de {{ $profissional->nome }}"
                                             class="rounded-circle mb-3 border" style="width: 7.5rem; height: 7.5rem; object-fit: cover;" loading="lazy">
                                    @else
                                        <span class="rounded-circle bg-primary-subtle text-primary-emphasis d-inline-flex align-items-center justify-content-center fs-2 fw-semibold mb-3"
                                              style="width: 7.5rem; height: 7.5rem;" aria-hidden="true">{{ $profissional->iniciais() }}</span>
                                    @endif

                                    <h3 class="h5 mb-0">{{ $profissional->nome }}</h3>
                                    @if ($profissional->profissao)
                                        <div class="text-primary small fw-semibold mb-2">{{ $profissional->profissao }}</div>
                                    @endif
                                    @if ($profissional->bio)
                                        <p class="text-secondary small mb-3" style="white-space: pre-line;">{{ $profissional->bio }}</p>
                                    @endif

                                    <div class="mt-auto d-flex flex-wrap justify-content-center gap-2">
                                        @if ($whatsapp)
                                            <a href="{{ $whatsapp }}" target="_blank" rel="noopener" class="btn btn-success btn-sm">
                                                <i class="bi bi-whatsapp me-1"></i>WhatsApp
                                            </a>
                                        @endif
                                        @if ($profissional->instagram)
                                            <a href="https://instagram.com/{{ $profissional->instagram }}" target="_blank" rel="noopener"
                                               class="btn btn-outline-secondary btn-sm" aria-label="Instagram de {{ $profissional->nome }}">
                                                <i class="bi bi-instagram me-1"></i>{{ '@'.$profissional->instagram }}
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Contato --}}
    @if ($temContato)
        <section id="contato" class="py-5 bg-white border-top">
            <div class="container">
                <h2 class="h3 mb-4 text-center">Contato</h2>
                <div class="row g-3 justify-content-center">
                    @if ($textos['site_endereco'])
                        <x-site-contato icone="geo-alt" titulo="Endereço">
                            <a href="https://www.google.com/maps/search/?api=1&query={{ rawurlencode($textos['site_endereco']) }}"
                               target="_blank" rel="noopener" class="link-body-emphasis">{{ $textos['site_endereco'] }}</a>
                        </x-site-contato>
                    @endif
                    @if ($textos['site_horario'])
                        <x-site-contato icone="clock" titulo="Horário de atendimento">{{ $textos['site_horario'] }}</x-site-contato>
                    @endif
                    @if ($textos['site_telefone'])
                        <x-site-contato icone="telephone" titulo="Telefone">
                            <a href="tel:{{ preg_replace('/[^0-9+]/', '', $textos['site_telefone']) }}" class="link-body-emphasis">{{ $textos['site_telefone'] }}</a>
                        </x-site-contato>
                    @endif
                    @if ($whatsappClinica)
                        <x-site-contato icone="whatsapp" titulo="WhatsApp">
                            <a href="{{ $whatsappClinica }}" target="_blank" rel="noopener" class="link-body-emphasis">{{ $textos['site_whatsapp'] }}</a>
                        </x-site-contato>
                    @endif
                    @if ($textos['site_email'])
                        <x-site-contato icone="envelope" titulo="E-mail">
                            <a href="mailto:{{ $textos['site_email'] }}" class="link-body-emphasis">{{ $textos['site_email'] }}</a>
                        </x-site-contato>
                    @endif
                    @if ($textos['site_instagram'])
                        <x-site-contato icone="instagram" titulo="Instagram">
                            <a href="https://instagram.com/{{ $textos['site_instagram'] }}" target="_blank" rel="noopener" class="link-body-emphasis">{{ '@'.$textos['site_instagram'] }}</a>
                        </x-site-contato>
                    @endif
                </div>
            </div>
        </section>
    @endif
</x-layouts.site>
