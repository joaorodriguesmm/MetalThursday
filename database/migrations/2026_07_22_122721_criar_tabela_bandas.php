<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cria a tabela das bandas.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
return new class extends Migration
{
    /**
     * Cria a tabela das bandas.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function up(): void
    {
        Schema::create(
            'bandas',
            function (Blueprint $tabela): void {
                $tabela->id();

                $tabela
                    ->string('nome')
                    ->index();

                $tabela
                    ->foreignId('pais_id')
                    ->constrained('paises')
                    ->restrictOnDelete();

                $tabela
                    ->foreignId('criado_por_id')
                    ->nullable()
                    ->constrained('utilizadores')
                    ->nullOnDelete();

                $tabela
                    ->foreignId('atualizado_por_id')
                    ->nullable()
                    ->constrained('utilizadores')
                    ->nullOnDelete();

                $tabela->timestamps();
                $tabela->softDeletes();

                $tabela->index(
                    [
                        'pais_id',
                        'nome',
                    ],
                    'bandas_pais_nome_indice',
                );
            },
        );
    }

    /**
     * Elimina a tabela das bandas.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function down(): void
    {
        Schema::dropIfExists('bandas');
    }
};
