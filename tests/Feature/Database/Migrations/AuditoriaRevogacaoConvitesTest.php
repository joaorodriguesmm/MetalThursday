<?php

declare(strict_types=1);

namespace Tests\Feature\Database\Migrations;

use App\Enumeracoes\PapelUtilizador;
use App\Models\Autenticacao\Convite;
use App\Models\Autenticacao\Utilizador;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa a integridade da autoria das revogações dos convites.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
final class AuditoriaRevogacaoConvitesTest extends TestCase
{
    use RefreshDatabase;

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
     * Confirma a coluna, a chave estrangeira e a restrição.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function possui_estrutura_de_auditoria_da_revogacao(): void
    {
        self::assertTrue(
            Schema::hasColumn(
                'convites',
                'revogado_por_id',
            ),
        );

        $restricao = DB::selectOne(
            <<<'SQL'
                SELECT
                    CONSTRAINT_NAME AS nome,
                    CONSTRAINT_TYPE AS tipo
                FROM information_schema.TABLE_CONSTRAINTS
                WHERE CONSTRAINT_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'convites'
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
            $restricao->nome,
        );

        self::assertSame(
            'CHECK',
            $restricao->tipo,
        );

        $chaveEstrangeira = DB::selectOne(
            <<<'SQL'
                SELECT
                    REFERENCED_TABLE_NAME AS tabela_referenciada
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE CONSTRAINT_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'convites'
                  AND COLUMN_NAME = 'revogado_por_id'
                  AND REFERENCED_TABLE_NAME IS NOT NULL
                SQL,
        );

        self::assertNotNull(
            $chaveEstrangeira,
        );

        self::assertSame(
            'utilizadores',
            $chaveEstrangeira->tabela_referenciada,
        );
    }

    /**
     * Confirma que uma revogação coerente pode ser persistida.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function permite_data_e_responsavel_em_conjunto(): void
    {
        $responsavel =
            $this->criarSuperAdministrador();

        $convite =
            Convite::factory()
                ->revogadoPor(
                    $responsavel,
                )
                ->create();

        self::assertNotNull(
            $convite->revogado_em,
        );

        self::assertSame(
            (int) $responsavel->getKey(),
            $convite->revogado_por_id,
        );
    }

    /**
     * Confirma que estados parciais de revogação são rejeitados.
     *
     * @param  bool  $comData  Indica se a data é persistida.
     * @param  bool  $comResponsavel  Indica se o responsável é persistido.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    #[DataProvider('fornecerEstadosParciais')]
    public function rejeita_estados_parciais_de_revogacao(
        bool $comData,
        bool $comResponsavel,
    ): void {
        $responsavel =
            $this->criarSuperAdministrador();

        $this->expectException(
            QueryException::class,
        );

        DB::table(
            'convites',
        )->insert([
            'nome_convidado' => 'Convite parcialmente revogado',

            'email_destino' => null,

            'codigo_hash' => hash(
                'sha256',
                sprintf(
                    'estado-%d-%d',
                    (int) $comData,
                    (int) $comResponsavel,
                ),
            ),

            'criado_por_id' => null,

            'utilizado_por_id' => null,

            'expira_em' => null,

            'utilizado_em' => null,

            'revogado_em' => $comData
                ? now()
                : null,

            'revogado_por_id' => $comResponsavel
                ? $responsavel->getKey()
                : null,

            'created_at' => now(),

            'updated_at' => now(),
        ]);
    }

    /**
     * Confirma que o responsável por uma revogação não pode ser eliminado.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function preserva_o_responsavel_pela_revogacao(): void
    {
        $responsavel =
            $this->criarSuperAdministrador();

        Convite::factory()
            ->revogadoPor(
                $responsavel,
            )
            ->create();

        $this->expectException(
            QueryException::class,
        );

        $responsavel->delete();
    }

    /**
     * Fornece combinações parciais inválidas.
     *
     * @return array<string, array{0: bool, 1: bool}> Estados inválidos.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public static function fornecerEstadosParciais(): array
    {
        return [
            'data sem responsável' => [
                true,
                false,
            ],

            'responsável sem data' => [
                false,
                true,
            ],
        ];
    }

    /**
     * Cria um superadministrador ativo.
     *
     * @return Utilizador Superadministrador criado.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function criarSuperAdministrador(): Utilizador
    {
        return Utilizador::factory()
            ->comPapel(
                PapelUtilizador::SuperAdministrador,
            )
            ->create();
    }
}
