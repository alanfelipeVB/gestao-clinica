<?php

namespace App\Http\Controllers;

use App\Models\Sala;
use Illuminate\View\View;

/**
 * Consulta das salas disponíveis (todos os perfis).
 */
class SalaController extends Controller
{
    public function index(): View
    {
        return view('salas.index', [
            'salas' => Sala::ativas()->orderBy('nome')->get(),
        ]);
    }
}
