<?php

declare(strict_types=1);

namespace Tests\Feature\Factories\Autenticacao;

use App\Enumeracoes\AcaoAcessoUtilizador;
use App\Models\Autenticacao\RegistoAcessoUtilizador;
use App\Models\Autenticacao\Utilizador;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa os estados da factory dos registos de acesso.
 *
 * @since 2.0.0
 */
final class FactoriesRegistoAcessoUtilizadorTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Confirma a criação de uma suspensão com relações conhecidas.
     *
     * @since 2.0.0
     */
    #[Test]
    public function cria_suspensao_com_relacoes_conhecidas(): void
    {
        $utilizador = Utilizador::factory()
            ->create();

        $responsavel = Utilizador::factory()
            ->create();

        $registo = RegistoAcessoUtilizador::factory()
            ->suspensao(
                "  Suspensão \n para testes. ",
            )
            ->paraUtilizador(
                $utilizador,
            )
            ->registadoPor(
                $responsavel,
            )
            ->create();

        self::assertSame(
            (int) $utilizador->getKey(),
            $registo->utilizador_id,
        );

        self::assertSame(
            (int) $responsavel->getKey(),
            $registo->responsavel_id,
        );

        self::assertSame(
            AcaoAcessoUtilizador::Suspensao,
            $registo->acao,
        );

        self::assertSame(
            'Suspensão para testes.',
            $registo->motivo,
        );

        self::assertTrue(
            $registo->eSuspensao(),
        );

        self::assertFalse(
            $registo->eReativacao(),
        );
    }

    /**
     * Confirma a criação de uma reativação sem motivo.
     *
     * @since 2.0.0
     */
    #[Test]
    public function cria_reativacao_sem_motivo(): void
    {
        $registo = RegistoAcessoUtilizador::factory()
            ->reativacao()
            ->create();

        self::assertSame(
            AcaoAcessoUtilizador::Reativacao,
            $registo->acao,
        );

        self::assertNull(
            $registo->motivo,
        );

        self::assertFalse(
            $registo->eSuspensao(),
        );

        self::assertTrue(
            $registo->eReativacao(),
        );
    }

    /**
     * Confirma que um motivo de suspensão inválido é rejeitado.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_motivo_de_suspensao_invalido(): void
    {
        $this->expectException(
            InvalidArgumentException::class,
        );

        RegistoAcessoUtilizador::factory()
            ->suspensao(
                '   ',
            );
    }

    /**
     * Confirma que um utilizador não persistido é rejeitado.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_utilizador_afetado_nao_persistido(): void
    {
        $this->expectException(
            InvalidArgumentException::class,
        );

        RegistoAcessoUtilizador::factory()
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

        RegistoAcessoUtilizador::factory()
            ->registadoPor(
                new Utilizador,
            );
    }
}
