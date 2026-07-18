<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Retorna a migração para criar a tabela de notificações.
 *
 * @return Migration - Migração para criar a tabela de notificações.
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
        // Cria a tabela notifications
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
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
        // Elimina a tabela notifications
        Schema::dropIfExists('notifications');
    }
};
