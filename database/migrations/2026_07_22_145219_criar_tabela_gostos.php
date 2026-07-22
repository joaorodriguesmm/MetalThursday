<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cria a tabela dos gostos atribuídos aos comentários.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
return new class extends Migration
{
    /**
     * Cria a tabela dos gostos.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function up(): void
    {
        Schema::create(
            'gostos',
            function (Blueprint $tabela): void {
                $tabela->id();

                $tabela
                    ->foreignId('utilizador_id')
                    ->constrained('utilizadores')
                    ->cascadeOnDelete();

                $tabela
                    ->foreignId('comentario_id')
                    ->constrained('comentarios')
                    ->cascadeOnDelete();

                $tabela->timestamps();

                $tabela->unique(
                    [
                        'utilizador_id',
                        'comentario_id',
                    ],
                    'gostos_utilizador_comentario_unico',
                );

                $tabela->index(
                    'comentario_id',
                    'gostos_comentario_indice',
                );
            },
        );
    }

    /**
     * Elimina a tabela dos gostos.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function down(): void
    {
        Schema::dropIfExists('gostos');
    }
};
