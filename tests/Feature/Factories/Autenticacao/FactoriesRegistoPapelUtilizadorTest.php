<?php

declare(strict_types=1);

namespace Tests\Feature\Factories\Autenticacao;

use App\Enumeracoes\PapelUtilizador;
use App\Models\Autenticacao\RegistoPapelUtilizador;
use App\Models\Autenticacao\Utilizador;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa a factory dos registos dos papéis dos utilizadores.
 *
 * @since 2.0.0
 */
final class FactoriesRegistoPapelUtilizadorTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Confirma a criação de uma alteração com relações conhecidas.
     *
     * @since 2.0.0
     */
    #[Test]
    public function cria_alteracao_com_relacoes_conhecidas(): void
    {
        $utilizador =
            Utilizador::factory()
                ->create();

        $responsavel =
            Utilizador::factory()
                ->create();

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
                ->create();

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

        self::assertSame(
            PapelUtilizador::Utilizador,
            $registo->papel_anterior,
        );

        self::assertSame(
            PapelUtilizador::Administrador,
            $registo->papel_novo,
        );
    }

    /**
     * Confirma a criação predefinida de uma alteração coerente.
     *
     * @since 2.0.0
     */
    #[Test]
    public function cria_registo_predefinido_coerente(): void
    {
        $registo =
            RegistoPapelUtilizador::factory()
                ->create();

        self::assertSame(
            PapelUtilizador::Utilizador,
            $registo->papel_anterior,
        );

        self::assertSame(
            PapelUtilizador::Administrador,
            $registo->papel_novo,
        );

        self::assertSame(
            PapelUtilizador::Administrador,
            $registo->utilizador->papel,
        );

        self::assertTrue(
            $registo->responsavel->eSuperAdministrador(),
        );
    }

    /**
     * Confirma a preservação do momento configurado.
     *
     * @since 2.0.0
     */
    #[Test]
    public function preserva_a_data_do_registo(): void
    {
        $momento =
            CarbonImmutable::parse(
                '2026-08-04 08:45:00',
            );

        $registo =
            RegistoPapelUtilizador::factory()
                ->registadoEm(
                    $momento,
                )
                ->create();

        self::assertTrue(
            $registo
                ->registado_em
                ->equalTo(
                    $momento,
                ),
        );
    }

    /**
     * Confirma que a factory rejeita papéis iguais.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_papeis_iguais(): void
    {
        $this->expectException(
            InvalidArgumentException::class,
        );

        RegistoPapelUtilizador::factory()
            ->alteracao(
                PapelUtilizador::Administrador,
                PapelUtilizador::Administrador,
            );
    }

    /**
     * Confirma que um utilizador afetado não persistido é rejeitado.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_utilizador_afetado_nao_persistido(): void
    {
        $this->expectException(
            InvalidArgumentException::class,
        );

        RegistoPapelUtilizador::factory()
            ->paraUtilizador(
                new Utilizador,
            );
    }

    /**
     * Confirma que um responsável não persistido é rejeitado.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_responsavel_nao_persistido(): void
    {
        $this->expectException(
            InvalidArgumentException::class,
        );

        RegistoPapelUtilizador::factory()
            ->registadoPor(
                new Utilizador,
            );
    }
}
