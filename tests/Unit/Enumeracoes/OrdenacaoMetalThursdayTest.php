<?php

declare(strict_types=1);

namespace Tests\Unit\Enumeracoes;

use App\Enumeracoes\OrdenacaoMetalThursday;
use PHPUnit\Framework\TestCase;

/**
 * Testa a enumeração das ordenações das MetalThursdays.
 *
 * @since 2.0.0
 *
 * @version 2.0.0
 */
final class OrdenacaoMetalThursdayTest extends TestCase
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
            OrdenacaoMetalThursday::Data,
            OrdenacaoMetalThursday::tentarCriar(
                'data',
            ),
        );

        self::assertSame(
            OrdenacaoMetalThursday::Classificacao,
            OrdenacaoMetalThursday::tentarCriar(
                'classificacao',
            ),
        );

        self::assertSame(
            OrdenacaoMetalThursday::MinhaClassificacao,
            OrdenacaoMetalThursday::tentarCriar(
                'minha_classificacao',
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
            OrdenacaoMetalThursday::Data,
            OrdenacaoMetalThursday::tentarCriar(
                '  DATA  ',
            ),
        );

        self::assertSame(
            OrdenacaoMetalThursday::Classificacao,
            OrdenacaoMetalThursday::tentarCriar(
                'CLASSIFICACAO',
            ),
        );

        self::assertSame(
            OrdenacaoMetalThursday::MinhaClassificacao,
            OrdenacaoMetalThursday::tentarCriar(
                'MINHA_CLASSIFICACAO',
            ),
        );
    }

    /**
     * Confirma que os contratos antigos e aliases não são reconhecidos.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    public function test_rejeita_valores_antigos_e_aliases(): void
    {
        self::assertNull(
            OrdenacaoMetalThursday::tentarCriar(
                'date',
            ),
        );

        self::assertNull(
            OrdenacaoMetalThursday::tentarCriar(
                'rating',
            ),
        );

        self::assertNull(
            OrdenacaoMetalThursday::tentarCriar(
                'my_rating',
            ),
        );

        self::assertNull(
            OrdenacaoMetalThursday::tentarCriar(
                'avaliacao',
            ),
        );

        self::assertNull(
            OrdenacaoMetalThursday::tentarCriar(
                'minha_avaliacao',
            ),
        );
    }

    /**
     * Confirma que valores inválidos não originam uma ordenação.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    public function test_rejeita_valores_invalidos(): void
    {
        self::assertNull(
            OrdenacaoMetalThursday::tentarCriar(
                'invalido',
            ),
        );

        self::assertNull(
            OrdenacaoMetalThursday::tentarCriar(
                '',
            ),
        );

        self::assertNull(
            OrdenacaoMetalThursday::tentarCriar(
                [],
            ),
        );

        self::assertNull(
            OrdenacaoMetalThursday::tentarCriar(
                null,
            ),
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
            'Data',
            OrdenacaoMetalThursday::Data->etiqueta(),
        );

        self::assertSame(
            'Avaliação média',
            OrdenacaoMetalThursday::Classificacao->etiqueta(),
        );

        self::assertSame(
            'A minha avaliação',
            OrdenacaoMetalThursday::MinhaClassificacao->etiqueta(),
        );
    }
}
