<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cria as tabelas técnicas de autenticação e sessões do Laravel.
 *
 * Os nomes das tabelas são definidos pelo MetalThursday e utilizam
 * português. Os nomes das colunas permanecem de acordo com os contratos dos
 * repositórios de tokens e sessões do Laravel.
 *
 * @since 2.0.0
 *
 * @version 2.0.0
 */
return new class extends Migration
{
    /**
     * Cria as tabelas de recuperação da palavra-passe e de sessões.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    public function up(): void
    {
        Schema::create(
            'tokens_redefinicao_palavra_passe',
            static function (Blueprint $tabela): void {
                $tabela
                    ->string(
                        'email',
                        255,
                    )
                    ->primary();

                $tabela->string(
                    'token',
                    255,
                );

                $tabela
                    ->timestamp(
                        'created_at',
                    )
                    ->nullable();
            },
        );

        Schema::create(
            'sessoes',
            static function (Blueprint $tabela): void {
                $tabela
                    ->string(
                        'id',
                        255,
                    )
                    ->primary();

                $tabela
                    ->foreignId(
                        'user_id',
                    )
                    ->nullable()
                    ->constrained(
                        table: 'utilizadores',
                    )
                    ->cascadeOnUpdate()
                    ->cascadeOnDelete();

                $tabela
                    ->string(
                        'ip_address',
                        45,
                    )
                    ->nullable();

                $tabela
                    ->text(
                        'user_agent',
                    )
                    ->nullable();

                $tabela->longText(
                    'payload',
                );

                $tabela
                    ->integer(
                        'last_activity',
                    )
                    ->index();
            },
        );
    }

    /**
     * Elimina as tabelas de autenticação e sessões.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    public function down(): void
    {
        Schema::dropIfExists(
            'sessoes',
        );

        Schema::dropIfExists(
            'tokens_redefinicao_palavra_passe',
        );
    }
};
