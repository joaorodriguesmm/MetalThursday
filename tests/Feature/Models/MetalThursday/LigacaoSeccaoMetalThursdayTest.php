<?php

declare(strict_types=1);

namespace Tests\Feature\Models\MetalThursday;

use App\Enumeracoes\PlataformaLigacao;
use App\Models\MetalThursday\LigacaoSeccaoMetalThursday;
use App\Models\MetalThursday\SeccaoMetalThursday;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa o contrato do modelo das ligações de secções MetalThursday.
 *
 * @since 2.0.0
 */
final class LigacaoSeccaoMetalThursdayTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Confirma que a plataforma é calculada no servidor e que uma ligação
     * personalizada nunca permanece marcada para incorporação.
     *
     * @since 2.0.0
     */
    #[Test]
    public function deteta_plataforma_e_normaliza_incorporacao_ao_persistir(): void
    {
        $seccao = SeccaoMetalThursday::factory()
            ->create();

        $youtube = LigacaoSeccaoMetalThursday::factory()
            ->for(
                $seccao,
                'seccaoMetalThursday',
            )
            ->create([
                'url' => 'https://music.youtube.com/watch?v=abc123',
                'incorporar' => true,
            ]);

        self::assertSame(
            PlataformaLigacao::YouTube,
            $youtube->plataforma,
        );
        self::assertTrue(
            $youtube->incorporar,
        );

        $outra = LigacaoSeccaoMetalThursday::factory()
            ->for(
                $seccao,
                'seccaoMetalThursday',
            )
            ->create([
                'url' => 'https://bandcamp.com/album/exemplo',
                'etiqueta' => '  Comprar   no Bandcamp  ',
                'incorporar' => true,
                'ordem' => 2,
            ]);

        self::assertSame(
            PlataformaLigacao::Outro,
            $outra->plataforma,
        );
        self::assertFalse(
            $outra->incorporar,
        );
        self::assertSame(
            'Comprar no Bandcamp',
            $outra->etiqueta,
        );
        self::assertTrue(
            $outra->seccaoMetalThursday->is(
                $seccao,
            ),
        );
    }

    /**
     * Confirma que URLs inseguros ou não absolutos são rejeitados pelo modelo.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_url_invalido(): void
    {
        $this->expectException(
            InvalidArgumentException::class,
        );

        LigacaoSeccaoMetalThursday::factory()
            ->make([
                'url' => 'javascript:alert(1)',
            ]);
    }

    /**
     * Confirma que a secção expõe as ligações pela ordem funcional definida.
     *
     * @since 2.0.0
     */
    #[Test]
    public function secao_obtem_ligacoes_ordenadas(): void
    {
        $seccao = SeccaoMetalThursday::factory()
            ->create();

        LigacaoSeccaoMetalThursday::factory()
            ->for(
                $seccao,
                'seccaoMetalThursday',
            )
            ->create([
                'url' => 'https://example.com/segunda',
                'ordem' => 2,
            ]);

        LigacaoSeccaoMetalThursday::factory()
            ->for(
                $seccao,
                'seccaoMetalThursday',
            )
            ->create([
                'url' => 'https://example.com/primeira',
                'ordem' => 1,
            ]);

        self::assertSame(
            [
                'https://example.com/primeira',
                'https://example.com/segunda',
            ],
            $seccao
                ->ligacoes()
                ->pluck(
                    'url',
                )
                ->all(),
        );
    }
}
