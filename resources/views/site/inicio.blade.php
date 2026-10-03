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
        @isset($profissionais)
            @if ($profissionais->isNotEmpty())
                <li class="nav-item"><a class="nav-link" href="#profissionais">Profissionais</a></li>
            @endif
        @endisset
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
                        @isset($profissionais)
                            @if ($profissionais->isNotEmpty())
                                <a href="#profissionais" class="btn btn-outline-light btn-lg">Conheça a equipe</a>
                            @endif
                        @endisset
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
