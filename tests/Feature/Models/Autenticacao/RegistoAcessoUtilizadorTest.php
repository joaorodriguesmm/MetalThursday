<?php

declare(strict_types=1);

namespace Tests\Feature\Models\Autenticacao;

use App\Enumeracoes\AcaoAcessoUtilizador;
use App\Models\Autenticacao\RegistoAcessoUtilizador;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa o modelo dos registos de acesso dos utilizadores.
 *
 * @since 2.0.0
 */
final class RegistoAcessoUtilizadorTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Confirma os casts e as relações de uma suspensão.
     *
     * @since 2.0.0
     */
    #[Test]
    public function carrega_uma_suspensao_com_os_contratos_esperados(): void
    {
        $registo = RegistoAcessoUtilizador::factory()
            ->suspensao(
                'Motivo válido.',
            )
            ->create()
            ->fresh();

        self::assertNotNull(
            $registo,
        );

        self::assertSame(
            AcaoAcessoUtilizador::Suspensao,
            $registo->acao,
        );

        self::assertInstanceOf(
            CarbonImmutable::class,
            $registo->registado_em,
        );

        self::assertSame(
            $registo->utilizador_id,
            (int) $registo->utilizador->getKey(),
        );

        self::assertSame(
            $registo->responsavel_id,
            (int) $registo->responsavel->getKey(),
        );
    }

    /**
     * Confirma a normalização do motivo pelo modelo.
     *
     * @since 2.0.0
     */
    #[Test]
    public function normaliza_o_motivo_da_suspensao(): void
    {
        $registo = RegistoAcessoUtilizador::factory()
            ->suspensao()
            ->make();

        $registo->motivo =
            "  Motivo \n normalizado. ";

        self::assertSame(
            'Motivo normalizado.',
            $registo->motivo,
        );
    }

    /**
     * Confirma que um motivo não textual é rejeitado.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_um_motivo_nao_textual(): void
    {
        $this->expectException(
            InvalidArgumentException::class,
        );

        $registo = new RegistoAcessoUtilizador;

        $registo->motivo = 123;
    }

    /**
     * Confirma que um registo persistido não pode ser alterado.
     *
     * @since 2.0.0
     */
    #[Test]
    public function impede_a_alteracao_de_um_registo_persistido(): void
    {
        $registo = RegistoAcessoUtilizador::factory()
            ->suspensao()
            ->create();

        $registo->motivo =
            'Motivo alterado.';

        $this->expectException(
            LogicException::class,
        );

        $registo->saveOrFail();
    }

    /**
     * Confirma que um registo persistido não pode ser eliminado.
     *
     * @since 2.0.0
     */
    #[Test]
    public function impede_a_eliminacao_de_um_registo_persistido(): void
    {
        $registo = RegistoAcessoUtilizador::factory()
            ->create();

        $this->expectException(
            LogicException::class,
        );

        $registo->deleteOrFail();
    }
}
