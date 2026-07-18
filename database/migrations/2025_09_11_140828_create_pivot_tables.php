<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Retorna a migração consolidada para criar as tabelas pivô (Many-to-Many).
 *
 * @return Migration - Migração consolidada.
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
        // Cria a tabela genre_parent_genre.
        Schema::create('genre_parent_genre', function (Blueprint $table) {
            $table->foreignId('genre_id')->constrained('genres')->onDelete('cascade');
            $table->foreignId('parent_genre_id')->constrained('genres')->onDelete('cascade');
            $table->primary(['genre_id', 'parent_genre_id']);
        });

        // Cria a tabela band_genre.
        Schema::create('band_genre', function (Blueprint $table) {
            $table->foreignId('band_id')->constrained('bands')->onDelete('cascade');
            $table->foreignId('genre_id')->constrained('genres')->onDelete('cascade');
            $table->primary(['band_id', 'genre_id']);
        });

        // Cria a tabela email_permission_user.
        Schema::create('email_permission_user', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('email_permission_id')->constrained('email_permissions')->onDelete('cascade');
            $table->primary(['user_id', 'email_permission_id']);
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
        // Elimina as tabelas por ordem inversa à da criação.
        Schema::dropIfExists('email_permission_user');
        Schema::dropIfExists('band_genre');
        Schema::dropIfExists('genre_parent_genre');
    }
};
