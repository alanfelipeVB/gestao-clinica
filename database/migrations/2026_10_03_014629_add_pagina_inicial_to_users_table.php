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
        Schema::table('users', function (Blueprint $table) {
            $table->string('foto')->nullable()->after('profissao');
            $table->string('bio', 500)->nullable()->after('foto');
            // Controlado pelo administrador.
            $table->boolean('exibir_no_site')->default(false)->after('ativo');
            // Consentimento do próprio profissional para publicar o WhatsApp.
            $table->boolean('publicar_whatsapp')->default(false)->after('exibir_no_site');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['foto', 'bio', 'exibir_no_site', 'publicar_whatsapp']);
        });
    }
};
