<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cria a tabela dos gostos atribuídos aos comentários.
 *
 * Cada utilizador pode atribuir apenas um gosto a cada comentário.
 *
 * @since 2.0.0
 *
 * @version 2.0.0
 */
return new class extends Migration
{
    /**
     * Cria a tabela dos gostos.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    public function up(): void
    {
        Schema::create(
            'gostos',
            static function (Blueprint $tabela): void {
                $tabela->id();

                $tabela
                    ->foreignId(
                        'utilizador_id',
                    )
                    ->constrained(
                        table: 'utilizadores',
                    )
                    ->cascadeOnUpdate()
                    ->cascadeOnDelete();

                $tabela
                    ->foreignId(
                        'comentario_id',
                    )
                    ->constrained(
                        table: 'comentarios',
                    )
                    ->cascadeOnUpdate()
                    ->cascadeOnDelete();

                $tabela->timestamps();

                $tabela->unique(
                    [
                        'utilizador_id',
                        'comentario_id',
                    ],
                    'gostos_utilizador_comentario_unico',
                );

                /*
                 * A restrição única começa por utilizador_id. Este índice
                 * adicional otimiza a contagem e listagem dos gostos de um
                 * comentário.
                 */
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
     * @version 2.0.0
     */
    public function down(): void
    {
        Schema::dropIfExists(
            'gostos',
        );
    }
};
