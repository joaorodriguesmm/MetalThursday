<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cria a tabela dos artistas musicais.
 *
 * Cada artista pertence a uma origem geográfica e pode ser associado a vários
 * géneros musicais.
 *
 * O nome não constitui a identidade do artista e pode, por isso, ser repetido
 * entre registos distintos.
 *
 * @since 2.0.0
 */
return new class extends Migration
{
    /**
     * Cria a tabela dos artistas.
     *
     * @since 2.0.0
     */
    public function up(): void
    {
        Schema::create(
            'artistas',
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
                 * Suporta a identificação de possíveis duplicados e a
                 * ordenação dos artistas sem transformar o nome numa chave
                 * de identidade.
                 */
                $tabela->index(
                    [
                        'nome',
                        'deleted_at',
                    ],
                    'artistas_nome_estado_indice',
                );

                $tabela->index(
                    [
                        'origem_geografica_id',
                        'deleted_at',
                        'nome',
                    ],
                    'artistas_origem_estado_nome_indice',
                );
            },
        );
    }

    /**
     * Elimina a tabela dos artistas.
     *
     * @since 2.0.0
     */
    public function down(): void
    {
        Schema::dropIfExists(
            'artistas',
        );
    }
};
