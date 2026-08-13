<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Restringe os papéis dos utilizadores aos contratos da aplicação.
 *
 * A coluna permanece textual porque os papéis constituem um conjunto fechado
 * definido pela aplicação. A restrição impede valores desconhecidos, variações
 * de maiúsculas e espaços adicionais, mantendo a enumeração PHP e a base de
 * dados alinhadas.
 *
 * @since 2.0.0
 */
return new class extends Migration
{
    /**
     * Nome da restrição aplicada à coluna dos papéis.
     *
     * @since 2.0.0
     */
    private const NOME_RESTRICAO =
        'utilizadores_papel_valido_verificacao';

    /**
     * Valores persistíveis na coluna dos papéis.
     *
     * A migration mantém os valores de forma autónoma para que a sua execução
     * futura não dependa de alterações posteriores à enumeração da aplicação.
     *
     * @var list<string>
     *
     * @since 2.0.0
     */
    private const PAPEIS_PERMITIDOS = [
        'utilizador',
        'administrador',
        'super_administrador',
    ];

    /**
     * Adiciona a restrição dos papéis dos utilizadores.
     *
     * A comparação binária torna significativas as diferenças de maiúsculas,
     * minúsculas e espaços, independentemente da collation da coluna.
     *
     * @throws LogicException Quando existem papéis incompatíveis nos dados
     *                        atuais.
     *
     * @since 2.0.0
     */
    public function up(): void
    {
        $possuiPapeisInvalidos = DB::table(
            'utilizadores',
        )
            ->whereRaw(
                'BINARY `papel` NOT IN (?, ?, ?)',
                self::PAPEIS_PERMITIDOS,
            )
            ->exists();

        if ($possuiPapeisInvalidos) {
            throw new LogicException(
                'Existem utilizadores com papéis incompatíveis com o contrato atual.',
            );
        }

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
    }

    /**
     * Remove a restrição dos papéis dos utilizadores.
     *
     * @since 2.0.0
     */
    public function down(): void
    {
        DB::statement(
            sprintf(
                'ALTER TABLE `utilizadores` DROP CONSTRAINT `%s`',
                self::NOME_RESTRICAO,
            ),
        );
    }
};
