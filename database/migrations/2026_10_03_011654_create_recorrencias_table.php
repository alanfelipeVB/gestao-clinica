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
        // Série de agendamentos recorrentes. Cada ocorrência é um agendamento normal.
        Schema::create('recorrencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('sala_id')->constrained('salas')->restrictOnDelete();
            $table->string('frequencia', 20);
            $table->date('data_inicio');
            $table->date('data_fim')->nullable();
            $table->unsignedSmallInteger('ocorrencias')->nullable();
            $table->foreignId('criado_por')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::table('agendamentos', function (Blueprint $table) {
            $table->foreignId('recorrencia_id')->nullable()->after('sala_id')
                ->constrained('recorrencias')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agendamentos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('recorrencia_id');
        });

        Schema::dropIfExists('recorrencias');
    }
};
