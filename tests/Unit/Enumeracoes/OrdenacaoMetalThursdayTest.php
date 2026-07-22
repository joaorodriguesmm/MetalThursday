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
 * @version 1.0.0
 */
final class OrdenacaoMetalThursdayTest extends TestCase
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
            OrdenacaoMetalThursday::Data,
            OrdenacaoMetalThursday::tentarCriar('data'),
        );

        $this->assertSame(
            OrdenacaoMetalThursday::Classificacao,
            OrdenacaoMetalThursday::tentarCriar('classificacao'),
        );

        $this->assertSame(
            OrdenacaoMetalThursday::MinhaClassificacao,
            OrdenacaoMetalThursday::tentarCriar(
                'minha_classificacao',
            ),
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
            OrdenacaoMetalThursday::Data,
            OrdenacaoMetalThursday::tentarCriar('date'),
        );

        $this->assertSame(
            OrdenacaoMetalThursday::Classificacao,
            OrdenacaoMetalThursday::tentarCriar('rating'),
        );

        $this->assertSame(
            OrdenacaoMetalThursday::MinhaClassificacao,
            OrdenacaoMetalThursday::tentarCriar('my_rating'),
        );
    }

    /**
     * Confirma que os aliases portugueses anteriores são reconhecidos.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function test_reconhece_aliases_portugueses(): void
    {
        $this->assertSame(
            OrdenacaoMetalThursday::Classificacao,
            OrdenacaoMetalThursday::tentarCriar('avaliacao'),
        );

        $this->assertSame(
            OrdenacaoMetalThursday::MinhaClassificacao,
            OrdenacaoMetalThursday::tentarCriar(
                'minha_avaliacao',
            ),
        );
    }

    /**
     * Confirma que valores inválidos não originam uma ordenação.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function test_rejeita_valores_invalidos(): void
    {
        $this->assertNull(
            OrdenacaoMetalThursday::tentarCriar('invalido'),
        );

        $this->assertNull(
            OrdenacaoMetalThursday::tentarCriar([]),
        );

        $this->assertNull(
            OrdenacaoMetalThursday::tentarCriar(null),
        );
    }
}
