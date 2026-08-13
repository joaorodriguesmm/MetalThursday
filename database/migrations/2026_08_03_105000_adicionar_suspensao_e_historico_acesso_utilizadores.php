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
 */
return new class extends Migration
{
    /**
     * Nome da restrição do estado atual de suspensão.
     *
     * @since 2.0.0
     */
    private const RESTRICAO_ESTADO_SUSPENSAO =
        'utilizadores_suspensao_coerente_verificacao';

    /**
     * Adiciona o estado atual e cria o histórico de acesso.
     *
     * A distinção entre o utilizador afetado e o responsável é garantida pelo
     * serviço transacional. O MariaDB não permite referenciar a coluna `id`,
     * por ser AUTO_INCREMENT, numa restrição CHECK da própria tabela.
     *
     * @since 2.0.0
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
                    );

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
     * Remove o histórico e o estado atual de acesso.
     *
     * @since 2.0.0
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

                $tabela->dropIndex(
                    'utilizadores_suspenso_por_indice',
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
