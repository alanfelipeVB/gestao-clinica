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

    const ehCelular = () => window.matchMedia('(max-width: 767.98px)').matches;

    const pad = (n) => String(n).padStart(2, '0');
    const formatarData = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
    const formatarHora = (d) => `${pad(d.getHours())}:${pad(d.getMinutes())}`;

    const escapar = (texto) => {
        const div = document.createElement('div');
        div.textContent = texto ?? '';
        return div.innerHTML;
    };

    const calendario = new Calendar(elemento, {
        plugins: [dayGridPlugin, timeGridPlugin, listPlugin, interactionPlugin],
        locale: ptBrLocale,
        // Com todas as salas, a visão de dia é a mais legível (salas lado a lado);
        // com uma sala filtrada, a semana dá a melhor visão de disponibilidade.
        initialView: ehCelular() ? 'listWeek' : (filtroSala.value ? 'timeGridWeek' : 'timeGridDay'),
        initialDate: elemento.dataset.dataInicial || undefined,
        headerToolbar: ehCelular()
            ? { left: 'prev,next', center: 'title', right: 'today' }
            : { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek' },
        footerToolbar: ehCelular() ? { center: 'dayGridMonth,timeGridDay,listWeek' } : undefined,
        buttonText: { listWeek: 'Lista' },
        contentHeight: 650,
        views: {
            listWeek: { height: 'auto' },
        },
        scrollTime: '07:00:00',
        nowIndicator: true,
        allDaySlot: false,
        slotDuration: '00:30:00',
        snapDuration: '00:15:00',
        slotLabelFormat: { hour: '2-digit', minute: '2-digit', hour12: false },
        eventTimeFormat: { hour: '2-digit', minute: '2-digit', hour12: false },
        dayMaxEvents: 3,
        eventDisplay: 'block',
        // Eventos simultâneos (salas diferentes) ficam lado a lado, sem sobreposição.
        slotEventOverlap: false,

        events: {
            url: elemento.dataset.eventosUrl,
            extraParams: () => ({
                sala_id: filtroSala.value,
                user_id: filtroProfissional.value,
            }),
            failure: () => alert('Não foi possível carregar a agenda. Recarregue a página.'),
        },

        // Conteúdo do evento: profissional, sala e descrição resumida.
        eventContent: (arg) => {
            if (arg.view.type.startsWith('list')) {
                const p = arg.event.extendedProps;
                return { html: `<strong>${escapar(arg.event.title)}</strong> · ${escapar(p.sala)}${p.resumo ? ` <span class="text-secondary">— ${escapar(p.resumo)}</span>` : ''}` };
            }

            if (arg.view.type === 'dayGridMonth') {
                return { html: `<div class="fc-event-linha"><b>${escapar(arg.timeText)}</b> ${escapar(arg.event.title)}</div>` };
            }

            const p = arg.event.extendedProps;

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
                        <div class="opacity-75">${escapar(p.sala)}</div>
                        ${p.resumo ? `<div class="opacity-75 fst-italic">${escapar(p.resumo)}</div>` : ''}
                    </div>`,
            };
        },

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
                calendario.changeView('timeGridDay', info.start);
                return;
            }

            const parametros = new URLSearchParams({
                data: formatarData(info.start),
                hora_inicio: formatarHora(info.start),
                hora_fim: formatarHora(info.end),
            });

            if (filtroSala.value) {
                parametros.set('sala_id', filtroSala.value);
            }

            window.location.href = `${elemento.dataset.criarUrl}?${parametros}`;
        },
    });

    calendario.render();

    filtroSala.addEventListener('change', () => calendario.refetchEvents());
    filtroProfissional.addEventListener('change', () => calendario.refetchEvents());
    filtroData.addEventListener('change', () => {
        if (filtroData.value) {
            calendario.gotoDate(filtroData.value);
        }
    });

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
