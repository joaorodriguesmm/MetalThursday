<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cria as tabelas técnicas das filas do Laravel.
 *
 * Os nomes das tabelas são definidos pelo MetalThursday e utilizam
 * português. Os nomes das colunas permanecem de acordo com os contratos dos
 * repositórios de filas, lotes e trabalhos falhados do Laravel.
 *
 * @since 2.0.0
 */
return new class extends Migration
{
    /**
     * Cria as tabelas de trabalhos, lotes e trabalhos falhados.
     *
     * @since 2.0.0
     */
    public function up(): void
    {
        Schema::create(
            'trabalhos_fila',
            static function (Blueprint $tabela): void {
                $tabela->id();

                $tabela
                    ->string(
                        'queue',
                        255,
                    )
                    ->collation(
                        'utf8mb4_bin',
                    );

                $tabela->longText(
                    'payload',
                );

                $tabela->unsignedSmallInteger(
                    'attempts',
                );

                $tabela
                    ->unsignedInteger(
                        'reserved_at',
                    )
                    ->nullable();

                $tabela->unsignedInteger(
                    'available_at',
                );

                $tabela->unsignedInteger(
                    'created_at',
                );

                $tabela->index(
                    [
                        'queue',
                        'reserved_at',
                        'available_at',
                    ],
                    'trabalhos_fila_estado_disponibilidade_indice',
                );
            },
        );

        Schema::create(
            'lotes_trabalhos_fila',
            static function (Blueprint $tabela): void {
                $tabela
                    ->char(
                        'id',
                        36,
                    )
                    ->charset(
                        'ascii',
                    )
                    ->collation(
                        'ascii_bin',
                    )
                    ->primary();

                $tabela->string(
                    'name',
                    255,
                );

                $tabela->unsignedInteger(
                    'total_jobs',
                );

                $tabela->unsignedInteger(
                    'pending_jobs',
                );

                $tabela->unsignedInteger(
                    'failed_jobs',
                );

                $tabela->longText(
                    'failed_job_ids',
                );

                $tabela
                    ->mediumText(
                        'options',
                    )
                    ->nullable();

                $tabela
                    ->unsignedInteger(
                        'cancelled_at',
                    )
                    ->nullable();

                $tabela->unsignedInteger(
                    'created_at',
                );

                $tabela
                    ->unsignedInteger(
                        'finished_at',
                    )
                    ->nullable();

                $tabela->index(
                    [
                        'finished_at',
                        'created_at',
                    ],
                    'lotes_trabalhos_fila_estado_data_indice',
                );

                $tabela->index(
                    [
                        'created_at',
                        'cancelled_at',
                    ],
                    'lotes_trabalhos_fila_criacao_cancelamento_indice',
                );
            },
        );

        Schema::create(
            'trabalhos_fila_falhados',
            static function (Blueprint $tabela): void {
                $tabela->id();

                $tabela
                    ->char(
                        'uuid',
                        36,
                    )
                    ->charset(
                        'ascii',
                    )
                    ->collation(
                        'ascii_bin',
                    )
                    ->unique();

                $tabela
                    ->string(
                        'connection',
                        255,
                    )
                    ->collation(
                        'utf8mb4_bin',
                    );

                $tabela
                    ->string(
                        'queue',
                        255,
                    )
                    ->collation(
                        'utf8mb4_bin',
                    );

                $tabela->longText(
                    'payload',
                );

                $tabela->longText(
                    'exception',
                );

                $tabela
                    ->timestamp(
                        'failed_at',
                    )
                    ->useCurrent();

                $tabela->index(
                    [
                        'connection',
                        'queue',
                    ],
                    'trabalhos_fila_falhados_ligacao_fila_indice',
                );

                $tabela->index(
                    'queue',
                    'trabalhos_fila_falhados_fila_indice',
                );

                $tabela->index(
                    'failed_at',
                    'trabalhos_fila_falhados_data_indice',
                );
            },
        );
    }

    /**
     * Elimina as tabelas das filas.
     *
     * @since 2.0.0
     */
    public function down(): void
    {
        Schema::dropIfExists(
            'trabalhos_fila_falhados',
        );

        Schema::dropIfExists(
            'lotes_trabalhos_fila',
        );

        Schema::dropIfExists(
            'trabalhos_fila',
        );
    }
};
