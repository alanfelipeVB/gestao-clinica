<?php

namespace App\Http\Controllers;

use App\Enums\SituacaoAtendimento;
use App\Enums\StatusAgendamento;
use App\Http\Requests\AgendamentoRequest;
use App\Models\Agendamento;
use App\Models\Sala;
use App\Models\User;
use App\Services\AgendamentoService;
use App\Services\RecorrenciaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AgendamentoController extends Controller
{
    public function __construct(
        private readonly AgendamentoService $agendamentos,
        private readonly RecorrenciaService $recorrencias,
    ) {
    }

    public function index(Request $request): View
    {
        $user = $request->user();

        $filtros = $request->validate([
            'periodo' => ['nullable', Rule::in(['proximos', 'anteriores', 'todos'])],
            'status' => ['nullable', Rule::enum(StatusAgendamento::class)],
            'situacao' => ['nullable', Rule::enum(SituacaoAtendimento::class)],
            'sala_id' => ['nullable', 'integer'],
            'user_id' => ['nullable', 'integer'],
            'data' => ['nullable', 'date_format:Y-m-d'],
            'recorrencia_id' => ['nullable', 'integer'],
        ]);

        $periodo = $filtros['periodo'] ?? 'proximos';

        $agendamentos = Agendamento::query()
            ->with(['sala', 'profissional'])
            // Profissional vê apenas os próprios agendamentos.
            ->when(! $user->isAdmin(), fn ($q) => $q->where('user_id', $user->id))
            ->when($user->isAdmin() && ! empty($filtros['user_id']), fn ($q) => $q->where('user_id', $filtros['user_id']))
            ->when($filtros['sala_id'] ?? null, fn ($q, $salaId) => $q->where('sala_id', $salaId))
            ->when($filtros['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            // Situação do atendimento só faz sentido para agendamentos ativos já iniciados.
            ->when($filtros['situacao'] ?? null, fn ($q, $situacao) => $q->agendados()->where('inicio', '<=', now())->where('situacao', $situacao))
            ->when($filtros['data'] ?? null, fn ($q, $data) => $q->whereDate('inicio', $data))
            ->when($filtros['recorrencia_id'] ?? null, fn ($q, $id) => $q->where('recorrencia_id', $id))
            ->when($periodo === 'proximos', fn ($q) => $q->where('fim', '>=', now())->orderBy('inicio'))
            ->when($periodo === 'anteriores', fn ($q) => $q->where('fim', '<', now())->orderByDesc('inicio'))
            ->when($periodo === 'todos', fn ($q) => $q->orderByDesc('inicio'))
            // Uma série é lida em ordem cronológica.
            ->when($filtros['recorrencia_id'] ?? null, fn ($q) => $q->reorder('inicio'))
            ->paginate(20)
            ->withQueryString();

        return view('agendamentos.index', [
            'agendamentos' => $agendamentos,
            'filtros' => [...$filtros, 'periodo' => $periodo],
            'salas' => Sala::orderBy('nome')->get(['id', 'nome']),
            'profissionais' => $user->isAdmin() ? User::orderBy('nome')->get(['id', 'nome']) : collect(),
        ]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', Agendamento::class);

        // Pré-preenchimento opcional (ex.: vindo da página de salas ou da agenda).
        $agendamento = new Agendamento([
            'sala_id' => $request->integer('sala_id') ?: null,
            'user_id' => $request->user()->id,
        ]);

        return view('agendamentos.create', [
            ...$this->dadosDoFormulario($agendamento),
            'preenchimento' => $request->only(['data', 'hora_inicio', 'hora_fim']),
        ]);
    }

    public function store(AgendamentoRequest $request): RedirectResponse|View
    {
        Gate::authorize('create', Agendamento::class);

        if ($request->repeteSerie()) {
            return $this->criarSerie($request);
        }

        $agendamento = $this->agendamentos->criar($request->dados(), $request->user());

        return redirect()
            ->route('agendamentos.show', $agendamento)
            ->with('sucesso', 'Agendamento realizado com sucesso.');
    }

    public function show(Agendamento $agendamento): View
    {
        Gate::authorize('view', $agendamento);

        $agendamento->load(['sala', 'profissional', 'criador', 'canceladoPor', 'situacaoMarcadaPor', 'recorrencia']);

        return view('agendamentos.show', ['agendamento' => $agendamento]);
    }

    public function edit(Agendamento $agendamento): View
    {
        Gate::authorize('update', $agendamento);

        return view('agendamentos.edit', [
            ...$this->dadosDoFormulario($agendamento),
            'preenchimento' => [],
        ]);
    }

    public function update(AgendamentoRequest $request, Agendamento $agendamento): RedirectResponse
    {
        Gate::authorize('update', $agendamento);

        $this->agendamentos->atualizar($agendamento, $request->dados());

        return redirect()
            ->route('agendamentos.show', $agendamento)
            ->with('sucesso', 'Agendamento atualizado.');
    }

    public function cancelar(Request $request, Agendamento $agendamento): RedirectResponse
    {
        Gate::authorize('cancel', $agendamento);

        $dados = $request->validate([
            'motivo_cancelamento' => ['nullable', 'string', 'max:255'],
            'escopo' => ['nullable', Rule::in(['este', 'proximos'])],
        ]);

        $motivo = $dados['motivo_cancelamento'] ?? null;

        if (($dados['escopo'] ?? 'este') === 'proximos' && $agendamento->recorrencia_id) {
            $total = $this->agendamentos->cancelarEsteEProximos($agendamento, $request->user(), $motivo);
            $mensagem = "{$total} agendamento(s) da série cancelado(s). Os horários foram liberados.";
        } else {
            $this->agendamentos->cancelar($agendamento, $request->user(), $motivo);
            $mensagem = 'Agendamento cancelado. O horário foi liberado.';
        }

        return redirect()
            ->route('agendamentos.show', $agendamento)
            ->with('sucesso', $mensagem);
    }

    /**
     * Sem "confirmado": mostra a prévia das datas. Com "confirmado": cria as ocorrências livres.
     */
    private function criarSerie(AgendamentoRequest $request): RedirectResponse|View
    {
        $dados = $request->dados();
        $serie = $request->dadosRecorrencia();

        if (! $request->boolean('confirmado')) {
            $previa = $this->recorrencias->previa($dados, $serie);

            return view('agendamentos.previa', [
                'previa' => $previa,
                'serie' => $serie,
                'dados' => $dados,
                'sala' => Sala::findOrFail($dados['sala_id']),
                'profissional' => User::findOrFail($dados['user_id']),
                'entrada' => $request->safe()->except('confirmado'),
                'livres' => $previa->where('situacao', RecorrenciaService::LIVRE)->count(),
            ]);
        }

        $resultado = $this->recorrencias->criar($dados, $serie, $request->user());
        $criados = $resultado['criados']->count();

        $mensagem = "Série criada: {$criados} agendamento(s).";
        if ($resultado['ignorados'] > 0) {
            $mensagem .= " {$resultado['ignorados']} data(s) ficaram de fora por conflito ou restrição.";
        }

        return redirect()
            ->route('agendamentos.index', ['recorrencia_id' => $resultado['recorrencia']->id, 'periodo' => 'todos'])
            ->with('sucesso', $mensagem);
    }

    public function registrarAtendimento(Request $request, Agendamento $agendamento): RedirectResponse
    {
        Gate::authorize('registrarAtendimento', $agendamento);

        $dados = $request->validate([
            'situacao' => ['required', Rule::in([SituacaoAtendimento::Realizado->value, SituacaoAtendimento::NaoRealizado->value])],
            'observacao_atendimento' => ['nullable', 'string', 'max:255'],
        ]);

        $situacao = SituacaoAtendimento::from($dados['situacao']);

        $this->agendamentos->registrarAtendimento(
            $agendamento,
            $situacao,
            $request->user(),
            $dados['observacao_atendimento'] ?? null,
        );

        return back()->with('sucesso', "Atendimento registrado como \"{$situacao->label()}\".");
    }

    /**
     * Listas usadas no formulário. Inclui a sala/profissional atual mesmo se estiverem inativos.
     *
     * @return array<string, mixed>
     */
    private function dadosDoFormulario(Agendamento $agendamento): array
    {
        $salas = Sala::query()
            ->where(fn ($q) => $q->where('ativa', true)->orWhere('id', $agendamento->sala_id))
            ->orderBy('nome')
            ->get();

        $profissionais = request()->user()->isAdmin()
            ? User::query()
                ->where(fn ($q) => $q->where('ativo', true)->orWhere('id', $agendamento->user_id))
                ->orderBy('nome')
                ->get()
            : collect();

        return [
            'agendamento' => $agendamento,
            'salas' => $salas,
            'profissionais' => $profissionais,
            'horarios' => AgendamentoService::horarios(),
            'dataLimite' => $this->agendamentos->dataLimite(),
            'dataLimiteRecorrencia' => $this->agendamentos->dataLimiteRecorrencia(),
        ];
    }
}
