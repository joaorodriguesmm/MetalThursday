<?php

declare(strict_types=1);

namespace Tests\Feature\Models\Autenticacao;

use App\Enumeracoes\PapelUtilizador;
use App\Models\Autenticacao\RegistoPapelUtilizador;
use App\Models\Autenticacao\Utilizador;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa o modelo do histórico dos papéis dos utilizadores.
 *
 * @since 2.0.0
 */
final class RegistoPapelUtilizadorTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Confirma os casts e as relações de uma alteração.
     *
     * @since 2.0.0
     */
    #[Test]
    public function carrega_uma_alteracao_com_os_contratos_esperados(): void
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

        $momento =
            CarbonImmutable::parse(
                '2026-08-04 09:00:00',
            );

        $registo =
            RegistoPapelUtilizador::factory()
                ->paraUtilizador(
                    $utilizador,
                )
                ->registadoPor(
                    $responsavel,
                )
                ->alteracao(
                    PapelUtilizador::Utilizador,
                    PapelUtilizador::Administrador,
                )
                ->registadoEm(
                    $momento,
                )
                ->create()
                ->fresh([
                    'utilizador',
                    'responsavel',
                ]);

        self::assertNotNull(
            $registo,
        );

        self::assertSame(
            PapelUtilizador::Utilizador,
            $registo->papel_anterior,
        );

        self::assertSame(
            PapelUtilizador::Administrador,
            $registo->papel_novo,
        );

        self::assertTrue(
            $registo->registado_em instanceof CarbonImmutable,
        );

        self::assertTrue(
            $registo->utilizador->is(
                $utilizador,
            ),
        );

        self::assertTrue(
            $registo->responsavel->is(
                $responsavel,
            ),
        );
    }

    /**
     * Confirma que o histórico do utilizador é ordenado do mais recente para
     * o mais antigo.
     *
     * @since 2.0.0
     */
    #[Test]
    public function ordena_o_historico_do_mais_recente_para_o_mais_antigo(): void
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

        $maisAntigo =
            RegistoPapelUtilizador::factory()
                ->paraUtilizador(
                    $utilizador,
                )
                ->registadoPor(
                    $responsavel,
                )
                ->alteracao(
                    PapelUtilizador::Utilizador,
                    PapelUtilizador::Administrador,
                )
                ->registadoEm(
                    CarbonImmutable::parse(
                        '2026-08-04 08:00:00',
                    ),
                )
                ->create();

        $maisRecente =
            RegistoPapelUtilizador::factory()
                ->paraUtilizador(
                    $utilizador,
                )
                ->registadoPor(
                    $responsavel,
                )
                ->alteracao(
                    PapelUtilizador::Administrador,
                    PapelUtilizador::SuperAdministrador,
                )
                ->registadoEm(
                    CarbonImmutable::parse(
                        '2026-08-04 09:00:00',
                    ),
                )
                ->create();

        self::assertSame(
            [
                (int) $maisRecente->getKey(),
                (int) $maisAntigo->getKey(),
            ],
            $utilizador
                ->registosPapel()
                ->pluck(
                    'id',
                )
                ->map(
                    static fn (
                        mixed $identificador,
                    ): int => (int) $identificador,
                )
                ->all(),
        );
    }

    /**
     * Confirma a relação das alterações efetuadas pelo responsável.
     *
     * @since 2.0.0
     */
    #[Test]
    public function obtem_as_alteracoes_efetuadas_pelo_responsavel(): void
    {
        $responsavel =
            Utilizador::factory()
                ->comPapel(
                    PapelUtilizador::SuperAdministrador,
                )
                ->create();

        $registo =
            RegistoPapelUtilizador::factory()
                ->registadoPor(
                    $responsavel,
                )
                ->create();

        self::assertSame(
            [
                (int) $registo->getKey(),
            ],
            $responsavel
                ->registosPapelEfetuados()
                ->pluck(
                    'id',
                )
                ->map(
                    static fn (
                        mixed $identificador,
                    ): int => (int) $identificador,
                )
                ->all(),
        );
    }

    /**
     * Confirma que um registo persistido não pode ser alterado.
     *
     * @since 2.0.0
     */
    #[Test]
    public function impede_a_alteracao_de_um_registo_persistido(): void
    {
        $registo =
            RegistoPapelUtilizador::factory()
                ->create();

        $registo->papel_novo =
            PapelUtilizador::SuperAdministrador;

        $this->expectException(
            LogicException::class,
        );

        $registo->save();
    }

    /**
     * Confirma que um registo persistido não pode ser eliminado.
     *
     * @since 2.0.0
     */
    #[Test]
    public function impede_a_eliminacao_de_um_registo_persistido(): void
    {
        $registo =
            RegistoPapelUtilizador::factory()
                ->create();

        $this->expectException(
            LogicException::class,
        );

        $registo->delete();
    }
}
