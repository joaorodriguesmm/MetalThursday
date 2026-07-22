<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cria a tabela intermédia entre bandas e géneros musicais.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
return new class extends Migration
{
    /**
     * Cria a tabela intermédia entre bandas e géneros.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function up(): void
    {
        Schema::create(
            'banda_genero',
            function (Blueprint $tabela): void {
                $tabela
                    ->foreignId('banda_id')
                    ->constrained('bandas')
                    ->cascadeOnDelete();

                $tabela
                    ->foreignId('genero_id')
                    ->constrained('generos')
                    ->cascadeOnDelete();

                $tabela->primary(
                    [
                        'banda_id',
                        'genero_id',
                    ],
                    'banda_genero_pk',
                );

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
     * @version 1.0.0
     */
    public function down(): void
    {
        Schema::dropIfExists('banda_genero');
    }
};
