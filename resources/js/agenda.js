import { Calendar } from '@fullcalendar/core';
import ptBrLocale from '@fullcalendar/core/locales/pt-br';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import listPlugin from '@fullcalendar/list';
import interactionPlugin from '@fullcalendar/interaction';

const elemento = document.getElementById('calendario');

if (elemento) {
    const filtroSala = document.getElementById('filtro-sala');
    const filtroProfissional = document.getElementById('filtro-profissional');
    const filtroData = document.getElementById('filtro-data');
    const modalElemento = document.getElementById('modal-evento');
    const modal = new window.bootstrap.Modal(modalElemento);

    const painelSalas = document.getElementById('salas-lado-a-lado');
    const colunasSalas = document.getElementById('colunas-salas');
    const tituloSalas = document.getElementById('titulo-salas');
    const salas = JSON.parse(painelSalas.dataset.salas);
    const botoesModo = document.querySelectorAll('[data-modo-agenda]');

    const ehCelular = () => window.matchMedia('(max-width: 767.98px)').matches;

    const pad = (n) => String(n).padStart(2, '0');
    const formatarData = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
    const formatarHora = (d) => `${pad(d.getHours())}:${pad(d.getMinutes())}`;
    const formatoTitulo = new Intl.DateTimeFormat('pt-BR', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });

    const escapar = (texto) => {
        const div = document.createElement('div');
        div.textContent = texto ?? '';
        return div.innerHTML;
    };

    // Preferência do modo (por navegador). Pode não estar disponível (modo privado etc.).
    const lerModo = () => {
        try {
            return localStorage.getItem('agenda.modo');
        } catch {
            return null;
        }
    };
    const salvarModo = (modo) => {
        try {
            localStorage.setItem('agenda.modo', modo);
        } catch {
            // Sem armazenamento: a escolha vale só nesta visita.
        }
    };

    let dataAtual = elemento.dataset.dataInicial ? new Date(`${elemento.dataset.dataInicial}T00:00:00`) : new Date();
    let calendario = null;
    let calendariosSalas = [];

    /**
     * Opções comuns aos dois modos. `salaFixa` é a sala da coluna no modo "lado a lado".
     */
    function opcoesBase(salaFixa = null) {
        return {
            plugins: [dayGridPlugin, timeGridPlugin, listPlugin, interactionPlugin],
            locale: ptBrLocale,
            contentHeight: 650,
            scrollTime: '07:00:00',
            nowIndicator: true,
            allDaySlot: false,
            slotDuration: '00:30:00',
            snapDuration: '00:15:00',
            slotLabelFormat: { hour: '2-digit', minute: '2-digit', hour12: false },
            eventTimeFormat: { hour: '2-digit', minute: '2-digit', hour12: false },
            dayMaxEvents: 3,
            eventDisplay: 'block',
            // Eventos simultâneos ficam lado a lado, sem sobreposição.
            slotEventOverlap: false,

            events: {
                url: elemento.dataset.eventosUrl,
                extraParams: () => ({
                    sala_id: salaFixa ? salaFixa.id : filtroSala.value,
                    user_id: filtroProfissional.value,
                }),
                failure: () => alert('Não foi possível carregar a agenda. Recarregue a página.'),
            },

            eventContent: (arg) => conteudoDoEvento(arg, Boolean(salaFixa)),

            // Tooltip nativo com o resumo completo do agendamento.
            eventDidMount: (info) => {
                const p = info.event.extendedProps;
                info.el.title = [p.horario, info.event.title, p.sala, p.resumo].filter(Boolean).join(' · ');
            },

            eventClick: (info) => {
                info.jsEvent.preventDefault();
                abrirModal(info.event);
            },

            // Clique/arrasto em horário livre: abre o formulário pré-preenchido.
            selectable: true,
            selectMirror: true,
            selectAllow: (info) => info.start >= new Date() && info.start.toDateString() === new Date(info.end - 1).toDateString(),
            select: (info) => {
                if (info.view.type === 'dayGridMonth') {
                    info.view.calendar.changeView('timeGridDay', info.start);
                    return;
                }

                // Clique simples (seleção mínima): sugere 1 hora, sem passar da meia-noite.
                let fim = info.end;
                if (info.end - info.start <= 30 * 60 * 1000) {
                    const umaHora = new Date(info.start.getTime() + 60 * 60 * 1000);
                    fim = umaHora.toDateString() === info.start.toDateString() ? umaHora : info.end;
                }

                const parametros = new URLSearchParams({
                    data: formatarData(info.start),
                    hora_inicio: formatarHora(info.start),
                    hora_fim: formatarHora(fim),
                });

                const salaId = salaFixa ? salaFixa.id : filtroSala.value;
                if (salaId) {
                    parametros.set('sala_id', salaId);
                }

                window.location.href = `${elemento.dataset.criarUrl}?${parametros}`;
            },
        };
    }

    /**
     * Conteúdo do evento conforme a visão. Na coluna da sala, o nome da sala é omitido (já está no cabeçalho).
     */
    function conteudoDoEvento(arg, colunaDeSala) {
        const p = arg.event.extendedProps;

        if (arg.view.type.startsWith('list')) {
            return { html: `<strong>${escapar(arg.event.title)}</strong> · ${escapar(p.sala)}${p.resumo ? ` <span class="text-secondary">— ${escapar(p.resumo)}</span>` : ''}` };
        }

        if (arg.view.type === 'dayGridMonth') {
            return { html: `<div class="fc-event-linha"><b>${escapar(arg.timeText)}</b> ${escapar(arg.event.title)}</div>` };
        }

        // Semana: colunas estreitas, conteúdo compacto (detalhes no tooltip e no modal).
        if (arg.view.type === 'timeGridWeek') {
            return {
                html: `
                    <div class="fc-event-detalhe">
                        <div class="fw-semibold">${escapar(arg.event.start ? formatarHora(arg.event.start) : '')}</div>
                        <div class="text-truncate">${escapar(p.profissional.replace(/^(Dra?\.\s+)/, '').split(' ')[0])}</div>
                    </div>`,
            };
        }

        return {
            html: `
                <div class="fc-event-detalhe">
                    <div class="fw-semibold">${escapar(arg.timeText)}</div>
                    <div>${escapar(arg.event.title)}</div>
                    ${colunaDeSala ? '' : `<div class="opacity-75">${escapar(p.sala)}</div>`}
                    ${p.resumo ? `<div class="opacity-75 fst-italic">${escapar(p.resumo)}</div>` : ''}
                </div>`,
        };
    }

    // ------------------------------------------------------------------
    // Modo "Calendário" (dia / semana / mês / lista)
    // ------------------------------------------------------------------

    function montarCalendario() {
        calendario = new Calendar(elemento, {
            ...opcoesBase(),
            // Com todas as salas, a visão de dia é a mais legível; com uma sala filtrada, a semana.
            initialView: ehCelular() ? 'listWeek' : (filtroSala.value ? 'timeGridWeek' : 'timeGridDay'),
            initialDate: dataAtual,
            headerToolbar: ehCelular()
                ? { left: 'prev,next', center: 'title', right: 'today' }
                : { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek' },
            footerToolbar: ehCelular() ? { center: 'dayGridMonth,timeGridDay,listWeek' } : undefined,
            buttonText: { listWeek: 'Lista' },
            views: {
                listWeek: { height: 'auto' },
            },
            datesSet: (info) => {
                dataAtual = info.view.calendar.getDate();
            },
        });

        calendario.render();
    }

    // ------------------------------------------------------------------
    // Modo "Salas lado a lado": um calendário de dia por sala, com data e rolagem sincronizadas
    // ------------------------------------------------------------------

    function salasVisiveis() {
        return filtroSala.value ? salas.filter((s) => String(s.id) === filtroSala.value) : salas;
    }

    function montarSalas() {
        colunasSalas.innerHTML = '';
        colunasSalas.style.setProperty('--colunas', salasVisiveis().length);

        calendariosSalas = salasVisiveis().map((sala) => {
            const coluna = document.createElement('div');
            coluna.className = 'agenda-sala-coluna';
            coluna.innerHTML = `
                <div class="agenda-sala-cabecalho" style="border-top-color: ${escapar(sala.cor)}">
                    <span class="rounded-circle flex-shrink-0" style="width: .7rem; height: .7rem; background: ${escapar(sala.cor)}"></span>
                    <span class="text-truncate">${escapar(sala.nome)}</span>
                </div>
                <div class="agenda-sala-calendario"></div>`;
            colunasSalas.appendChild(coluna);

            const cal = new Calendar(coluna.querySelector('.agenda-sala-calendario'), {
                ...opcoesBase(sala),
                initialView: 'timeGridDay',
                initialDate: dataAtual,
                headerToolbar: false,
                dayHeaders: false,
            });
            cal.render();

            return cal;
        });

        atualizarTituloSalas();
        sincronizarRolagem();
    }

    function irParaData(data) {
        dataAtual = data;
        calendariosSalas.forEach((cal) => cal.gotoDate(data));
        atualizarTituloSalas();
    }

    function atualizarTituloSalas() {
        const texto = formatoTitulo.format(dataAtual);
        tituloSalas.textContent = texto.charAt(0).toUpperCase() + texto.slice(1);
    }

    /**
     * Mantém as colunas na mesma faixa de horário ao rolar qualquer uma delas.
     */
    function sincronizarRolagem() {
        const rolaveis = calendariosSalas
            .map((cal) => cal.el.querySelector('.fc-timegrid-body')?.closest('.fc-scroller'))
            .filter(Boolean);

        let sincronizando = false;

        rolaveis.forEach((origem) => {
            origem.addEventListener('scroll', () => {
                if (sincronizando) {
                    return;
                }
                sincronizando = true;
                rolaveis.forEach((destino) => {
                    if (destino !== origem) {
                        destino.scrollTop = origem.scrollTop;
                    }
                });
                requestAnimationFrame(() => (sincronizando = false));
            });
        });
    }

    document.querySelector('[data-salas-acao="anterior"]').addEventListener('click', () => {
        const d = new Date(dataAtual);
        d.setDate(d.getDate() - 1);
        irParaData(d);
    });
    document.querySelector('[data-salas-acao="proximo"]').addEventListener('click', () => {
        const d = new Date(dataAtual);
        d.setDate(d.getDate() + 1);
        irParaData(d);
    });
    document.querySelector('[data-salas-acao="hoje"]').addEventListener('click', () => irParaData(new Date()));

    // ------------------------------------------------------------------
    // Troca de modo e filtros
    // ------------------------------------------------------------------

    function desmontar() {
        calendario?.destroy();
        calendario = null;
        calendariosSalas.forEach((cal) => cal.destroy());
        calendariosSalas = [];
    }

    function aplicarModo(modo) {
        desmontar();

        const ladoALado = modo === 'salas';
        elemento.classList.toggle('d-none', ladoALado);
        painelSalas.classList.toggle('d-none', !ladoALado);
        botoesModo.forEach((botao) => {
            const ativo = botao.dataset.modoAgenda === modo;
            botao.classList.toggle('active', ativo);
            botao.setAttribute('aria-pressed', String(ativo));
        });

        if (ladoALado) {
            montarSalas();
        } else {
            montarCalendario();
        }
    }

    const modoAtual = () => (painelSalas.classList.contains('d-none') ? 'calendario' : 'salas');

    botoesModo.forEach((botao) => botao.addEventListener('click', () => {
        salvarModo(botao.dataset.modoAgenda);
        aplicarModo(botao.dataset.modoAgenda);
    }));

    filtroSala.addEventListener('change', () => {
        if (modoAtual() === 'salas') {
            montarSalas();
        } else {
            calendario.refetchEvents();
        }
    });

    filtroProfissional.addEventListener('change', () => {
        calendario?.refetchEvents();
        calendariosSalas.forEach((cal) => cal.refetchEvents());
    });

    filtroData.addEventListener('change', () => {
        if (!filtroData.value) {
            return;
        }

        if (modoAtual() === 'salas') {
            irParaData(new Date(`${filtroData.value}T00:00:00`));
        } else {
            calendario.gotoDate(filtroData.value);
        }
    });

    // Padrão: salas lado a lado no computador (quando há mais de uma sala); no celular, o calendário em lista.
    const modoInicial = lerModo() ?? (!ehCelular() && salas.length > 1 ? 'salas' : 'calendario');
    aplicarModo(modoInicial);

    // ------------------------------------------------------------------
    // Modal de detalhes
    // ------------------------------------------------------------------

    function abrirModal(evento) {
        const p = evento.extendedProps;
        const campo = (nome) => modalElemento.querySelector(`[data-campo="${nome}"]`);
        const blocos = (nome) => modalElemento.querySelectorAll(`[data-bloco="${nome}"]`);
        const mostrar = (nome, visivel) => blocos(nome).forEach((el) => el.classList.toggle('d-none', !visivel));

        campo('cor').style.background = evento.backgroundColor;
        campo('sala').textContent = p.sala;
        campo('data').textContent = p.data.charAt(0).toUpperCase() + p.data.slice(1);
        campo('horario').textContent = p.horario;
        campo('profissional').textContent = p.profissional;
        campo('descricao').textContent = p.descricao ?? '';

        mostrar('descricao', p.descricao !== null);
        campo('situacao').textContent = p.situacao ?? '';
        mostrar('situacao', Boolean(p.situacao));
        mostrar('privado', p.descricao === null);

        mostrar('detalhes', Boolean(p.url_detalhes));
        blocos('detalhes').forEach((el) => (el.href = p.url_detalhes ?? '#'));

        mostrar('editar', Boolean(p.url_editar));
        blocos('editar').forEach((el) => (el.href = p.url_editar ?? '#'));

        mostrar('cancelar', Boolean(p.url_cancelar));
        const form = document.getElementById('form-cancelar-evento');
        form.action = p.url_cancelar ?? '';
        form.reset();

        modal.show();
    }

    document.getElementById('form-cancelar-evento').addEventListener('submit', (event) => {
        if (!window.confirm('Cancelar este agendamento? O horário ficará livre.')) {
            event.preventDefault();
        }
    });
}
