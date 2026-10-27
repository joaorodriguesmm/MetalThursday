<?php

declare(strict_types=1);

namespace Tests\Feature\Servicos\Musica;

use App\Servicos\Musica\ServicoImportacaoArtista;
use App\Servicos\Musica\ServicoMusicBrainz;
use App\Servicos\Musica\ServicoTheAudioDB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ValidacaoTextosAnosIntegracoesTest extends TestCase
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
    public function musicbrainz_rejeita_nome_com_caracteres_de_controlo(): void
    {
        $this->simularMusicBrainz([
            'name' => "Artista\x00Invalido",
        ]);

        $resultado = app(ServicoMusicBrainz::class)
            ->pesquisarArtistas('Artista');

        self::assertSame([], $resultado);
    }

    #[Test]
    public function musicbrainz_ignora_anos_fora_do_intervalo_permitido(): void
    {
        $this->simularMusicBrainz([
            'life-span' => [
                'begin' => '0999',
                'end' => '9999',
            ],
        ]);

        $resultado = app(ServicoMusicBrainz::class)
            ->pesquisarArtistas('Artista');

        self::assertCount(1, $resultado);
        self::assertNull($resultado[0]['ano_inicio']);
        self::assertNull($resultado[0]['ano_fim']);
    }

    #[Test]
    public function theaudiodb_ignora_anos_fora_do_intervalo_permitido(): void
    {
        $this->simularTheAudioDB([
            'intFormedYear' => '0000',
            'intDiedYear' => '9999',
        ]);

        $resultado = app(ServicoTheAudioDB::class)
            ->obterArtistaPorMusicBrainz(self::MBID);

        self::assertIsArray($resultado);
        self::assertNull($resultado['ano_inicio']);
        self::assertNull($resultado['ano_fim']);
    }

    #[Test]
    public function theaudiodb_ignora_pais_com_caracteres_de_controlo(): void
    {
        $this->simularTheAudioDB([
            'strCountry' => "Portugal\x00Invalido",
            'strCountryCode' => 'PT',
        ]);

        $resultado = app(ServicoTheAudioDB::class)
            ->obterArtistaPorMusicBrainz(self::MBID);

        self::assertIsArray($resultado);
        self::assertNull($resultado['pais']);
        self::assertSame('PT', $resultado['codigo_pais']);
    }

    #[Test]
    public function proposta_nao_utiliza_ano_final_anterior_ao_inicial(): void
    {
        Http::fake([
            'https://musicbrainz.org/ws/2/artist/'.self::MBID.'*' => Http::response([
                'id' => self::MBID,
                'name' => 'Artista de teste',
                'life-span' => [
                    'begin' => '2004',
                    'end' => '1998',
                    'ended' => true,
                ],
            ]),
            'https://www.theaudiodb.com/api/v1/json/123/artist-mb.php*' => Http::response([
                'artists' => null,
            ]),
        ]);

        $resultado = app(ServicoImportacaoArtista::class)
            ->obterProposta(self::MBID);

        self::assertSame(
            2004,
            $resultado['ano_inicio_atividade'],
        );

        self::assertNull(
            $resultado['ano_fim_atividade'],
        );
    }

    #[Test]
    public function musicbrainz_preserva_anos_nos_limites_permitidos(): void
    {
        $anoAtual = now()->year;

        $this->simularMusicBrainz([
            'life-span' => [
                'begin' => '1000-01-01',
                'end' => (string) $anoAtual,
            ],
        ]);

        $resultado = app(ServicoMusicBrainz::class)
            ->pesquisarArtistas('Artista');

        self::assertCount(1, $resultado);
        self::assertSame(1000, $resultado[0]['ano_inicio']);
        self::assertSame($anoAtual, $resultado[0]['ano_fim']);
    }

    #[Test]
    public function theaudiodb_preserva_anos_nos_limites_permitidos(): void
    {
        $anoAtual = now()->year;

        $this->simularTheAudioDB([
            'intFormedYear' => '1000',
            'intDiedYear' => (string) $anoAtual,
        ]);

        $resultado = app(ServicoTheAudioDB::class)
            ->obterArtistaPorMusicBrainz(self::MBID);

        self::assertIsArray($resultado);
        self::assertSame(1000, $resultado['ano_inicio']);
        self::assertSame($anoAtual, $resultado['ano_fim']);
    }

    #[Test]
    public function theaudiodb_preserva_paragrafos_e_tabulacoes_na_biografia(): void
    {
        $biografia = "Primeira linha.\nSegunda\tlinha.";

        $this->simularTheAudioDB([
            'strBiographyPT' => $biografia,
        ]);

        $resultado = app(ServicoTheAudioDB::class)
            ->obterArtistaPorMusicBrainz(self::MBID);

        self::assertIsArray($resultado);
        self::assertSame($biografia, $resultado['biografia']);
        self::assertSame('pt', $resultado['idioma_biografia']);
    }

    #[Test]
    public function musicbrainz_ignora_textos_opcionais_invalidos(): void
    {
        $this->simularMusicBrainz([
            'disambiguation' => "Nota\x00Invalida",
            'country' => 'PT',
            'area' => [
                'name' => "Lisboa\x1FInvalida",
            ],
        ]);

        $resultado = app(ServicoMusicBrainz::class)
            ->pesquisarArtistas('Artista');

        self::assertCount(1, $resultado);
        self::assertSame('Artista de teste', $resultado[0]['nome']);
        self::assertNull($resultado[0]['desambiguacao']);
        self::assertNull($resultado[0]['area']);
        self::assertSame('PT', $resultado[0]['codigo_pais']);
    }

    #[Test]
    public function proposta_rejeita_periodo_invertido_entre_fontes(): void
    {
        Http::fake([
            'https://musicbrainz.org/ws/2/artist/'.self::MBID.'*' => Http::response([
                'id' => self::MBID,
                'name' => 'Artista de teste',
                'life-span' => [
                    'begin' => '2004',
                ],
            ]),
            'https://www.theaudiodb.com/api/v1/json/123/artist-mb.php*' => Http::response([
                'artists' => [
                    [
                        'strMusicBrainzID' => self::MBID,
                        'strArtist' => 'Artista de teste',
                        'intDiedYear' => '1998',
                    ],
                ],
            ]),
        ]);

        $resultado = app(ServicoImportacaoArtista::class)
            ->obterProposta(self::MBID);

        self::assertSame(2004, $resultado['ano_inicio_atividade']);
        self::assertNull($resultado['ano_fim_atividade']);
    }

    /**
     * Simula uma pesquisa de artistas no MusicBrainz.
     *
     * @param  array<string, mixed>  $atributos
     */
    private function simularMusicBrainz(array $atributos): void
    {
        Http::fake([
            'https://musicbrainz.org/ws/2/artist/*' => Http::response([
                'artists' => [
                    array_merge(
                        [
                            'id' => self::MBID,
                            'name' => 'Artista de teste',
                        ],
                        $atributos,
                    ),
                ],
            ]),
        ]);
    }

    /**
     * Simula uma resposta de artista do TheAudioDB.
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
}
