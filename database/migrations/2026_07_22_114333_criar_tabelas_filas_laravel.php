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
 *
 * @version 2.0.0
 */
return new class extends Migration
{
    /**
     * Cria as tabelas de trabalhos, lotes e trabalhos falhados.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
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
                    ->index();

                $tabela->longText(
                    'payload',
                );

                $tabela->unsignedTinyInteger(
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
            },
        );

        Schema::create(
            'lotes_trabalhos_fila',
            static function (Blueprint $tabela): void {
                $tabela
                    ->string(
                        'id',
                        255,
                    )
                    ->primary();

                $tabela->string(
                    'name',
                    255,
                );

                $tabela->integer(
                    'total_jobs',
                );

                $tabela->integer(
                    'pending_jobs',
                );

                $tabela->integer(
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
                    ->integer(
                        'cancelled_at',
                    )
                    ->nullable();

                $tabela->integer(
                    'created_at',
                );

                $tabela
                    ->integer(
                        'finished_at',
                    )
                    ->nullable();
            },
        );

        Schema::create(
            'trabalhos_fila_falhados',
            static function (Blueprint $tabela): void {
                $tabela->id();

                $tabela
                    ->string(
                        'uuid',
                        255,
                    )
                    ->unique();

                $tabela->text(
                    'connection',
                );

                $tabela->text(
                    'queue',
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
            },
        );
    }

    /**
     * Elimina as tabelas das filas.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
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
