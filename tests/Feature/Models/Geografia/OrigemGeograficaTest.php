<?php

declare(strict_types=1);

namespace Tests\Feature\Models\Geografia;

use App\Models\Geografia\OrigemGeografica;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa os contratos persistidos das origens geográficas.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
final class OrigemGeograficaTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Confirma que a factory cria uma origem com dados conhecidos.
     *
     * Este teste protege contra a utilização de closures estáticas em
     * estados de factories.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function factory_cria_origem_com_dados_conhecidos(): void
    {
        $origemGeografica = OrigemGeografica::factory()
            ->comDados(
                '  Reino Unido  ',
                ' gb ',
            )
            ->create();

        self::assertSame(
            'Reino Unido',
            $origemGeografica->nome,
        );

        self::assertSame(
            'GB',
            $origemGeografica->codigo,
        );

        $this->assertDatabaseHas(
            'origens_geograficas',
            [
                'id' => $origemGeografica->getKey(),

                'nome' => 'Reino Unido',

                'codigo' => 'GB',
            ],
        );
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

        $origemGeografica = new OrigemGeografica;

        $origemGeografica->nome = 123;
    }

    /**
     * Confirma que o código não converte valores não textuais.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function rejeita_codigo_nao_textual(): void
    {
        $this->expectException(
            InvalidArgumentException::class,
        );

        $origemGeografica = new OrigemGeografica;

        $origemGeografica->codigo = 123;
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

        $origemGeografica = new OrigemGeografica;

        $origemGeografica->nome = "Reino\nUnido";
    }

    /**
     * Confirma que o código rejeita hífenes exteriores ou consecutivos.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function rejeita_codigo_com_formato_invalido(): void
    {
        $this->expectException(
            InvalidArgumentException::class,
        );

        $origemGeografica = new OrigemGeografica;

        $origemGeografica->codigo = 'GB--ENG';
    }
}
