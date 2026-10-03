<?php

namespace App\Http\Controllers;

use App\Models\Sala;
use App\Services\RelatorioService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Relatório mensal: o administrador vê todos os profissionais; o profissional, apenas os próprios números.
 */
class RelatorioController extends Controller
{
    public function __construct(private readonly RelatorioService $relatorios)
    {
    }

    public function index(Request $request): View
    {
        [$mes, $salaId] = $this->filtros($request);
        $user = $request->user();

        $linhas = $this->relatorios->mensal($mes, $salaId, $user->isAdmin() ? null : $user);

        return view('relatorio.index', [
            'mes' => $mes,
            'salaId' => $salaId,
            'salas' => Sala::orderBy('nome')->get(['id', 'nome']),
            'linhas' => $linhas,
            'totais' => $this->relatorios->totais($linhas),
            'agendamentos' => $user->isAdmin() ? collect() : $this->relatorios->agendamentosDoMes($user, $mes, $salaId),
        ]);
    }

    /**
     * CSV (separador ";" e UTF-8 com BOM, para abrir direto no Excel).
     */
    public function exportar(Request $request): StreamedResponse
    {
        [$mes, $salaId] = $this->filtros($request);
        $user = $request->user();

        $linhas = $this->relatorios->mensal($mes, $salaId, $user->isAdmin() ? null : $user);
        $totais = $this->relatorios->totais($linhas);
        $arquivo = 'relatorio-atendimentos-'.$mes->format('Y-m').'.csv';

        return response()->streamDownload(function () use ($linhas, $totais) {
            $saida = fopen('php://output', 'w');
            fwrite($saida, "\xEF\xBB\xBF");

            fputcsv($saida, [
                'Profissional', 'Profissão', 'Agendados', 'Realizados', 'Não realizados',
                'Pendentes', 'A realizar', 'Cancelados', 'Horas realizadas', 'Comparecimento (%)',
            ], ';');

            foreach ($linhas as $linha) {
                fputcsv($saida, [
                    $linha['profissional']->nome,
                    $linha['profissional']->profissao,
                    ...$this->valoresCsv($linha),
                ], ';');
            }

            if ($linhas->count() > 1) {
                fputcsv($saida, ['Total', '', ...$this->valoresCsv($totais)], ';');
            }

            fclose($saida);
        }, $arquivo, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * @return array{0: Carbon, 1: ?int}
     */
    private function filtros(Request $request): array
    {
        $dados = $request->validate([
            'mes' => ['nullable', 'date_format:Y-m'],
            'sala_id' => ['nullable', 'integer'],
        ]);

        $mes = isset($dados['mes'])
            ? Carbon::createFromFormat('Y-m-d', $dados['mes'].'-01')->startOfDay()
            : today()->startOfMonth();

        return [$mes, isset($dados['sala_id']) ? (int) $dados['sala_id'] : null];
    }

    /**
     * @param  array<string, mixed>  $valores
     * @return list<string|int>
     */
    private function valoresCsv(array $valores): array
    {
        return [
            $valores['agendados'],
            $valores['realizados'],
            $valores['nao_realizados'],
            $valores['pendentes'],
            $valores['futuros'],
            $valores['cancelados'],
            number_format($valores['minutos_realizados'] / 60, 1, ',', ''),
            $valores['taxa'] === null ? '' : number_format($valores['taxa'], 1, ',', ''),
        ];
    }
}
