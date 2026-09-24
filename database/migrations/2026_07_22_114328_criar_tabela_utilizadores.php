<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Cria a tabela dos utilizadores da aplicação.
 *
 * Os campos `email`, `email_verified_at`, `password` e `remember_token`
 * permanecem em inglês por integrarem os contratos de autenticação,
 * verificação de e-mail e recuperação da palavra-passe do Laravel.
 *
 * @since 2.0.0
 */
return new class extends Migration
{
    /**
     * Cria a tabela dos utilizadores no estado funcional completo.
     *
     * @since 2.0.0
     */
    public function up(): void
    {
        Schema::create(
            'utilizadores',
            static function (Blueprint $tabela): void {
                $tabela->id();

                $tabela->string(
                    'nome',
                    255,
                );

                $tabela
                    ->string(
                        'email',
                        255,
                    )
                    ->unique();

                $tabela
                    ->timestamp(
                        'email_verified_at',
                    )
                    ->nullable();

                $tabela->string(
                    'password',
                    255,
                );

                $tabela
                    ->string(
                        'fotografia',
                        255,
                    )
                    ->nullable();

                $tabela
                    ->string(
                        'papel',
                        19,
                    )
                    ->default(
                        'utilizador',
                    );

                $tabela
                    ->timestamp(
                        'suspenso_em',
                    )
                    ->nullable();

                $tabela
                    ->string(
                        'motivo_suspensao',
                        1000,
                    )
                    ->nullable();

                $tabela
                    ->foreignId(
                        'suspenso_por_id',
                    )
                    ->nullable();

                $tabela
                    ->boolean(
                        'disponivel_para_nomeacao',
                    )
                    ->default(
                        true,
                    );

                $tabela->rememberToken();

                $tabela->timestamps();

                $tabela->index(
                    [
                        'suspenso_em',
                        'id',
                    ],
                    'utilizadores_suspensao_indice',
                );

                $tabela->index(
                    [
                        'papel',
                        'suspenso_em',
                        'id',
                    ],
                    'utilizadores_papel_suspensao_indice',
                );

                $tabela->index(
                    'suspenso_por_id',
                    'utilizadores_suspenso_por_indice',
                );

                $tabela
                    ->foreign(
                        'suspenso_por_id',
                    )
                    ->references(
                        'id',
                    )
                    ->on(
                        'utilizadores',
                    )
                    ->restrictOnDelete();
            },
        );

        DB::statement(
            <<<'SQL'
            ALTER TABLE `utilizadores`
                ADD CONSTRAINT `utilizadores_papel_valido_verificacao`
                CHECK (
                    BINARY `papel` IN (
                        BINARY 'utilizador',
                        BINARY 'administrador',
                        BINARY 'super_administrador'
                    )
                )
            SQL,
        );

        DB::statement(
            <<<'SQL'
            ALTER TABLE `utilizadores`
                ADD CONSTRAINT `utilizadores_suspensao_coerente_verificacao`
                CHECK (
                    (
                        `suspenso_em` IS NULL
                        AND `motivo_suspensao` IS NULL
                        AND `suspenso_por_id` IS NULL
                    )
                    OR
                    (
                        `suspenso_em` IS NOT NULL
                        AND `motivo_suspensao` IS NOT NULL
                        AND `motivo_suspensao` REGEXP '[^[:space:]]'
                        AND `suspenso_por_id` IS NOT NULL
                    )
                )
            SQL,
        );
    }

    /**
     * Elimina a tabela dos utilizadores.
     *
     * @since 2.0.0
     */
    public function down(): void
    {
        Schema::dropIfExists(
            'utilizadores',
        );
    }
};
