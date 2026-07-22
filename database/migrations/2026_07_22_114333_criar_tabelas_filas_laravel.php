<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cria as tabelas técnicas das filas do Laravel.
 *
 * @return Migration Migração das tabelas de filas.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
return new class extends Migration
{
    /**
     * Cria as tabelas de trabalhos, lotes e falhas.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function up(): void
    {
        Schema::create(
            'jobs',
            function (Blueprint $tabela): void {
                $tabela->id();

                $tabela
                    ->string('queue')
                    ->index();

                $tabela->longText('payload');
                $tabela->unsignedTinyInteger('attempts');

                $tabela
                    ->unsignedInteger('reserved_at')
                    ->nullable();

                $tabela->unsignedInteger('available_at');
                $tabela->unsignedInteger('created_at');
            },
        );

        Schema::create(
            'job_batches',
            function (Blueprint $tabela): void {
                $tabela
                    ->string('id')
                    ->primary();

                $tabela->string('name');
                $tabela->integer('total_jobs');
                $tabela->integer('pending_jobs');
                $tabela->integer('failed_jobs');
                $tabela->longText('failed_job_ids');

                $tabela
                    ->mediumText('options')
                    ->nullable();

                $tabela
                    ->integer('cancelled_at')
                    ->nullable();

                $tabela->integer('created_at');

                $tabela
                    ->integer('finished_at')
                    ->nullable();
            },
        );

        Schema::create(
            'failed_jobs',
            function (Blueprint $tabela): void {
                $tabela->id();

                $tabela
                    ->string('uuid')
                    ->unique();

                $tabela->text('connection');
                $tabela->text('queue');
                $tabela->longText('payload');
                $tabela->longText('exception');

                $tabela
                    ->timestamp('failed_at')
                    ->useCurrent();
            },
        );
    }

    /**
     * Elimina as tabelas das filas.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function down(): void
    {
        Schema::dropIfExists('failed_jobs');
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('jobs');
    }
};
