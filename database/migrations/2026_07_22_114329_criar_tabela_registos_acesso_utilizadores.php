<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Cria o histórico das alterações de acesso dos utilizadores.
 *
 * @since 2.0.0
 */
return new class extends Migration
{
    /**
     * Cria a tabela e as respetivas restrições de integridade.
     *
     * @since 2.0.0
     */
    public function up(): void
    {
        Schema::create(
            'registos_acesso_utilizadores',
            static function (Blueprint $tabela): void {
                $tabela->id();

                $tabela->foreignId(
                    'utilizador_id',
                );

                $tabela
                    ->string(
                        'acao',
                        10,
                    )
                    ->charset('ascii')
                    ->collation('ascii_bin');

                $tabela
                    ->string(
                        'motivo',
                        1000,
                    )
                    ->nullable();

                $tabela->foreignId(
                    'responsavel_id',
                );

                $tabela->timestamp(
                    'registado_em',
                );

                $tabela->index(
                    [
                        'utilizador_id',
                        'registado_em',
                        'id',
                    ],
                    'registos_acesso_utilizador_data_indice',
                );

                $tabela->index(
                    [
                        'responsavel_id',
                        'registado_em',
                        'id',
                    ],
                    'registos_acesso_responsavel_data_indice',
                );

                $tabela
                    ->foreign(
                        'utilizador_id',
                    )
                    ->references(
                        'id',
                    )
                    ->on(
                        'utilizadores',
                    )
                    ->restrictOnDelete();

                $tabela
                    ->foreign(
                        'responsavel_id',
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
            ALTER TABLE `registos_acesso_utilizadores`
                ADD CONSTRAINT `registos_acesso_acao_valida_verificacao`
                CHECK (
                    `acao` IN (
                        'suspensao',
                        'reativacao'
                    )
                )
            SQL,
        );

        DB::statement(
            <<<'SQL'
            ALTER TABLE `registos_acesso_utilizadores`
                ADD CONSTRAINT `registos_acesso_estado_coerente_verificacao`
                CHECK (
                    (
                        `acao` = 'suspensao'
                        AND `motivo` IS NOT NULL
                        AND `motivo` REGEXP '[^[:space:]]'
                    )
                    OR
                    (
                        `acao` = 'reativacao'
                        AND `motivo` IS NULL
                    )
                )
            SQL,
        );

        DB::statement(
            <<<'SQL'
            ALTER TABLE `registos_acesso_utilizadores`
                ADD CONSTRAINT `registos_acesso_responsavel_distinto_verificacao`
                CHECK (`responsavel_id` <> `utilizador_id`)
            SQL,
        );
    }

    /**
     * Elimina o histórico de alterações de acesso.
     *
     * @since 2.0.0
     */
    public function down(): void
    {
        Schema::dropIfExists(
            'registos_acesso_utilizadores',
        );
    }
};
