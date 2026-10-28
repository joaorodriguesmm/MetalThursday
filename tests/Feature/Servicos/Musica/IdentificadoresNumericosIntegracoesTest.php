<?php

declare(strict_types=1);

namespace Tests\Feature\Servicos\Musica;

use App\Servicos\Musica\ServicoMusicBrainz;
use App\Servicos\Musica\ServicoTheAudioDB;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class IdentificadoresNumericosIntegracoesTest extends TestCase
{
    private const MBID = '65f4f0c5-ef9e-490c-aee3-909e7ae6b2ab';

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();

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
    public function musicbrainz_rejeita_discogs_id_acima_do_limite_inteiro(): void
    {
        $artista = $this->obterMusicBrainz(
            str_repeat('9', 30),
        );

        self::assertNull($artista['discogs_id']);
    }

    #[Test]
    public function musicbrainz_preserva_discogs_id_valido(): void
    {
        $artista = $this->obterMusicBrainz('18839');

        self::assertSame(18839, $artista['discogs_id']);
    }

    #[Test]
    #[DataProvider('identificadoresInvalidosTheAudioDB')]
    public function theaudiodb_rejeita_identificador_numerico_invalido(
        string $identificador,
    ): void {
        $artista = $this->obterTheAudioDB($identificador);

        self::assertIsArray($artista);
        self::assertNull($artista['id']);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function identificadoresInvalidosTheAudioDB(): array
    {
        return [
            'zero' => ['0'],
            'negativo' => ['-1'],
            'decimal' => ['1.5'],
            'notacao_cientifica' => ['1e3'],
            'excessivo' => [str_repeat('9', 30)],
        ];
    }

    #[Test]
    public function theaudiodb_preserva_identificador_valido(): void
    {
        $artista = $this->obterTheAudioDB('12345');

        self::assertIsArray($artista);
        self::assertSame(12345, $artista['id']);
    }

    #[Test]
    public function musicbrainz_aceita_discogs_id_com_zeros_a_esquerda(): void
    {
        $artista = $this->obterMusicBrainz('00018839');

        self::assertSame(18839, $artista['discogs_id']);
    }

    #[Test]
    public function musicbrainz_aceita_discogs_id_no_limite_inteiro(): void
    {
        $artista = $this->obterMusicBrainz((string) PHP_INT_MAX);

        self::assertSame(PHP_INT_MAX, $artista['discogs_id']);
    }

    #[Test]
    public function theaudiodb_aceita_id_com_zeros_a_esquerda(): void
    {
        $artista = $this->obterTheAudioDB('00012345');

        self::assertIsArray($artista);
        self::assertSame(12345, $artista['id']);
    }

    #[Test]
    public function theaudiodb_aceita_id_no_limite_inteiro(): void
    {
        $artista = $this->obterTheAudioDB((string) PHP_INT_MAX);

        self::assertIsArray($artista);
        self::assertSame(PHP_INT_MAX, $artista['id']);
    }

    #[Test]
    public function musicbrainz_ignora_discogs_invalido_e_aceita_o_seguinte(): void
    {
        Http::fake([
            'https://musicbrainz.org/ws/2/artist/'.self::MBID.'*' => Http::response([
                'id' => self::MBID,
                'name' => 'Artista de teste',
                'relations' => [
                    [
                        'type' => 'discogs',
                        'url' => [
                            'resource' => 'https://www.discogs.com/artist/'
                                .str_repeat('9', 30),
                        ],
                    ],
                    [
                        'type' => 'discogs',
                        'url' => [
                            'resource' => 'https://www.discogs.com/artist/18839',
                        ],
                    ],
                ],
            ]),
        ]);

        $artista = app(ServicoMusicBrainz::class)
            ->obterArtista(self::MBID);

        self::assertSame(18839, $artista['discogs_id']);
        self::assertCount(2, $artista['relacoes']);
    }

    /**
     * @return array<string, mixed>
     */
    private function obterMusicBrainz(string $identificador): array
    {
        Http::fake([
            'https://musicbrainz.org/ws/2/artist/'.self::MBID.'*' => Http::response([
                'id' => self::MBID,
                'name' => 'Artista de teste',
                'relations' => [
                    [
                        'type' => 'discogs',
                        'url' => [
                            'resource' => 'https://www.discogs.com/artist/'
                                .$identificador,
                        ],
                    ],
                ],
            ]),
        ]);

        return app(ServicoMusicBrainz::class)
            ->obterArtista(self::MBID);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function obterTheAudioDB(string $identificador): ?array
    {
        Http::fake([
            'https://www.theaudiodb.com/api/v1/json/123/artist-mb.php*' => Http::response([
                'artists' => [
                    [
                        'strMusicBrainzID' => self::MBID,
                        'strArtist' => 'Artista de teste',
                        'idArtist' => $identificador,
                    ],
                ],
            ]),
        ]);

        return app(ServicoTheAudioDB::class)
            ->obterArtistaPorMusicBrainz(self::MBID);
    }
}
