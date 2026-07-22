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
 * @version 1.0.0
 */
final class RespostaBinariaTest extends TestCase
{
    /**
     * Confirma que os valores portugueses são reconhecidos.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function test_reconhece_valores_portugueses(): void
    {
        $this->assertSame(
            RespostaBinaria::Sim,
            RespostaBinaria::tentarCriar('sim'),
        );

        $this->assertSame(
            RespostaBinaria::Nao,
            RespostaBinaria::tentarCriar('nao'),
        );
    }

    /**
     * Confirma que os valores antigos continuam temporariamente suportados.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function test_reconhece_valores_antigos(): void
    {
        $this->assertSame(
            RespostaBinaria::Sim,
            RespostaBinaria::tentarCriar('yes'),
        );

        $this->assertSame(
            RespostaBinaria::Nao,
            RespostaBinaria::tentarCriar('no'),
        );
    }

    /**
     * Confirma que valores inválidos não originam uma resposta.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function test_rejeita_valores_invalidos(): void
    {
        $this->assertNull(
            RespostaBinaria::tentarCriar('talvez'),
        );

        $this->assertNull(
            RespostaBinaria::tentarCriar(true),
        );

        $this->assertNull(
            RespostaBinaria::tentarCriar(null),
        );
    }

    /**
     * Confirma a conversão das respostas para valores booleanos.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function test_converte_respostas_para_booleanos(): void
    {
        $this->assertTrue(
            RespostaBinaria::Sim->paraBooleano(),
        );

        $this->assertFalse(
            RespostaBinaria::Nao->paraBooleano(),
        );
    }
}
