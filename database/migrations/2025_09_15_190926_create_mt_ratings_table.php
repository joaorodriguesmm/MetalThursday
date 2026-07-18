<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Retorna a migração para criar a tabela mt_ratings.
 *
 * @return Migration - Migração para criar a tabela mt_ratings.
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
        Schema::create('mt_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('rating', 3, 1);
            $table->morphs('rateable');
            $table->timestamps();
            $table->unique(['user_id', 'rateable_id', 'rateable_type']);
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
        Schema::dropIfExists('mt_ratings');
    }
};
