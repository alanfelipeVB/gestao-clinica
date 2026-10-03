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
        Schema::create('tutoriais', function (Blueprint $table) {
            $table->id();
            $table->string('titulo', 150);
            $table->text('descricao')->nullable();
            // Caminho no disco "local" (storage/app/private), nunca exposto publicamente.
            $table->string('arquivo');
            $table->string('mime', 50);
            $table->unsignedBigInteger('tamanho');
            $table->unsignedSmallInteger('ordem')->default(0);
            $table->boolean('publicado')->default(false)->index();
            $table->foreignId('criado_por')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });

        // Quem marcou cada tutorial como assistido.
        Schema::create('tutorial_visualizacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tutorial_id')->constrained('tutoriais')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->dateTime('assistido_em');
            $table->unique(['tutorial_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tutorial_visualizacoes');
        Schema::dropIfExists('tutoriais');
    }
};
