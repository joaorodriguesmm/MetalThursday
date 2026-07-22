<?php

declare(strict_types=1);

namespace Tests\Unit\Enumeracoes;

use App\Enumeracoes\DirecaoOrdenacao;
use PHPUnit\Framework\TestCase;

/**
 * Testa a enumeração das direções de ordenação.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
final class DirecaoOrdenacaoTest extends TestCase
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
            DirecaoOrdenacao::Ascendente,
            DirecaoOrdenacao::tentarCriar('ascendente'),
        );

        $this->assertSame(
            DirecaoOrdenacao::Descendente,
            DirecaoOrdenacao::tentarCriar('descendente'),
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
            DirecaoOrdenacao::Ascendente,
            DirecaoOrdenacao::tentarCriar('asc'),
        );

        $this->assertSame(
            DirecaoOrdenacao::Descendente,
            DirecaoOrdenacao::tentarCriar('desc'),
        );
    }

    /**
     * Confirma que valores inválidos não originam uma direção.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function test_rejeita_valores_invalidos(): void
    {
        $this->assertNull(
            DirecaoOrdenacao::tentarCriar('invalido'),
        );

        $this->assertNull(
            DirecaoOrdenacao::tentarCriar(123),
        );

        $this->assertNull(
            DirecaoOrdenacao::tentarCriar(null),
        );
    }

    /**
     * Confirma a conversão para os valores utilizados pelo SQL.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function test_converte_direcao_para_sql(): void
    {
        $this->assertSame(
            'asc',
            DirecaoOrdenacao::Ascendente->paraSql(),
        );

        $this->assertSame(
            'desc',
            DirecaoOrdenacao::Descendente->paraSql(),
        );
    }
}
