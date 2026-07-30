<?php

declare(strict_types=1);

namespace Tests\Unit\Enumeracoes;

use App\Enumeracoes\RespostaBinaria;
use PHPUnit\Framework\TestCase;

/**
 * Testa a enumeração das respostas binárias.
 *
 * @since 2.0.0
 *
 * @version 2.0.0
 */
final class RespostaBinariaTest extends TestCase
{
    /**
     * Confirma que os valores públicos portugueses são reconhecidos.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    public function test_reconhece_valores_publicos_portugueses(): void
    {
        self::assertSame(
            RespostaBinaria::Sim,
            RespostaBinaria::tentarCriar(
                'sim',
            ),
        );

        self::assertSame(
            RespostaBinaria::Nao,
            RespostaBinaria::tentarCriar(
                'nao',
            ),
        );
    }

    /**
     * Confirma que os valores textuais são normalizados.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function test_normaliza_espacos_e_maiusculas(): void
    {
        self::assertSame(
            RespostaBinaria::Sim,
            RespostaBinaria::tentarCriar(
                '  SIM  ',
            ),
        );

        self::assertSame(
            RespostaBinaria::Nao,
            RespostaBinaria::tentarCriar(
                'NAO',
            ),
        );
    }

    /**
     * Confirma que os valores ingleses antigos não são reconhecidos.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    public function test_rejeita_valores_ingleses_antigos(): void
    {
        self::assertNull(
            RespostaBinaria::tentarCriar(
                'yes',
            ),
        );

        self::assertNull(
            RespostaBinaria::tentarCriar(
                'no',
            ),
        );
    }

    /**
     * Confirma que valores inválidos não originam uma resposta.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    public function test_rejeita_valores_invalidos(): void
    {
        self::assertNull(
            RespostaBinaria::tentarCriar(
                'talvez',
            ),
        );

        self::assertNull(
            RespostaBinaria::tentarCriar(
                '',
            ),
        );

        self::assertNull(
            RespostaBinaria::tentarCriar(
                true,
            ),
        );

        self::assertNull(
            RespostaBinaria::tentarCriar(
                1,
            ),
        );

        self::assertNull(
            RespostaBinaria::tentarCriar(
                null,
            ),
        );
    }

    /**
     * Confirma a criação explícita a partir de valores booleanos.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function test_cria_respostas_a_partir_de_booleanos(): void
    {
        self::assertSame(
            RespostaBinaria::Sim,
            RespostaBinaria::deBooleano(
                true,
            ),
        );

        self::assertSame(
            RespostaBinaria::Nao,
            RespostaBinaria::deBooleano(
                false,
            ),
        );
    }

    /**
     * Confirma a conversão das respostas para valores booleanos.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    public function test_converte_respostas_para_booleanos(): void
    {
        self::assertTrue(
            RespostaBinaria::Sim->paraBooleano(),
        );

        self::assertFalse(
            RespostaBinaria::Nao->paraBooleano(),
        );
    }

    /**
     * Confirma as etiquetas apresentadas ao utilizador.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function test_devolve_etiquetas_portuguesas(): void
    {
        self::assertSame(
            'Sim',
            RespostaBinaria::Sim->etiqueta(),
        );

        self::assertSame(
            'Não',
            RespostaBinaria::Nao->etiqueta(),
        );
    }
}
