<?php

declare(strict_types=1);

namespace Tests\Feature\Database\Migrations;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use LogicException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa os índices utilizados pelas consultas das notificações.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
final class IndicesNotificacoesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Confirma que a tabela possui os índices compostos especializados.
     *
     * O índice genérico anterior deve deixar de existir, porque os dois novos
     * índices já possuem o mesmo prefixo e cobrem as consultas concretas da
     * aplicação.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function possui_indices_para_leitura_e_ordenacao(): void
    {
        $linhas =
            DB::select(
                <<<'SQL'
                    SELECT
                        INDEX_NAME AS nome_indice,
                        SEQ_IN_INDEX AS ordem_coluna,
                        COLUMN_NAME AS nome_coluna
                    FROM information_schema.STATISTICS
                    WHERE TABLE_SCHEMA = DATABASE()
                      AND TABLE_NAME = 'notificacoes'
                    ORDER BY INDEX_NAME, SEQ_IN_INDEX
                    SQL,
            );

        /**
         * @var array<string, array<int, string>> $indices
         */
        $indices = [];

        foreach ($linhas as $linha) {
            $dadosLinha =
                (array) $linha;

            $nomeIndice =
                $dadosLinha['nome_indice']
                ?? null;

            $ordemColuna =
                $dadosLinha['ordem_coluna']
                ?? null;

            $nomeColuna =
                $dadosLinha['nome_coluna']
                ?? null;

            if (
                ! is_string($nomeIndice)
                || ! is_numeric($ordemColuna)
                || ! is_string($nomeColuna)
            ) {
                throw new LogicException(
                    'A base de dados devolveu metadados de índices num formato inesperado.',
                );
            }

            $indices[$nomeIndice][(int) $ordemColuna] =
                $nomeColuna;
        }

        foreach ($indices as &$colunas) {
            ksort(
                $colunas,
            );

            $colunas =
                array_values(
                    $colunas,
                );
        }

        unset(
            $colunas,
        );

        self::assertSame(
            [
                'notifiable_type',
                'notifiable_id',
                'read_at',
            ],
            $indices['notificacoes_destinatario_leitura_indice']
                ?? null,
        );

        self::assertSame(
            [
                'notifiable_type',
                'notifiable_id',
                'created_at',
                'id',
            ],
            $indices['notificacoes_destinatario_criacao_indice']
                ?? null,
        );

        self::assertArrayNotHasKey(
            'notificacoes_notificavel_indice',
            $indices,
        );
    }
}
