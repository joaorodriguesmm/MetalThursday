<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adiciona a autoria administrativa das revogações dos convites.
 *
 * Uma revogação passa a conservar obrigatoriamente o superadministrador
 * responsável. A data e o responsável são sempre ambos nulos ou ambos
 * preenchidos.
 *
 * A migration recusa dados históricos sem autoria em vez de lhes atribuir
 * retroativamente um responsável desconhecido ou incorreto.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
return new class extends Migration
{
    /**
     * Nome da restrição de coerência da revogação.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const NOME_RESTRICAO =
        'convites_revogacao_responsavel_coerente_verificacao';

    /**
     * Adiciona o responsável e a restrição de coerência.
     *
     * @throws LogicException Quando existem revogações anteriores sem
     *                        responsável identificável.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function up(): void
    {
        $possuiRevogacoesSemResponsavel =
            DB::table(
                'convites',
            )
                ->whereNotNull(
                    'revogado_em',
                )
                ->exists();

        if ($possuiRevogacoesSemResponsavel) {
            throw new LogicException(
                'Existem convites revogados sem um responsável identificável.',
            );
        }

        Schema::table(
            'convites',
            static function (Blueprint $tabela): void {
                /*
                 * A eliminação física do responsável é impedida para preservar
                 * permanentemente a autoria da revogação.
                 */
                $tabela
                    ->foreignId(
                        'revogado_por_id',
                    )
                    ->nullable()
                    ->after(
                        'revogado_em',
                    )
                    ->constrained(
                        table: 'utilizadores',
                    )
                    ->cascadeOnUpdate()
                    ->restrictOnDelete();
            },
        );

        DB::statement(
            <<<'SQL'
                ALTER TABLE `convites`
                ADD CONSTRAINT `convites_revogacao_responsavel_coerente_verificacao`
                CHECK (
                    (
                        `revogado_em` IS NULL
                        AND `revogado_por_id` IS NULL
                    )
                    OR
                    (
                        `revogado_em` IS NOT NULL
                        AND `revogado_por_id` IS NOT NULL
                    )
                )
                SQL,
        );
    }

    /**
     * Remove a autoria administrativa das revogações.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function down(): void
    {
        DB::statement(
            sprintf(
                'ALTER TABLE `convites` DROP CONSTRAINT `%s`',
                self::NOME_RESTRICAO,
            ),
        );

        Schema::table(
            'convites',
            static function (Blueprint $tabela): void {
                $tabela->dropConstrainedForeignId(
                    'revogado_por_id',
                );
            },
        );
    }
};
