<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adiciona o estado atual e o histórico de acesso dos utilizadores.
 *
 * A suspensão não elimina o utilizador nem os seus conteúdos. O estado atual
 * permanece na tabela `utilizadores`, enquanto cada suspensão e reativação é
 * preservada num registo histórico independente.
 *
 * @since 2.0.0
 *
 * @version 1.0.1
 */
return new class extends Migration
{
    /**
     * Nome da restrição do estado atual de suspensão.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const RESTRICAO_ESTADO_SUSPENSAO =
        'utilizadores_suspensao_coerente_verificacao';

    /**
     * Adiciona o estado atual e cria o histórico de acesso.
     *
     * A distinção entre o utilizador afetado e o responsável é garantida pelo
     * serviço transacional. O MariaDB 10.4 não permite referenciar a coluna
     * `id`, por ser AUTO_INCREMENT, numa restrição CHECK da própria tabela.
     *
     * @since 2.0.0
     *
     * @version 1.0.1
     */
    public function up(): void
    {
        Schema::table(
            'utilizadores',
            static function (Blueprint $tabela): void {
                $tabela
                    ->timestamp(
                        'suspenso_em',
                    )
                    ->nullable()
                    ->after(
                        'papel',
                    );

                $tabela
                    ->string(
                        'motivo_suspensao',
                        1000,
                    )
                    ->nullable()
                    ->after(
                        'suspenso_em',
                    );

                $tabela
                    ->foreignId(
                        'suspenso_por_id',
                    )
                    ->nullable()
                    ->after(
                        'motivo_suspensao',
                    )
                    ->constrained(
                        'utilizadores',
                    )
                    ->restrictOnDelete();

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
            },
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
                        AND CHAR_LENGTH(TRIM(`motivo_suspensao`)) > 0
                        AND `suspenso_por_id` IS NOT NULL
                    )
                )
                SQL,
        );

        Schema::create(
            'registos_acesso_utilizadores',
            static function (Blueprint $tabela): void {
                $tabela->id();

                $tabela
                    ->foreignId(
                        'utilizador_id',
                    )
                    ->constrained(
                        'utilizadores',
                    )
                    ->restrictOnDelete();

                $tabela->string(
                    'acao',
                    16,
                );

                $tabela
                    ->string(
                        'motivo',
                        1000,
                    )
                    ->nullable();

                $tabela
                    ->foreignId(
                        'responsavel_id',
                    )
                    ->constrained(
                        'utilizadores',
                    )
                    ->restrictOnDelete();

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

                $tabela->index(
                    [
                        'acao',
                        'registado_em',
                        'id',
                    ],
                    'registos_acesso_acao_data_indice',
                );
            },
        );

        DB::statement(
            <<<'SQL'
                ALTER TABLE `registos_acesso_utilizadores`
                ADD CONSTRAINT `registos_acesso_acao_valida_verificacao`
                CHECK (
                    BINARY `acao` IN (
                        BINARY 'suspensao',
                        BINARY 'reativacao'
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
                        BINARY `acao` = BINARY 'suspensao'
                        AND `motivo` IS NOT NULL
                        AND CHAR_LENGTH(TRIM(`motivo`)) > 0
                    )
                    OR
                    (
                        BINARY `acao` = BINARY 'reativacao'
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
     * Remove o histórico e o estado atual de acesso.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function down(): void
    {
        Schema::dropIfExists(
            'registos_acesso_utilizadores',
        );

        DB::statement(
            sprintf(
                'ALTER TABLE `utilizadores` DROP CONSTRAINT `%s`',
                self::RESTRICAO_ESTADO_SUSPENSAO,
            ),
        );

        Schema::table(
            'utilizadores',
            static function (Blueprint $tabela): void {
                $tabela->dropIndex(
                    'utilizadores_papel_suspensao_indice',
                );

                $tabela->dropIndex(
                    'utilizadores_suspensao_indice',
                );

                $tabela->dropForeign(
                    [
                        'suspenso_por_id',
                    ],
                );

                $tabela->dropColumn([
                    'suspenso_em',
                    'motivo_suspensao',
                    'suspenso_por_id',
                ]);
            },
        );
    }
};
