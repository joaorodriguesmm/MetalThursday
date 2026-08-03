<?php

declare(strict_types=1);

namespace Tests\Feature\Models\MetalThursday;

use App\Models\MetalThursday\Edicao;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa os contratos do modelo das edições.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
final class EdicaoTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Confirma a normalização do nome e da ligação da compilação.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function normaliza_nome_e_ligacao_compilacao(): void
    {
        $edicao = new Edicao;

        $edicao->nome =
            '  Edição   de janeiro  ';

        $edicao->ligacao_compilacao =
            '  https://example.com/compilacao  ';

        self::assertSame(
            'Edição de janeiro',
            $edicao->nome,
        );

        self::assertSame(
            'https://example.com/compilacao',
            $edicao->ligacao_compilacao,
        );

        $edicao->ligacao_compilacao =
            '   ';

        self::assertNull(
            $edicao->ligacao_compilacao,
        );
    }

    /**
     * Confirma que a ligação não aceita valores não textuais.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function rejeita_ligacao_compilacao_nao_textual(): void
    {
        $this->expectException(
            InvalidArgumentException::class,
        );

        $edicao = new Edicao;

        $edicao->ligacao_compilacao = 123;
    }

    /**
     * Confirma que a ligação não aceita credenciais incorporadas.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function rejeita_ligacao_compilacao_com_credenciais(): void
    {
        $this->expectException(
            InvalidArgumentException::class,
        );

        $edicao = new Edicao;

        $edicao->ligacao_compilacao =
            'https://utilizador:segredo@example.com/compilacao';
    }

    /**
     * Confirma que a ligação não aceita barras invertidas.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function rejeita_ligacao_compilacao_com_barra_invertida(): void
    {
        $this->expectException(
            InvalidArgumentException::class,
        );

        $edicao = new Edicao;

        $edicao->ligacao_compilacao =
            'https://example.com\\compilacao';
    }

    /**
     * Confirma que a data final não pode anteceder a data inicial.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function rejeita_periodo_invertido(): void
    {
        $this->expectException(
            InvalidArgumentException::class,
        );

        $edicao = new Edicao;

        $edicao->nome =
            'Edição de janeiro';

        $edicao->data_inicio =
            '2026-01-31';

        $edicao->data_fim =
            '2026-01-01';

        $edicao->saveOrFail();
    }
}
