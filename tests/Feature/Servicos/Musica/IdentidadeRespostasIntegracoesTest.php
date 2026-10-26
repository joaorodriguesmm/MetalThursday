<?php

declare(strict_types=1);

namespace Tests\Feature\Servicos\Musica;

use App\Servicos\Musica\ServicoMusicBrainz;
use App\Servicos\Musica\ServicoTheAudioDB;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

final class IdentidadeRespostasIntegracoesTest extends TestCase
{
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
    public function musicbrainz_rejeita_artista_com_mbid_diferente_do_solicitado(): void
    {
        $solicitado = '65f4f0c5-ef9e-490c-aee3-909e7ae6b2ab';
        $outro = '70d5e4d1-31aa-498f-9e9b-177d9349681f';

        Http::fake([
            'https://musicbrainz.org/ws/2/artist/'.$solicitado.'*' => Http::response([
                'id' => $outro,
                'name' => 'Artista incorreto',
            ]),
        ]);

        $excecao = null;

        try {
            app(ServicoMusicBrainz::class)->obterArtista(
                $solicitado,
            );
        } catch (RuntimeException $erro) {
            $excecao = $erro;
        }

        Http::assertSentCount(1);

        self::assertInstanceOf(
            RuntimeException::class,
            $excecao,
        );

        self::assertStringContainsString(
            'identidade diferente',
            $excecao->getMessage(),
        );
    }

    #[Test]
    public function theaudiodb_ignora_artista_com_mbid_diferente(): void
    {
        $solicitado = '65f4f0c5-ef9e-490c-aee3-909e7ae6b2ab';
        $outro = '70d5e4d1-31aa-498f-9e9b-177d9349681f';

        Http::fake([
            'https://www.theaudiodb.com/api/v1/json/123/artist-mb.php*' => Http::response([
                'artists' => [
                    [
                        'idArtist' => '9001',
                        'strArtist' => 'Artista incorreto',
                        'strMusicBrainzID' => $outro,
                        'strBiographyPT' => 'Biografia incorreta.',
                    ],
                ],
            ]),
        ]);

        $resultado = app(ServicoTheAudioDB::class)
            ->obterArtistaPorMusicBrainz($solicitado);

        self::assertNull($resultado);

        Http::assertSentCount(1);
    }

    #[Test]
    public function theaudiodb_seleciona_identidade_correta_na_segunda_posicao(): void
    {
        $solicitado = '65f4f0c5-ef9e-490c-aee3-909e7ae6b2ab';
        $outro = '70d5e4d1-31aa-498f-9e9b-177d9349681f';

        Http::fake([
            'https://www.theaudiodb.com/api/v1/json/123/artist-mb.php*' => Http::response([
                'artists' => [
                    [
                        'idArtist' => '9001',
                        'strArtist' => 'Artista incorreto',
                        'strMusicBrainzID' => $outro,
                        'strBiographyPT' => 'Biografia incorreta.',
                    ],
                    [
                        'idArtist' => '9002',
                        'strArtist' => 'Artista correto',
                        'strMusicBrainzID' => $solicitado,
                        'strBiographyPT' => 'Biografia correta.',
                    ],
                ],
            ]),
        ]);

        $resultado = app(ServicoTheAudioDB::class)
            ->obterArtistaPorMusicBrainz($solicitado);

        self::assertIsArray($resultado);

        self::assertSame(
            $solicitado,
            $resultado['mbid'],
        );

        self::assertSame(
            'Artista correto',
            $resultado['nome'],
        );

        self::assertSame(
            'Biografia correta.',
            $resultado['biografia'],
        );

        Http::assertSentCount(1);
    }

    #[Test]
    public function theaudiodb_rejeita_artista_sem_mbid(): void
    {
        Http::fake([
            'https://www.theaudiodb.com/api/v1/json/123/artist-mb.php*' => Http::response([
                'artists' => [
                    [
                        'strArtist' => 'Artista sem identidade',
                        'strBiographyPT' => 'Dados nao verificaveis.',
                    ],
                ],
            ]),
        ]);

        $resultado = app(ServicoTheAudioDB::class)
            ->obterArtistaPorMusicBrainz(
                '65f4f0c5-ef9e-490c-aee3-909e7ae6b2ab',
            );

        self::assertNull($resultado);
        Http::assertSentCount(1);
    }

    #[Test]
    public function theaudiodb_aceita_mbid_com_maiusculas(): void
    {
        $mbid = '65f4f0c5-ef9e-490c-aee3-909e7ae6b2ab';

        Http::fake([
            'https://www.theaudiodb.com/api/v1/json/123/artist-mb.php*' => Http::response([
                'artists' => [
                    [
                        'strArtist' => 'Artista correto',
                        'strMusicBrainzID' => strtoupper($mbid),
                    ],
                ],
            ]),
        ]);

        $resultado = app(ServicoTheAudioDB::class)
            ->obterArtistaPorMusicBrainz($mbid);

        self::assertIsArray($resultado);
        self::assertSame('Artista correto', $resultado['nome']);
        Http::assertSentCount(1);
    }

    #[Test]
    public function musicbrainz_aceita_mbid_solicitado_com_maiusculas(): void
    {
        $mbid = '65f4f0c5-ef9e-490c-aee3-909e7ae6b2ab';

        Http::fake([
            'https://musicbrainz.org/ws/2/artist/'.$mbid.'*' => Http::response([
                'id' => $mbid,
                'name' => 'Artista correto',
            ]),
        ]);

        $resultado = app(ServicoMusicBrainz::class)
            ->obterArtista(strtoupper($mbid));

        self::assertSame($mbid, $resultado['mbid']);
        Http::assertSentCount(1);
    }
}
