<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('agendamentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('sala_id')->constrained('salas')->restrictOnDelete();
            $table->dateTime('inicio');
            $table->dateTime('fim');
            $table->text('descricao');
            $table->string('status', 20)->default('agendado');
            $table->foreignId('criado_por')->constrained('users')->restrictOnDelete();
            $table->foreignId('cancelado_por')->nullable()->constrained('users')->restrictOnDelete();
            $table->dateTime('cancelado_em')->nullable();
            $table->string('motivo_cancelamento')->nullable();
            $table->timestamps();

            // Busca de conflitos por sala e por profissional.
            $table->index(['sala_id', 'status', 'inicio', 'fim']);
            $table->index(['user_id', 'status', 'inicio', 'fim']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agendamentos');
    }
};
