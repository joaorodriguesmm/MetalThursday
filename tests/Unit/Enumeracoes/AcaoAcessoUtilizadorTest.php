<?php

declare(strict_types=1);

namespace Tests\Unit\Enumeracoes;

use App\Enumeracoes\AcaoAcessoUtilizador;
use PHPUnit\Framework\TestCase;

/**
 * Testa a enumeração das alterações de acesso dos utilizadores.
 *
 * @since 2.0.0
 */
final class AcaoAcessoUtilizadorTest extends TestCase
{
    /**
     * Confirma que os valores públicos portugueses são reconhecidos.
     *
     * @since 2.0.0
     */
    public function test_reconhece_valores_publicos_portugueses(): void
    {
        self::assertSame(
            AcaoAcessoUtilizador::Suspensao,
            AcaoAcessoUtilizador::tentarCriar(
                'suspensao',
            ),
        );

        self::assertSame(
            AcaoAcessoUtilizador::Reativacao,
            AcaoAcessoUtilizador::tentarCriar(
                'reativacao',
            ),
        );
    }

    /**
     * Confirma que espaços exteriores e maiúsculas são normalizados.
     *
     * @since 2.0.0
     */
    public function test_normaliza_espacos_e_maiusculas(): void
    {
        self::assertSame(
            AcaoAcessoUtilizador::Suspensao,
            AcaoAcessoUtilizador::tentarCriar(
                '  SUSPENSAO  ',
            ),
        );

        self::assertSame(
            AcaoAcessoUtilizador::Reativacao,
            AcaoAcessoUtilizador::tentarCriar(
                'REATIVACAO',
            ),
        );
    }

    /**
     * Confirma que aliases e valores inválidos são rejeitados.
     *
     * @since 2.0.0
     */
    public function test_rejeita_aliases_e_valores_invalidos(): void
    {
        self::assertNull(
            AcaoAcessoUtilizador::tentarCriar(
                'suspender',
            ),
        );

        self::assertNull(
            AcaoAcessoUtilizador::tentarCriar(
                'reativar',
            ),
        );

        self::assertNull(
            AcaoAcessoUtilizador::tentarCriar(
                'suspensão',
            ),
        );

        self::assertNull(
            AcaoAcessoUtilizador::tentarCriar(
                '',
            ),
        );

        self::assertNull(
            AcaoAcessoUtilizador::tentarCriar(
                1,
            ),
        );

        self::assertNull(
            AcaoAcessoUtilizador::tentarCriar(
                null,
            ),
        );
    }

    /**
     * Confirma as etiquetas portuguesas das ações.
     *
     * @since 2.0.0
     */
    public function test_devolve_etiquetas_portuguesas(): void
    {
        self::assertSame(
            'Suspensão',
            AcaoAcessoUtilizador::Suspensao->etiqueta(),
        );

        self::assertSame(
            'Reativação',
            AcaoAcessoUtilizador::Reativacao->etiqueta(),
        );
    }

    /**
     * Confirma a distinção entre suspensão e reativação.
     *
     * @since 2.0.0
     */
    public function test_distingue_as_acoes(): void
    {
        self::assertTrue(
            AcaoAcessoUtilizador::Suspensao->eSuspensao(),
        );

        self::assertFalse(
            AcaoAcessoUtilizador::Suspensao->eReativacao(),
        );

        self::assertFalse(
            AcaoAcessoUtilizador::Reativacao->eSuspensao(),
        );

        self::assertTrue(
            AcaoAcessoUtilizador::Reativacao->eReativacao(),
        );
    }
}
