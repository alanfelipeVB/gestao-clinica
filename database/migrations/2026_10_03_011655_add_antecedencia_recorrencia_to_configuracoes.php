<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('configuracoes')->insertOrIgnore([
            'chave' => 'antecedencia_recorrencia_dias',
            'valor' => '90',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('configuracoes')->where('chave', 'antecedencia_recorrencia_dias')->delete();
    }
};
