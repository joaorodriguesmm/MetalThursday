<?php

declare(strict_types=1);

namespace Tests\Feature\Database\Migrations;

use App\Enumeracoes\PapelUtilizador;
use App\Models\Autenticacao\Utilizador;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa a integridade dos papéis persistidos nos utilizadores.
 *
 * @since 2.0.0
 */
final class PapeisUtilizadoresTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Nome da restrição aplicada à coluna dos papéis.
     *
     * @since 2.0.0
     */
    private const NOME_RESTRICAO =
        'utilizadores_papel_valido_verificacao';

    /**
     * Confirma que a restrição dos papéis existe na base de dados.
     *
     * @since 2.0.0
     */
    #[Test]
    public function possui_restricao_dos_papeis(): void
    {
        $restricao = DB::selectOne(
            <<<'SQL'
                SELECT
                    CONSTRAINT_NAME AS nome_restricao,
                    CONSTRAINT_TYPE AS tipo_restricao
                FROM information_schema.TABLE_CONSTRAINTS
                WHERE CONSTRAINT_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'utilizadores'
                  AND CONSTRAINT_NAME = ?
                SQL,
            [
                self::NOME_RESTRICAO,
            ],
        );

        self::assertNotNull(
            $restricao,
        );

        self::assertSame(
            self::NOME_RESTRICAO,
            $restricao->nome_restricao,
        );

        self::assertSame(
            'CHECK',
            $restricao->tipo_restricao,
        );
    }

    /**
     * Confirma que todos os papéis da enumeração podem ser persistidos.
     *
     * @since 2.0.0
     */
    #[Test]
    public function permite_os_papeis_definidos_pela_aplicacao(): void
    {
        foreach (PapelUtilizador::cases() as $papel) {
            $utilizador = Utilizador::factory()->create();

            DB::table(
                'utilizadores',
            )
                ->where(
                    'id',
                    $utilizador->id,
                )
                ->update([
                    'papel' => $papel->value,
                ]);

            self::assertSame(
                $papel->value,
                DB::table(
                    'utilizadores',
                )
                    ->where(
                        'id',
                        $utilizador->id,
                    )
                    ->value(
                        'papel',
                    ),
            );
        }
    }

    /**
     * Confirma que a base de dados rejeita papéis fora do contrato exato.
     *
     * @param  string  $papel  Papel inválido testado.
     *
     * @since 2.0.0
     */
    #[Test]
    #[DataProvider('fornecerPapeisInvalidos')]
    public function rejeita_papeis_invalidos(
        string $papel,
    ): void {
        $utilizador = Utilizador::factory()->create();

        $this->expectException(
            QueryException::class,
        );

        DB::table(
            'utilizadores',
        )
            ->where(
                'id',
                $utilizador->id,
            )
            ->update([
                'papel' => $papel,
            ]);
    }

    /**
     * Fornece papéis incompatíveis com o contrato da aplicação.
     *
     * @return array<string, array{0: string}> Papéis inválidos.
     *
     * @since 2.0.0
     */
    public static function fornecerPapeisInvalidos(): array
    {
        return [
            'valor desconhecido' => [
                'editor',
            ],

            'maiúsculas diferentes' => [
                'ADMINISTRADOR',
            ],

            'espaço final' => [
                'administrador ',
            ],
        ];
    }
}
