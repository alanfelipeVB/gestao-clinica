<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('configuracoes', function (Blueprint $table) {
            $table->id();
            $table->string('chave', 100)->unique();
            $table->string('valor')->nullable();
            $table->timestamps();
        });

        // Valores iniciais: existem em qualquer ambiente, sem depender de seeder.
        DB::table('configuracoes')->insert([
            'chave' => 'antecedencia_maxima_dias',
            'valor' => '30',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('configuracoes');
    }
};
