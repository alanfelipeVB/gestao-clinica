<?php

namespace App\Http\Controllers;

use App\Http\Requests\PerfilRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class PerfilController extends Controller
{
    public function edit(Request $request): View
    {
        return view('perfil.edit', ['user' => $request->user()]);
    }

    public function update(PerfilRequest $request): RedirectResponse
    {
        $request->user()->update($request->validated());

        return redirect()->route('perfil.edit')->with('sucesso', 'Seus dados foram atualizados.');
    }

    public function atualizarSenha(Request $request): RedirectResponse
    {
        $dados = $request->validateWithBag('senha', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8), 'different:current_password'],
        ], [
            'password.different' => 'A nova senha deve ser diferente da senha atual.',
        ]);

        $request->user()->update(['password' => $dados['password']]);

        // Nova sessão após a troca de senha.
        $request->session()->regenerate();

        return redirect()->route('perfil.edit')->with('sucesso', 'Senha alterada com sucesso.');
    }
}
