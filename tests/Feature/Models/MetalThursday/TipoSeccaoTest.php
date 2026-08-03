<?php

declare(strict_types=1);

namespace Tests\Feature\Models\MetalThursday;

use App\Models\MetalThursday\TipoSeccao;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa os contratos do modelo dos tipos de secção.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
final class TipoSeccaoTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Confirma que os campos textuais são normalizados.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function normaliza_campos_textuais(): void
    {
        $tipoSeccao = new TipoSeccao;

        $tipoSeccao->identificador =
            '  FAIXA_DESTAQUE  ';

        $tipoSeccao->nome =
            '  Faixa   em destaque  ';

        $tipoSeccao->descricao =
            '  Apresenta   uma faixa escolhida.  ';

        self::assertSame(
            'faixa_destaque',
            $tipoSeccao->identificador,
        );

        self::assertSame(
            'Faixa em destaque',
            $tipoSeccao->nome,
        );

        self::assertSame(
            'Apresenta uma faixa escolhida.',
            $tipoSeccao->descricao,
        );
    }

    /**
     * Confirma que o identificador não converte valores não textuais.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function rejeita_identificador_nao_textual(): void
    {
        $this->expectException(
            InvalidArgumentException::class,
        );

        $tipoSeccao = new TipoSeccao;

        $tipoSeccao->identificador = 123;
    }

    /**
     * Confirma que o nome não converte valores não textuais.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function rejeita_nome_nao_textual(): void
    {
        $this->expectException(
            InvalidArgumentException::class,
        );

        $tipoSeccao = new TipoSeccao;

        $tipoSeccao->nome = 123;
    }

    /**
     * Confirma que a descrição não converte valores não textuais.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function rejeita_descricao_nao_textual(): void
    {
        $this->expectException(
            InvalidArgumentException::class,
        );

        $tipoSeccao = new TipoSeccao;

        $tipoSeccao->descricao = 123;
    }

    /**
     * Confirma que o nome rejeita caracteres de controlo.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function rejeita_caracteres_de_controlo_no_nome(): void
    {
        $this->expectException(
            InvalidArgumentException::class,
        );

        $tipoSeccao = new TipoSeccao;

        $tipoSeccao->nome = "Faixa\nem destaque";
    }

    /**
     * Confirma que a descrição rejeita caracteres de controlo.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function rejeita_caracteres_de_controlo_na_descricao(): void
    {
        $this->expectException(
            InvalidArgumentException::class,
        );

        $tipoSeccao = new TipoSeccao;

        $tipoSeccao->descricao =
            "Descrição\tinválida";
    }
}
