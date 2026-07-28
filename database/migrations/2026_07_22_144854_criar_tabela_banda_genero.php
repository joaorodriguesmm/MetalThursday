<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cria a tabela intermédia entre bandas e géneros musicais.
 *
 * Cada associação entre uma banda e um género pode existir apenas uma vez.
 *
 * @since 2.0.0
 *
 * @version 2.0.0
 */
return new class extends Migration
{
    /**
     * Cria a tabela intermédia entre bandas e géneros.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    public function up(): void
    {
        Schema::create(
            'banda_genero',
            static function (Blueprint $tabela): void {
                $tabela
                    ->foreignId(
                        'banda_id',
                    )
                    ->constrained(
                        table: 'bandas',
                    )
                    ->cascadeOnUpdate()
                    ->cascadeOnDelete();

                $tabela
                    ->foreignId(
                        'genero_id',
                    )
                    ->constrained(
                        table: 'generos',
                    )
                    ->cascadeOnUpdate()
                    ->cascadeOnDelete();

                $tabela->primary(
                    [
                        'banda_id',
                        'genero_id',
                    ],
                    'banda_genero_primaria',
                );

                /*
                 * A chave primária começa por banda_id. Este índice adicional
                 * otimiza a obtenção das bandas associadas a um género.
                 */
                $tabela->index(
                    'genero_id',
                    'banda_genero_genero_indice',
                );
            },
        );
    }

    /**
     * Elimina a tabela intermédia entre bandas e géneros.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    public function down(): void
    {
        Schema::dropIfExists(
            'banda_genero',
        );
    }
};
