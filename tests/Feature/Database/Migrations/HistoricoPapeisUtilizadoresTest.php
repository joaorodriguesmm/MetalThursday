<?php

declare(strict_types=1);

namespace Tests\Feature\Database\Migrations;

use App\Enumeracoes\PapelUtilizador;
use App\Models\Autenticacao\Utilizador;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa a integridade do histórico dos papéis dos utilizadores.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
final class HistoricoPapeisUtilizadoresTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Restrições esperadas na tabela do histórico.
     *
     * @var list<string>
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const RESTRICOES = [
        'registos_papel_papeis_validos_verificacao',
        'registos_papel_papeis_distintos_verificacao',
        'registos_papel_responsavel_distinto_verificacao',
    ];

    /**
     * Confirma a estrutura e as restrições do histórico.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function possui_estrutura_e_restricoes_de_integridade(): void
    {
        self::assertTrue(
            Schema::hasColumns(
                'registos_papel_utilizadores',
                [
                    'id',
                    'utilizador_id',
                    'papel_anterior',
                    'papel_novo',
                    'responsavel_id',
                    'registado_em',
                ],
            ),
        );

        $restricoes = collect(
            DB::select(
                <<<'SQL'
                    SELECT CONSTRAINT_NAME AS nome
                    FROM information_schema.TABLE_CONSTRAINTS
                    WHERE CONSTRAINT_SCHEMA = DATABASE()
                      AND TABLE_NAME = 'registos_papel_utilizadores'
                    SQL,
            ),
        )
            ->pluck(
                'nome',
            )
            ->all();

        foreach (self::RESTRICOES as $restricao) {
            self::assertContains(
                $restricao,
                $restricoes,
            );
        }

        $chavesEstrangeiras = collect(
            DB::select(
                <<<'SQL'
                    SELECT
                        COLUMN_NAME AS coluna,
                        REFERENCED_TABLE_NAME AS tabela_referenciada
                    FROM information_schema.KEY_COLUMN_USAGE
                    WHERE CONSTRAINT_SCHEMA = DATABASE()
                      AND TABLE_NAME = 'registos_papel_utilizadores'
                      AND REFERENCED_TABLE_NAME IS NOT NULL
                    SQL,
            ),
        )
            ->mapWithKeys(
                static fn (
                    object $chave,
                ): array => [
                    $chave->coluna => $chave->tabela_referenciada,
                ],
            )
            ->all();

        self::assertSame(
            'utilizadores',
            $chavesEstrangeiras['utilizador_id']
                ?? null,
        );

        self::assertSame(
            'utilizadores',
            $chavesEstrangeiras['responsavel_id']
                ?? null,
        );
    }

    /**
     * Confirma que uma alteração coerente pode ser persistida.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function permite_um_registo_historico_valido(): void
    {
        $utilizador =
            Utilizador::factory()
                ->comPapel(
                    PapelUtilizador::Administrador,
                )
                ->create();

        $responsavel =
            Utilizador::factory()
                ->comPapel(
                    PapelUtilizador::SuperAdministrador,
                )
                ->create();

        DB::table(
            'registos_papel_utilizadores',
        )->insert([
            'utilizador_id' => $utilizador->getKey(),
            'papel_anterior' => PapelUtilizador::Utilizador->value,
            'papel_novo' => PapelUtilizador::Administrador->value,
            'responsavel_id' => $responsavel->getKey(),
            'registado_em' => now(),
        ]);

        $this->assertDatabaseHas(
            'registos_papel_utilizadores',
            [
                'utilizador_id' => $utilizador->getKey(),
                'papel_anterior' => PapelUtilizador::Utilizador->value,
                'papel_novo' => PapelUtilizador::Administrador->value,
                'responsavel_id' => $responsavel->getKey(),
            ],
        );
    }

    /**
     * Confirma que os papéis fora do contrato exato são rejeitados.
     *
     * @param  string  $campo  Campo alterado.
     * @param  string  $valor  Valor inválido.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    #[DataProvider('fornecerPapeisInvalidos')]
    public function rejeita_papeis_invalidos(
        string $campo,
        string $valor,
    ): void {
        $utilizador =
            Utilizador::factory()
                ->create();

        $responsavel =
            Utilizador::factory()
                ->comPapel(
                    PapelUtilizador::SuperAdministrador,
                )
                ->create();

        $dados = [
            'utilizador_id' => $utilizador->getKey(),
            'papel_anterior' => PapelUtilizador::Utilizador->value,
            'papel_novo' => PapelUtilizador::Administrador->value,
            'responsavel_id' => $responsavel->getKey(),
            'registado_em' => now(),
        ];

        $dados[$campo] =
            $valor;

        $this->expectException(
            QueryException::class,
        );

        DB::table(
            'registos_papel_utilizadores',
        )->insert(
            $dados,
        );
    }

    /**
     * Confirma que uma alteração sem efeito é rejeitada.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function rejeita_papeis_iguais(): void
    {
        $utilizador =
            Utilizador::factory()
                ->create();

        $responsavel =
            Utilizador::factory()
                ->comPapel(
                    PapelUtilizador::SuperAdministrador,
                )
                ->create();

        $this->expectException(
            QueryException::class,
        );

        DB::table(
            'registos_papel_utilizadores',
        )->insert([
            'utilizador_id' => $utilizador->getKey(),
            'papel_anterior' => PapelUtilizador::Administrador->value,
            'papel_novo' => PapelUtilizador::Administrador->value,
            'responsavel_id' => $responsavel->getKey(),
            'registado_em' => now(),
        ]);
    }

    /**
     * Confirma que o utilizador afetado não pode ser o responsável.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function rejeita_o_utilizador_como_responsavel_do_proprio_historico(): void
    {
        $utilizador =
            Utilizador::factory()
                ->create();

        $this->expectException(
            QueryException::class,
        );

        DB::table(
            'registos_papel_utilizadores',
        )->insert([
            'utilizador_id' => $utilizador->getKey(),
            'papel_anterior' => PapelUtilizador::Utilizador->value,
            'papel_novo' => PapelUtilizador::Administrador->value,
            'responsavel_id' => $utilizador->getKey(),
            'registado_em' => now(),
        ]);
    }

    /**
     * Fornece valores incompatíveis com o contrato dos papéis.
     *
     * @return array<string, array{0: string, 1: string}> Valores inválidos.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public static function fornecerPapeisInvalidos(): array
    {
        return [
            'papel anterior desconhecido' => [
                'papel_anterior',
                'editor',
            ],

            'papel novo desconhecido' => [
                'papel_novo',
                'editor',
            ],

            'papel anterior com maiúsculas' => [
                'papel_anterior',
                'ADMINISTRADOR',
            ],

            'papel novo com espaço final' => [
                'papel_novo',
                'administrador ',
            ],
        ];
    }
}
