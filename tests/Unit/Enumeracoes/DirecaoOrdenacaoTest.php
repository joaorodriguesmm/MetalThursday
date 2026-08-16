<?php

declare(strict_types=1);

namespace Tests\Unit\Enumeracoes;

use App\Enumeracoes\DirecaoOrdenacao;
use PHPUnit\Framework\TestCase;

/**
 * Testa a enumeração das direções de ordenação.
 *
 * @since 2.0.0
 */
final class DirecaoOrdenacaoTest extends TestCase
{
    /**
     * Confirma que os valores públicos portugueses são reconhecidos.
     *
     * @since 2.0.0
     */
    public function test_reconhece_valores_publicos_portugueses(): void
    {
        self::assertSame(
            DirecaoOrdenacao::Ascendente,
            DirecaoOrdenacao::tentarCriar(
                'ascendente',
            ),
        );

        self::assertSame(
            DirecaoOrdenacao::Descendente,
            DirecaoOrdenacao::tentarCriar(
                'descendente',
            ),
        );
    }

    /**
     * Confirma que os valores textuais são normalizados.
     *
     * @since 2.0.0
     */
    public function test_normaliza_espacos_e_maiusculas(): void
    {
        self::assertSame(
            DirecaoOrdenacao::Ascendente,
            DirecaoOrdenacao::tentarCriar(
                '  ASCENDENTE  ',
            ),
        );

        self::assertSame(
            DirecaoOrdenacao::Descendente,
            DirecaoOrdenacao::tentarCriar(
                'DESCENDENTE',
            ),
        );
    }

    /**
     * Confirma que os valores técnicos do SQL não são parâmetros públicos.
     *
     * @since 2.0.0
     */
    public function test_rejeita_valores_tecnicos_do_sql(): void
    {
        self::assertNull(
            DirecaoOrdenacao::tentarCriar(
                'asc',
            ),
        );

        self::assertNull(
            DirecaoOrdenacao::tentarCriar(
                'desc',
            ),
        );
    }

    /**
     * Confirma que valores inválidos não originam uma direção.
     *
     * @since 2.0.0
     */
    public function test_rejeita_valores_invalidos(): void
    {
        self::assertNull(
            DirecaoOrdenacao::tentarCriar(
                'invalido',
            ),
        );

        self::assertNull(
            DirecaoOrdenacao::tentarCriar(
                '',
            ),
        );

        self::assertNull(
            DirecaoOrdenacao::tentarCriar(
                123,
            ),
        );

        self::assertNull(
            DirecaoOrdenacao::tentarCriar(
                null,
            ),
        );
    }

    /**
     * Confirma as etiquetas apresentadas ao utilizador.
     *
     * @since 2.0.0
     */
    public function test_devolve_etiquetas_portuguesas(): void
    {
        self::assertSame(
            'Ascendente',
            DirecaoOrdenacao::Ascendente->etiqueta(),
        );

        self::assertSame(
            'Descendente',
            DirecaoOrdenacao::Descendente->etiqueta(),
        );
    }

    /**
     * Confirma a conversão para os valores utilizados pelo SQL.
     *
     * @since 2.0.0
     */
    public function test_converte_direcao_para_sql(): void
    {
        self::assertSame(
            'asc',
            DirecaoOrdenacao::Ascendente->paraSql(),
        );

        self::assertSame(
            'desc',
            DirecaoOrdenacao::Descendente->paraSql(),
        );
    }
}
