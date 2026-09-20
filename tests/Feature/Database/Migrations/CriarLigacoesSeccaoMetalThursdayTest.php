<?php

declare(strict_types=1);

namespace Tests\Feature\Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa a criação da estrutura de ligações das secções.
 *
 * @since 2.0.0
 */
final class CriarLigacoesSeccaoMetalThursdayTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Confirma a criação e reversão da tabela de ligações.
     *
     * @since 2.0.0
     */
    #[Test]
    public function cria_e_reverte_estrutura_de_ligacoes(): void
    {
        self::assertTrue(
            Schema::hasColumns(
                'ligacoes_seccao_metal_thursday',
                [
                    'id',
                    'seccao_metal_thursday_id',
                    'plataforma',
                    'etiqueta',
                    'url',
                    'incorporar',
                    'ordem',
                    'created_at',
                    'updated_at',
                ],
            ),
        );

        $restricoes = $this->obterRestricoesCheck();

        self::assertContains(
            'ligacoes_seccao_metal_thursday_plataforma_valida',
            $restricoes,
        );
        self::assertContains(
            'ligacoes_seccao_metal_thursday_ordem_valida',
            $restricoes,
        );

        $migracao = require database_path(
            'migrations/2026_09_16_090000_criar_ligacoes_seccao_metal_thursday.php',
        );

        self::assertInstanceOf(
            Migration::class,
            $migracao,
        );

        $migracao->down();

        self::assertFalse(
            Schema::hasTable(
                'ligacoes_seccao_metal_thursday',
            ),
        );

        $migracao->up();

        self::assertTrue(
            Schema::hasColumns(
                'ligacoes_seccao_metal_thursday',
                [
                    'id',
                    'seccao_metal_thursday_id',
                    'plataforma',
                    'etiqueta',
                    'url',
                    'incorporar',
                    'ordem',
                    'created_at',
                    'updated_at',
                ],
            ),
        );

        $restricoes = $this->obterRestricoesCheck();

        self::assertContains(
            'ligacoes_seccao_metal_thursday_plataforma_valida',
            $restricoes,
        );
        self::assertContains(
            'ligacoes_seccao_metal_thursday_ordem_valida',
            $restricoes,
        );
    }

    /**
     * Obtém os nomes das restrições CHECK da tabela de ligações.
     *
     * @return list<string> Nomes das restrições.
     *
     * @since 2.0.0
     */
    private function obterRestricoesCheck(): array
    {
        $restricoes = DB::select(
            <<<'SQL'
            SELECT `CONSTRAINT_NAME` AS `nome`
            FROM `information_schema`.`TABLE_CONSTRAINTS`
            WHERE `CONSTRAINT_SCHEMA` = DATABASE()
                AND `TABLE_NAME` = 'ligacoes_seccao_metal_thursday'
                AND `CONSTRAINT_TYPE` = 'CHECK'
            SQL,
        );

        return array_map(
            static fn (object $restricao): string => (string) $restricao->nome,
            $restricoes,
        );
    }
}
