<?php

declare(strict_types=1);

namespace Tests\Feature\Servicos\Musica;

use App\Models\Musica\Artista;
use App\Servicos\Musica\ServicoMusicBrainz;
use App\Servicos\Musica\ServicoTheAudioDB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

final class LimitesMetadadosIntegracoesTest extends TestCase
{
    use RefreshDatabase;

    private const MBID = '65f4f0c5-ef9e-490c-aee3-909e7ae6b2ab';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('musicbrainz.base_url', 'https://musicbrainz.org');
        config()->set('musicbrainz.user_agent', 'MetalThursdayTest/2.0');
        config()->set('musicbrainz.tentativas', 1);
        config()->set('musicbrainz.intervalo_repeticao_ms', 0);
        config()->set('musicbrainz.intervalo_minimo_pedidos_ms', 0);

        config()->set('theaudiodb.base_url', 'https://www.theaudiodb.com');
        config()->set('theaudiodb.api_key', '123');
        config()->set('theaudiodb.tentativas', 1);
        config()->set('theaudiodb.intervalo_repeticao_ms', 0);
        config()->set('theaudiodb.intervalo_minimo_pedidos_ms', 0);
    }

    #[Test]
    public function musicbrainz_ignora_resultado_com_nome_demasiado_longo(): void
    {
        Http::fake([
            'https://musicbrainz.org/ws/2/artist/*' => Http::response([
                'artists' => [
                    [
                        'id' => self::MBID,
                        'name' => str_repeat(
                            'A',
                            Artista::COMPRIMENTO_MAXIMO_NOME + 1,
                        ),
                    ],
                ],
            ]),
        ]);

        $resultado = app(ServicoMusicBrainz::class)
            ->pesquisarArtistas('Artista');

        self::assertSame([], $resultado);
        Http::assertSentCount(1);
    }

    #[Test]
    public function musicbrainz_rejeita_ficha_com_nome_demasiado_longo(): void
    {
        Http::fake([
            'https://musicbrainz.org/ws/2/artist/'.self::MBID.'*' => Http::response([
                'id' => self::MBID,
                'name' => str_repeat(
                    'A',
                    Artista::COMPRIMENTO_MAXIMO_NOME + 1,
                ),
            ]),
        ]);

        $this->expectException(RuntimeException::class);

        app(ServicoMusicBrainz::class)
            ->obterArtista(self::MBID);
    }

    #[Test]
    public function theaudiodb_usa_biografia_inglesa_quando_portuguesa_excede_limite(): void
    {
        $this->simularTheAudioDB([
            'strBiographyPT' => str_repeat(
                'A',
                Artista::COMPRIMENTO_MAXIMO_BIOGRAFIA + 1,
            ),
            'strBiographyEN' => 'Biografia válida em inglês.',
        ]);

        $resultado = $this->obterTheAudioDB();

        self::assertSame(
            'Biografia válida em inglês.',
            $resultado['biografia'],
        );

        self::assertSame(
            'en',
            $resultado['idioma_biografia'],
        );
    }

    #[Test]
    public function theaudiodb_rejeita_biografia_com_caracteres_de_controlo(): void
    {
        $this->simularTheAudioDB([
            'strBiographyPT' => 'Texto'.chr(0).'inválido',
        ]);

        $resultado = $this->obterTheAudioDB();

        self::assertNull($resultado['biografia']);
        self::assertNull($resultado['idioma_biografia']);
    }

    #[Test]
    public function theaudiodb_rejeita_imagem_acima_do_limite(): void
    {
        $this->simularTheAudioDB([
            'strArtistThumb' => 'https://cdn.example.com/'
                .str_repeat(
                    'a',
                    Artista::COMPRIMENTO_MAXIMO_URL_IMAGEM,
                ),
        ]);

        $resultado = $this->obterTheAudioDB();

        self::assertNull($resultado['imagem']);
    }

    #[Test]
    public function theaudiodb_rejeita_ligacao_acima_do_limite(): void
    {
        $this->simularTheAudioDB([
            'strWebsite' => 'https://metallica.bandcamp.com/'
                .str_repeat('a', 2050),
        ]);

        $resultado = $this->obterTheAudioDB();

        self::assertSame([], $resultado['ligacoes']);
    }

    #[Test]
    public function biografia_com_multibyte_dentro_do_limite_de_caracteres_e_persistida(): void
    {
        $biografia = str_repeat('á', 40000);

        self::assertSame(40000, mb_strlen($biografia));

        self::assertGreaterThan(
            65535,
            strlen($biografia),
        );

        $artista = Artista::factory()->create([
            'biografia' => $biografia,
        ]);

        self::assertSame(
            $biografia,
            $artista->refresh()->biografia,
        );
    }

    /**
     * Simula uma resposta da API complementar.
     *
     * @param  array<string, mixed>  $atributos
     */
    private function simularTheAudioDB(array $atributos): void
    {
        Http::fake([
            'https://www.theaudiodb.com/api/v1/json/123/artist-mb.php*' => Http::response([
                'artists' => [
                    array_merge(
                        [
                            'strMusicBrainzID' => self::MBID,
                            'strArtist' => 'Artista de teste',
                        ],
                        $atributos,
                    ),
                ],
            ]),
        ]);
    }

    /**
     * Obtém os dados normalizados da API complementar.
     *
     * @return array<string, mixed>
     */
    private function obterTheAudioDB(): array
    {
        $resultado = app(ServicoTheAudioDB::class)
            ->obterArtistaPorMusicBrainz(self::MBID);

        self::assertIsArray($resultado);

        Http::assertSentCount(1);

        return $resultado;
    }
}
