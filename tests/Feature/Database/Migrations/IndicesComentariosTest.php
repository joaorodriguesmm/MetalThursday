<?php

declare(strict_types=1);

namespace Tests\Feature\Database\Migrations;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use LogicException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa os índices utilizados pela árvore de comentários.
 *
 * @since 2.0.0
 */
final class IndicesComentariosTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Confirma que a tabela possui os índices compostos correspondentes às
     * consultas dos comentários principais e das respostas.
     *
     * Os índices genéricos anteriores devem deixar de existir, porque os
     * índices especializados conservam os mesmos prefixos.
     *
     * @since 2.0.0
     */
    #[Test]
    public function possui_indices_para_comentarios_principais_e_respostas(): void
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
                      AND TABLE_NAME = 'comentarios'
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
                'tipo_comentavel',
                'comentavel_id',
                'comentario_pai_id',
                'deleted_at',
                'created_at',
                'id',
            ],
            $indices['comentarios_comentavel_principal_ordem_indice']
                ?? null,
        );

        self::assertSame(
            [
                'comentario_pai_id',
                'deleted_at',
                'created_at',
                'id',
            ],
            $indices['comentarios_respostas_ordem_indice']
                ?? null,
        );

        self::assertArrayNotHasKey(
            'comentarios_comentavel_indice',
            $indices,
        );

        self::assertArrayNotHasKey(
            'comentarios_pai_data_indice',
            $indices,
        );
    }
}
