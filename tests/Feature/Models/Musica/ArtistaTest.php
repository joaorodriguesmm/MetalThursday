<?php

declare(strict_types=1);

namespace Tests\Feature\Models\Musica;

use App\Models\Geografia\OrigemGeografica;
use App\Models\Musica\Artista;
use App\Models\Musica\Genero;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa os contratos persistidos do modelo dos artistas.
 *
 * @since 2.0.0
 */
final class ArtistaTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Confirma que artistas ativos distintos podem possuir o mesmo nome.
     *
     * A identidade do artista é determinada pelo respetivo identificador e não
     * pelo nome apresentado.
     *
     * @since 2.0.0
     */
    #[Test]
    public function permite_artistas_ativos_com_o_mesmo_nome(): void
    {
        $origemGeografica = OrigemGeografica::factory()
            ->create([
                'nome' => 'Portugal',

                'codigo' => 'PT',
            ]);

        $primeiroArtista = Artista::factory()
            ->comNome(
                'Moonspell',
            )
            ->deOrigemGeografica(
                $origemGeografica,
            )
            ->create();

        $segundoArtista = Artista::factory()
            ->comNome(
                'Moonspell',
            )
            ->deOrigemGeografica(
                $origemGeografica,
            )
            ->create();

        self::assertNotSame(
            $primeiroArtista->getKey(),
            $segundoArtista->getKey(),
        );

        self::assertSame(
            2,
            Artista::query()
                ->where(
                    'nome',
                    'Moonspell',
                )
                ->count(),
        );
    }

    /**
     * Confirma que um artista pode existir sem origem geográfica.
     *
     * @since 2.0.0
     */
    #[Test]
    public function permite_artista_sem_origem_geografica(): void
    {
        $artista = Artista::factory()
            ->create([
                'origem_geografica_id' => null,
            ]);

        $artista->refresh();

        self::assertNull(
            $artista->origem_geografica_id,
        );

        self::assertNull(
            $artista->origemGeografica,
        );
    }

    /**
     * Confirma que o modelo não converte silenciosamente valores não textuais.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_nome_nao_textual(): void
    {
        $this->expectException(
            InvalidArgumentException::class,
        );

        $artista = new Artista;

        $artista->nome = 123;
    }

    /**
     * Confirma que o nome não aceita caracteres de controlo.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_caracteres_de_controlo_no_nome(): void
    {
        $this->expectException(
            InvalidArgumentException::class,
        );

        $artista = new Artista;

        $artista->nome = "Artista\nInválido";
    }

    /**
     * Confirma que o rótulo de seleção omite a origem geográfica quando esta não
     * está indicada.
     *
     * @since 2.0.0
     */
    #[Test]
    public function constroi_rotulo_selecao_sem_origem_geografica(): void
    {
        $genero = Genero::factory()
            ->comNome(
                'Heavy Metal',
            )
            ->create();

        $artista = Artista::factory()
            ->comNome(
                'Ghost',
            )
            ->create([
                'origem_geografica_id' => null,
            ]);

        $artista
            ->generos()
            ->attach(
                $genero->getKey(),
            );

        $artista->load([
            'origemGeografica:id,nome',
            'generos:id,nome',
        ]);

        self::assertSame(
            'Ghost · Heavy Metal',
            $artista->obterRotuloSelecao(),
        );
    }
}
