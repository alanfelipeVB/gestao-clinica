<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Administrador inicial
    |--------------------------------------------------------------------------
    |
    | Dados usados pelo AdminSeeder para criar o primeiro administrador.
    | Defina os valores no .env — nunca versione senhas reais.
    |
    */

    'admin' => [
        'nome' => env('ADMIN_NOME', 'Administrador'),
        'email' => env('ADMIN_EMAIL'),
        'senha' => env('ADMIN_SENHA'),
    ],

];
