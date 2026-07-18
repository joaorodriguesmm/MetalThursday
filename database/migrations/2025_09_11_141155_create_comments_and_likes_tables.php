<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Retorna a migração consolidada para criar as tabelas de comentários e likes.
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
        // Cria a tabela mt_comments.
        Schema::create('mt_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->text('content');
            $table->morphs('commentable');
            $table->foreignId('parent_id')->nullable()->constrained('mt_comments')->onDelete('cascade');
            $table->timestamps();
        });

        // Cria a tabela mt_likes.
        Schema::create('mt_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('comment_id')->constrained('mt_comments')->onDelete('cascade');
            $table->timestamps();
            $table->unique(['user_id', 'comment_id']);
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
        Schema::dropIfExists('mt_likes');
        Schema::dropIfExists('mt_comments');
    }
};
