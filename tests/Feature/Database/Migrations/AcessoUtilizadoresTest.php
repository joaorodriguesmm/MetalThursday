<?php

declare(strict_types=1);

namespace Tests\Feature\Database\Migrations;

use App\Enumeracoes\AcaoAcessoUtilizador;
use App\Models\Autenticacao\Utilizador;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa a integridade do estado e do histórico de acesso dos utilizadores.
 *
 * @since 2.0.0
 */
final class AcessoUtilizadoresTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Confirma que as restrições de coerência foram criadas.
     *
     * @since 2.0.0
     */
    #[Test]
    public function possui_as_restricoes_de_integridade(): void
    {
        $nomes = collect(
            DB::select(
                <<<'SQL'
                    SELECT
                        CONSTRAINT_NAME AS nome
                    FROM information_schema.TABLE_CONSTRAINTS
                    WHERE CONSTRAINT_SCHEMA = DATABASE()
                      AND CONSTRAINT_TYPE = 'CHECK'
                      AND CONSTRAINT_NAME IN (
                          'utilizadores_suspensao_coerente_verificacao',
                          'registos_acesso_acao_valida_verificacao',
                          'registos_acesso_estado_coerente_verificacao',
                          'registos_acesso_responsavel_distinto_verificacao'
                      )
                    ORDER BY CONSTRAINT_NAME
                    SQL,
            ),
        )
            ->pluck(
                'nome',
            )
            ->all();

        self::assertSame(
            [
                'registos_acesso_acao_valida_verificacao',
                'registos_acesso_estado_coerente_verificacao',
                'registos_acesso_responsavel_distinto_verificacao',
                'utilizadores_suspensao_coerente_verificacao',
            ],
            $nomes,
        );
    }

    /**
     * Confirma que um utilizador ativo possui todos os campos de suspensão
     * vazios.
     *
     * @since 2.0.0
     */
    #[Test]
    public function permite_um_utilizador_com_acesso_ativo(): void
    {
        $utilizador = Utilizador::factory()
            ->create();

        $estado = DB::table(
            'utilizadores',
        )
            ->where(
                'id',
                $utilizador->getKey(),
            )
            ->first([
                'suspenso_em',
                'motivo_suspensao',
                'suspenso_por_id',
            ]);

        self::assertNotNull(
            $estado,
        );

        self::assertNull(
            $estado->suspenso_em,
        );

        self::assertNull(
            $estado->motivo_suspensao,
        );

        self::assertNull(
            $estado->suspenso_por_id,
        );
    }

    /**
     * Confirma que um estado de suspensão coerente pode ser persistido.
     *
     * @since 2.0.0
     */
    #[Test]
    public function permite_um_estado_de_suspensao_coerente(): void
    {
        [
            $utilizador,
            $responsavel,
        ] = $this->criarUtilizadorEResponsavel();

        $atualizados = DB::table(
            'utilizadores',
        )
            ->where(
                'id',
                $utilizador->getKey(),
            )
            ->update([
                'suspenso_em' => now(),

                'motivo_suspensao' => 'Motivo válido.',

                'suspenso_por_id' => $responsavel->getKey(),
            ]);

        self::assertSame(
            1,
            $atualizados,
        );
    }

    /**
     * Confirma que todos os estados parciais de suspensão são rejeitados.
     *
     * @param  bool  $possuiData  Define se a data é preenchida.
     * @param  string|null  $motivo  Motivo recebido.
     * @param  bool  $possuiResponsavel  Define se o responsável é preenchido.
     *
     * @since 2.0.0
     */
    #[Test]
    #[DataProvider('fornecerEstadosParciaisSuspensao')]
    public function rejeita_estados_parciais_de_suspensao(
        bool $possuiData,
        ?string $motivo,
        bool $possuiResponsavel,
    ): void {
        [
            $utilizador,
            $responsavel,
        ] = $this->criarUtilizadorEResponsavel();

        $this->expectException(
            QueryException::class,
        );

        DB::table(
            'utilizadores',
        )
            ->where(
                'id',
                $utilizador->getKey(),
            )
            ->update([
                'suspenso_em' => $possuiData
                    ? now()
                    : null,

                'motivo_suspensao' => $motivo,

                'suspenso_por_id' => $possuiResponsavel
                    ? $responsavel->getKey()
                    : null,
            ]);
    }

    /**
     * Confirma que um motivo de suspensão sem conteúdo é rejeitado.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_um_motivo_de_suspensao_sem_conteudo(): void
    {
        [
            $utilizador,
            $responsavel,
        ] = $this->criarUtilizadorEResponsavel();

        $this->expectException(
            QueryException::class,
        );

        DB::table(
            'utilizadores',
        )
            ->where(
                'id',
                $utilizador->getKey(),
            )
            ->update([
                'suspenso_em' => now(),

                'motivo_suspensao' => '   ',

                'suspenso_por_id' => $responsavel->getKey(),
            ]);
    }

    /**
     * Confirma que uma suspensão histórica coerente pode ser persistida.
     *
     * @since 2.0.0
     */
    #[Test]
    public function permite_um_registo_historico_de_suspensao(): void
    {
        [
            $utilizador,
            $responsavel,
        ] = $this->criarUtilizadorEResponsavel();

        $resultado = DB::table(
            'registos_acesso_utilizadores',
        )->insert([
            'utilizador_id' => $utilizador->getKey(),

            'acao' => AcaoAcessoUtilizador::Suspensao->value,

            'motivo' => 'Motivo válido.',

            'responsavel_id' => $responsavel->getKey(),

            'registado_em' => now(),
        ]);

        self::assertTrue(
            $resultado,
        );
    }

    /**
     * Confirma que uma reativação histórica não possui motivo.
     *
     * @since 2.0.0
     */
    #[Test]
    public function permite_um_registo_historico_de_reativacao(): void
    {
        [
            $utilizador,
            $responsavel,
        ] = $this->criarUtilizadorEResponsavel();

        $resultado = DB::table(
            'registos_acesso_utilizadores',
        )->insert([
            'utilizador_id' => $utilizador->getKey(),

            'acao' => AcaoAcessoUtilizador::Reativacao->value,

            'motivo' => null,

            'responsavel_id' => $responsavel->getKey(),

            'registado_em' => now(),
        ]);

        self::assertTrue(
            $resultado,
        );
    }

    /**
     * Confirma que ações históricas desconhecidas são rejeitadas.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_uma_acao_historica_desconhecida(): void
    {
        [
            $utilizador,
            $responsavel,
        ] = $this->criarUtilizadorEResponsavel();

        $this->expectException(
            QueryException::class,
        );

        DB::table(
            'registos_acesso_utilizadores',
        )->insert([
            'utilizador_id' => $utilizador->getKey(),

            'acao' => 'bloqueio',

            'motivo' => 'Motivo válido.',

            'responsavel_id' => $responsavel->getKey(),

            'registado_em' => now(),
        ]);
    }

    /**
     * Confirma que a coerência entre a ação e o motivo é obrigatória.
     *
     * @param  AcaoAcessoUtilizador  $acao  Ação testada.
     * @param  string|null  $motivo  Motivo incompatível.
     *
     * @since 2.0.0
     */
    #[Test]
    #[DataProvider('fornecerHistoricosIncoerentes')]
    public function rejeita_um_historico_incoerente(
        AcaoAcessoUtilizador $acao,
        ?string $motivo,
    ): void {
        [
            $utilizador,
            $responsavel,
        ] = $this->criarUtilizadorEResponsavel();

        $this->expectException(
            QueryException::class,
        );

        DB::table(
            'registos_acesso_utilizadores',
        )->insert([
            'utilizador_id' => $utilizador->getKey(),

            'acao' => $acao->value,

            'motivo' => $motivo,

            'responsavel_id' => $responsavel->getKey(),

            'registado_em' => now(),
        ]);
    }

    /**
     * Confirma que o responsável histórico tem de ser diferente do utilizador
     * afetado.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_o_utilizador_como_responsavel_do_proprio_historico(): void
    {
        $utilizador = Utilizador::factory()
            ->create();

        $this->expectException(
            QueryException::class,
        );

        DB::table(
            'registos_acesso_utilizadores',
        )->insert([
            'utilizador_id' => $utilizador->getKey(),

            'acao' => AcaoAcessoUtilizador::Suspensao->value,

            'motivo' => 'Motivo válido.',

            'responsavel_id' => $utilizador->getKey(),

            'registado_em' => now(),
        ]);
    }

    /**
     * Fornece todos os estados atuais parcialmente preenchidos.
     *
     * @return array<string, array{0: bool, 1: string|null, 2: bool}>
     *                                                                Estados inválidos.
     *
     * @since 2.0.0
     */
    public static function fornecerEstadosParciaisSuspensao(): array
    {
        return [
            'apenas data' => [
                true,
                null,
                false,
            ],

            'apenas motivo' => [
                false,
                'Motivo válido.',
                false,
            ],

            'apenas responsável' => [
                false,
                null,
                true,
            ],

            'data e motivo sem responsável' => [
                true,
                'Motivo válido.',
                false,
            ],

            'data e responsável sem motivo' => [
                true,
                null,
                true,
            ],

            'motivo e responsável sem data' => [
                false,
                'Motivo válido.',
                true,
            ],
        ];
    }

    /**
     * Fornece ações e motivos historicamente incompatíveis.
     *
     * @return array<string, array{0: AcaoAcessoUtilizador, 1: string|null}>
     *                                                                       Históricos inválidos.
     *
     * @since 2.0.0
     */
    public static function fornecerHistoricosIncoerentes(): array
    {
        return [
            'suspensão sem motivo' => [
                AcaoAcessoUtilizador::Suspensao,
                null,
            ],

            'suspensão com motivo sem conteúdo' => [
                AcaoAcessoUtilizador::Suspensao,
                '   ',
            ],

            'reativação com motivo' => [
                AcaoAcessoUtilizador::Reativacao,
                'Motivo indevido.',
            ],
        ];
    }

    /**
     * Cria um utilizador afetado e outro utilizador responsável.
     *
     * @return array{0: Utilizador, 1: Utilizador} Utilizadores criados.
     *
     * @since 2.0.0
     */
    private function criarUtilizadorEResponsavel(): array
    {
        return [
            Utilizador::factory()
                ->create(),

            Utilizador::factory()
                ->create(),
        ];
    }
}
