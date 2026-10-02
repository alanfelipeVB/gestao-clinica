<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, DashboardService $dashboard): View
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            return view('dashboard.admin', [
                'totais' => $dashboard->totais(),
                'salas' => $dashboard->situacaoDasSalas(),
                'hoje' => $dashboard->agendamentosDeHoje(),
                'proximos' => $dashboard->proximosAgendamentos(),
            ]);
        }

        return view('dashboard.profissional', [
            'hoje' => $dashboard->agendamentosDeHoje($user),
            'proximos' => $dashboard->proximosAgendamentos($user, dias: 30),
        ]);
    }
}
