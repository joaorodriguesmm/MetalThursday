<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Retorna a migração para criar a tabela mt_listens.
 *
 * @return Migration - Migração para criar a tabela mt_listens.
 *
 * @since 1.0
 * @version 1.0
 */
return new class extends Migration
{
    /**
     * Executa a migração.
     *
     * @return void
     *
     * @since 1.0
     * @version 1.0
     */
    public function up(): void
    {
        Schema::create('mt_listens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->morphs('listenable');
            $table->timestamps();
            $table->unique(['user_id', 'listenable_id', 'listenable_type']);
        });
    }

    /**
     * Reverte a migração.
     *
     * @return void
     *
     * @since 1.0
     * @version 1.0
     */
    public function down(): void
    {
        Schema::dropIfExists('mt_listens');
    }
};
