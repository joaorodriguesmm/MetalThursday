<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cria a tabela intermédia entre artistas e géneros musicais.
 *
 * Cada associação entre um artista e um género pode existir apenas uma vez.
 *
 * @since 2.0.0
 */
return new class extends Migration
{
    /**
     * Cria a tabela intermédia entre artistas e géneros.
     *
     * @since 2.0.0
     */
    public function up(): void
    {
        Schema::create(
            'artista_genero',
            static function (Blueprint $tabela): void {
                $tabela->foreignId(
                    'artista_id',
                );

                $tabela->foreignId(
                    'genero_id',
                );

                $tabela->primary(
                    [
                        'artista_id',
                        'genero_id',
                    ],
                    'artista_genero_primaria',
                );

                /*
                 * A chave primária começa por artista_id. Este índice
                 * adicional otimiza a obtenção dos artistas associados a um
                 * género.
                 */
                $tabela->index(
                    'genero_id',
                    'artista_genero_genero_indice',
                );

                $tabela
                    ->foreign(
                        'artista_id',
                    )
                    ->references(
                        'id',
                    )
                    ->on(
                        'artistas',
                    )
                    ->cascadeOnDelete();

                $tabela
                    ->foreign(
                        'genero_id',
                    )
                    ->references(
                        'id',
                    )
                    ->on(
                        'generos',
                    )
                    ->cascadeOnDelete();
            },
        );
    }

    /**
     * Elimina a tabela intermédia entre artistas e géneros.
     *
     * @since 2.0.0
     */
    public function down(): void
    {
        Schema::dropIfExists(
            'artista_genero',
        );
    }
};
