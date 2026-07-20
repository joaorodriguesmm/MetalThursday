<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Retorna a migração responsável pela criação das tabelas base utilizadas
 * pelo Laravel.
 *
 * Esta migração contém as tabelas de utilizadores, recuperação de
 * palavra-passe, sessões, cache e filas.
 *
 * Os nomes das tabelas e colunas permanecem em inglês por fazerem parte dos
 * contratos e convenções dos componentes internos do Laravel.
 *
 * @return Migration - Migração das tabelas base do Laravel.
 *
 * @since 1.0.0
 *
 * @version 2.0.0
 */
return new class extends Migration
{
    /**
     * Cria as tabelas base utilizadas pelo Laravel.
     *
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function up(): void
    {
        Schema::create(
            'users',
            function (Blueprint $tabela): void {
                $tabela->id();
                $tabela->string('name');

                $tabela
                    ->string('email')
                    ->nullable()
                    ->unique();

                $tabela
                    ->timestamp('email_verified_at')
                    ->nullable();

                $tabela
                    ->string('password')
                    ->nullable();

                $tabela
                    ->string('photo')
                    ->nullable();

                $tabela
                    ->string('invite_code')
                    ->unique();

                $tabela->rememberToken();
                $tabela->timestamps();
            },
        );

        Schema::create(
            'password_reset_tokens',
            function (Blueprint $tabela): void {
                $tabela
                    ->string('email')
                    ->primary();

                $tabela->string('token');

                $tabela
                    ->timestamp('created_at')
                    ->nullable();
            },
        );

        Schema::create(
            'sessions',
            function (Blueprint $tabela): void {
                $tabela
                    ->string('id')
                    ->primary();

                $tabela
                    ->foreignId('user_id')
                    ->nullable()
                    ->index();

                $tabela
                    ->string('ip_address', 45)
                    ->nullable();

                $tabela
                    ->text('user_agent')
                    ->nullable();

                $tabela->longText('payload');

                $tabela
                    ->integer('last_activity')
                    ->index();
            },
        );

        Schema::create(
            'cache',
            function (Blueprint $tabela): void {
                $tabela
                    ->string('key')
                    ->primary();

                $tabela->mediumText('value');
                $tabela->integer('expiration');
            },
        );

        Schema::create(
            'cache_locks',
            function (Blueprint $tabela): void {
                $tabela
                    ->string('key')
                    ->primary();

                $tabela->string('owner');
                $tabela->integer('expiration');
            },
        );

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
     * Elimina as tabelas base utilizadas pelo Laravel.
     *
     * As tabelas são eliminadas pela ordem inversa à respetiva criação.
     *
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function down(): void
    {
        Schema::dropIfExists('failed_jobs');
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('cache_locks');
        Schema::dropIfExists('cache');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
