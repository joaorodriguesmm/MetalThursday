<?php

declare(strict_types=1);

namespace Tests\Feature\Models\Musica;

use App\Models\Geografia\OrigemGeografica;
use App\Models\Musica\Banda;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa os contratos persistidos do modelo das bandas.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
final class BandaTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Confirma que a coluna gerada de unicidade permanece interna.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function omite_nome_ativo_da_serializacao(): void
    {
        $origemGeografica = OrigemGeografica::factory()
            ->create([
                'nome' => 'Portugal',

                'codigo' => 'PT',
            ]);

        $banda = Banda::factory()
            ->comNome(
                'Moonspell',
            )
            ->deOrigemGeografica(
                $origemGeografica,
            )
            ->create();

        $bandaPersistida = Banda::query()
            ->findOrFail(
                $banda->getKey(),
            );

        self::assertSame(
            'Moonspell',
            $bandaPersistida->nome_ativo,
        );

        self::assertArrayNotHasKey(
            'nome_ativo',
            $bandaPersistida->toArray(),
        );
    }

    /**
     * Confirma que o modelo não converte silenciosamente valores não textuais.
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

        $banda = new Banda;

        $banda->nome = 123;
    }

    /**
     * Confirma que o nome não aceita caracteres de controlo.
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

        $banda = new Banda;

        $banda->nome = "Banda\nInválida";
    }
}
