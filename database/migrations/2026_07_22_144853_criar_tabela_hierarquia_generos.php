<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cria a tabela intermédia da hierarquia dos géneros musicais.
 *
 * Um género pode possuir vários géneros ascendentes e cada género
 * ascendente pode estar relacionado com vários géneros descendentes.
 *
 * @return Migration Migração da hierarquia dos géneros.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
return new class extends Migration
{
    /**
     * Cria a tabela intermédia da hierarquia dos géneros.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function up(): void
    {
        Schema::create(
            'hierarquia_generos',
            function (Blueprint $tabela): void {
                $tabela
                    ->foreignId('genero_id')
                    ->constrained('generos')
                    ->cascadeOnDelete();

                $tabela
                    ->foreignId('genero_pai_id')
                    ->constrained(
                        table: 'generos',
                        indexName: 'hierarquia_generos_genero_pai_id_fk',
                    )
                    ->cascadeOnDelete();

                $tabela->primary(
                    [
                        'genero_id',
                        'genero_pai_id',
                    ],
                    'hierarquia_generos_pk',
                );
            },
        );
    }

    /**
     * Elimina a tabela intermédia da hierarquia dos géneros.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function down(): void
    {
        Schema::dropIfExists(
            'hierarquia_generos',
        );
    }
};
