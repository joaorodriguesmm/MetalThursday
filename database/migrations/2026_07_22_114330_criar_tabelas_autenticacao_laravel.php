<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cria as tabelas técnicas de autenticação e sessões do Laravel.
 *
 * @return Migration Migração das tabelas técnicas de autenticação.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
return new class extends Migration
{
    /**
     * Cria as tabelas de recuperação de palavra-passe e sessões.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function up(): void
    {
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

                /*
                 * O gestor de sessões do Laravel utiliza este nome.
                 */
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
    }

    /**
     * Elimina as tabelas de autenticação e sessões.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
    }
};
