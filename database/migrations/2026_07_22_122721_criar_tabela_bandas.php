<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cria a tabela das bandas.
 *
 * Cada banda pertence a uma origem geográfica e pode ser associada a vários
 * géneros musicais.
 *
 * @since 2.0.0
 */
return new class extends Migration
{
    /**
     * Cria a tabela das bandas.
     *
     * @since 2.0.0
     */
    public function up(): void
    {
        Schema::create(
            'bandas',
            static function (Blueprint $tabela): void {
                $tabela->id();

                $tabela->string(
                    'nome',
                    255,
                );

                $tabela
                    ->foreignId(
                        'origem_geografica_id',
                    )
                    ->constrained(
                        table: 'origens_geograficas',
                    )
                    ->cascadeOnUpdate()
                    ->restrictOnDelete();

                $tabela
                    ->foreignId(
                        'criado_por_id',
                    )
                    ->nullable()
                    ->constrained(
                        table: 'utilizadores',
                    )
                    ->cascadeOnUpdate()
                    ->nullOnDelete();

                $tabela
                    ->foreignId(
                        'atualizado_por_id',
                    )
                    ->nullable()
                    ->constrained(
                        table: 'utilizadores',
                    )
                    ->cascadeOnUpdate()
                    ->nullOnDelete();

                $tabela->timestamps();

                $tabela->softDeletes();

                /*
                 * Permite reutilizar o nome de uma banda eliminada
                 * logicamente, mantendo a unicidade entre bandas ativas.
                 */
                $tabela
                    ->string(
                        'nome_ativo',
                        255,
                    )
                    ->nullable()
                    ->virtualAs(
                        'if(`deleted_at` is null, `nome`, null)',
                    );

                $tabela->unique(
                    'nome_ativo',
                    'bandas_nome_ativo_unico',
                );

                $tabela->index(
                    [
                        'origem_geografica_id',
                        'deleted_at',
                        'nome',
                    ],
                    'bandas_origem_estado_nome_indice',
                );
            },
        );
    }

    /**
     * Elimina a tabela das bandas.
     *
     * @since 2.0.0
     */
    public function down(): void
    {
        Schema::dropIfExists(
            'bandas',
        );
    }
};
