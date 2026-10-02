<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Provisório: o conteúdo completo dos dashboards (admin e profissional) vem em etapa própria.
     */
    public function __invoke(): View
    {
        return view('dashboard.index');
    }
}
