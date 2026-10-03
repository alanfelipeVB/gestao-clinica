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
        Schema::table('agendamentos', function (Blueprint $table) {
            // Resultado do atendimento: pendente, realizado ou nao_realizado.
            $table->string('situacao', 20)->default('pendente')->after('status');
            $table->foreignId('situacao_marcada_por')->nullable()->after('situacao')
                ->constrained('users')->restrictOnDelete();
            $table->dateTime('situacao_marcada_em')->nullable()->after('situacao_marcada_por');
            $table->string('observacao_atendimento')->nullable()->after('situacao_marcada_em');

            // Relatórios mensais por profissional e situação.
            $table->index(['user_id', 'inicio', 'status', 'situacao']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agendamentos', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'inicio', 'status', 'situacao']);
            $table->dropConstrainedForeignId('situacao_marcada_por');
            $table->dropColumn(['situacao', 'situacao_marcada_em', 'observacao_atendimento']);
        });
    }
};
