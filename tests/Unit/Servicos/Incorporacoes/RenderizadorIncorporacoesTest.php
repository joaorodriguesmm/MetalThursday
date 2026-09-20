<?php

declare(strict_types=1);

namespace Tests\Unit\Servicos\Incorporacoes;

use App\Enumeracoes\PlataformaLigacao;
use App\Models\MetalThursday\LigacaoSeccaoMetalThursday;
use App\Servicos\Incorporacoes\RenderizadorIncorporacoes;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa a renderização das novas ligações públicas das secções.
 *
 * @since 2.0.0
 */
final class RenderizadorIncorporacoesTest extends TestCase
{
    /**
     * Confirma a incorporação de um vídeo do YouTube e a ligação externa.
     *
     * @since 2.0.0
     */
    #[Test]
    public function youtube_incorpora_video_quando_url_e_compativel(): void
    {
        $conteudo = app(
            RenderizadorIncorporacoes::class,
        )->renderizarLigacao(
            $this->criarLigacao(
                PlataformaLigacao::YouTube,
                'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                true,
            ),
        )->toHtml();

        $this->assertStringContainsString(
            'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ?rel=0',
            $conteudo,
        );

        $this->assertStringContainsString(
            'Abrir no YouTube / YouTube Music',
            $conteudo,
        );
    }

    /**
     * Confirma que uma lista de reprodução é inferida a partir do URL.
     *
     * @since 2.0.0
     */
    #[Test]
    public function youtube_incorpora_lista_reproducao_quando_url_e_compativel(): void
    {
        $conteudo = app(
            RenderizadorIncorporacoes::class,
        )->renderizarLigacao(
            $this->criarLigacao(
                PlataformaLigacao::YouTube,
                'https://www.youtube.com/playlist?list=PL1234567890',
                true,
            ),
        )->toHtml();

        $this->assertStringContainsString(
            'https://www.youtube-nocookie.com/embed/videoseries?list=PL1234567890&amp;rel=0',
            $conteudo,
        );
    }

    /**
     * Confirma a incorporação de uma faixa do Spotify.
     *
     * @since 2.0.0
     */
    #[Test]
    public function spotify_e_incorporado_quando_o_url_e_compativel(): void
    {
        $conteudo = app(
            RenderizadorIncorporacoes::class,
        )->renderizarLigacao(
            $this->criarLigacao(
                PlataformaLigacao::Spotify,
                'https://open.spotify.com/track/11dFghVXANMlKmJXsNCbNl',
                true,
            ),
        )->toHtml();

        $this->assertStringContainsString(
            'https://open.spotify.com/embed/track/11dFghVXANMlKmJXsNCbNl',
            $conteudo,
        );

        $this->assertStringContainsString(
            'allow="autoplay; clipboard-write; encrypted-media; fullscreen; picture-in-picture"',
            $conteudo,
        );

        $this->assertStringContainsString(
            'Abrir no Spotify',
            $conteudo,
        );
    }

    /**
     * Confirma o suporte de URLs regionais produzidos pelo Spotify.
     *
     * @since 2.0.0
     */
    #[Test]
    public function spotify_remove_prefixo_regional_ao_construir_incorporacao(): void
    {
        $conteudo = app(
            RenderizadorIncorporacoes::class,
        )->renderizarLigacao(
            $this->criarLigacao(
                PlataformaLigacao::Spotify,
                'https://open.spotify.com/intl-pt/album/4aawyAB9vmqN3uQ7FjRGTy',
                true,
            ),
        )->toHtml();

        $this->assertStringContainsString(
            'https://open.spotify.com/embed/album/4aawyAB9vmqN3uQ7FjRGTy',
            $conteudo,
        );
    }

    /**
     * Confirma que a opção de incorporação é respeitada.
     *
     * @since 2.0.0
     */
    #[Test]
    public function spotify_nao_e_incorporado_quando_opcao_esta_desativada(): void
    {
        $conteudo = app(
            RenderizadorIncorporacoes::class,
        )->renderizarLigacao(
            $this->criarLigacao(
                PlataformaLigacao::Spotify,
                'https://open.spotify.com/track/11dFghVXANMlKmJXsNCbNl',
                false,
            ),
        )->toHtml();

        $this->assertStringNotContainsString(
            '<iframe',
            $conteudo,
        );

        $this->assertStringContainsString(
            'Abrir no Spotify',
            $conteudo,
        );
    }

    /**
     * Confirma que ligações Spotify não canónicas ficam apenas externas.
     *
     * @since 2.0.0
     */
    #[Test]
    public function spotify_link_curto_fica_apenas_como_ligacao_externa(): void
    {
        $conteudo = app(
            RenderizadorIncorporacoes::class,
        )->renderizarLigacao(
            $this->criarLigacao(
                PlataformaLigacao::Spotify,
                'https://spotify.link/exemplo',
                true,
            ),
        )->toHtml();

        $this->assertStringNotContainsString(
            '<iframe',
            $conteudo,
        );

        $this->assertStringContainsString(
            'https://spotify.link/exemplo',
            $conteudo,
        );
    }

    /**
     * Confirma que um host semelhante não é transformado em incorporação.
     *
     * @since 2.0.0
     */
    #[Test]
    public function spotify_rejeita_host_semelhante_na_renderizacao_defensiva(): void
    {
        $conteudo = app(
            RenderizadorIncorporacoes::class,
        )->renderizarLigacao(
            $this->criarLigacao(
                PlataformaLigacao::Spotify,
                'https://open.spotify.com.example.com/track/11dFghVXANMlKmJXsNCbNl',
                true,
            ),
        )->toHtml();

        $this->assertStringNotContainsString(
            '<iframe',
            $conteudo,
        );
    }

    /**
     * Confirma a incorporação de um álbum do Apple Music.
     *
     * @since 2.0.0
     */
    #[Test]
    public function apple_music_incorpora_album_compativel(): void
    {
        $conteudo = app(
            RenderizadorIncorporacoes::class,
        )->renderizarLigacao(
            $this->criarLigacao(
                PlataformaLigacao::AppleMusic,
                'https://music.apple.com/pt/album/album-teste/1234567890',
                true,
            ),
        )->toHtml();

        $this->assertStringContainsString(
            'https://embed.music.apple.com/pt/album/album-teste/1234567890',
            $conteudo,
        );

        $this->assertStringContainsString(
            'height="450"',
            $conteudo,
        );

        $this->assertStringContainsString(
            'Abrir no Apple Music',
            $conteudo,
        );
    }

    /**
     * Confirma o formato de álbum sem slug.
     *
     * @since 2.0.0
     */
    #[Test]
    public function apple_music_incorpora_album_sem_slug(): void
    {
        $conteudo = app(
            RenderizadorIncorporacoes::class,
        )->renderizarLigacao(
            $this->criarLigacao(
                PlataformaLigacao::AppleMusic,
                'https://music.apple.com/pt/album/1603083637',
                true,
            ),
        )->toHtml();

        $this->assertStringContainsString(
            'https://embed.music.apple.com/pt/album/1603083637',
            $conteudo,
        );
    }

    /**
     * Confirma a música identificada pelo parâmetro `i` de um álbum.
     *
     * @since 2.0.0
     */
    #[Test]
    public function apple_music_preserva_musica_identificada_no_album(): void
    {
        $conteudo = app(
            RenderizadorIncorporacoes::class,
        )->renderizarLigacao(
            $this->criarLigacao(
                PlataformaLigacao::AppleMusic,
                'https://music.apple.com/pt/album/creep/1097862062?i=1097862231',
                true,
            ),
        )->toHtml();

        $this->assertStringContainsString(
            'https://embed.music.apple.com/pt/album/creep/1097862062?i=1097862231',
            $conteudo,
        );

        $this->assertStringContainsString(
            'height="175"',
            $conteudo,
        );
    }

    /**
     * Confirma a incorporação de uma música com URL direto.
     *
     * @since 2.0.0
     */
    #[Test]
    public function apple_music_incorpora_url_direto_de_musica(): void
    {
        $conteudo = app(
            RenderizadorIncorporacoes::class,
        )->renderizarLigacao(
            $this->criarLigacao(
                PlataformaLigacao::AppleMusic,
                'https://music.apple.com/us/song/fear-them/6764201503',
                true,
            ),
        )->toHtml();

        $this->assertStringContainsString(
            'https://embed.music.apple.com/us/song/fear-them/6764201503',
            $conteudo,
        );

        $this->assertStringContainsString(
            'height="175"',
            $conteudo,
        );
    }

    /**
     * Confirma a incorporação de uma lista de reprodução.
     *
     * @since 2.0.0
     */
    #[Test]
    public function apple_music_incorpora_lista_reproducao(): void
    {
        $conteudo = app(
            RenderizadorIncorporacoes::class,
        )->renderizarLigacao(
            $this->criarLigacao(
                PlataformaLigacao::AppleMusic,
                'https://music.apple.com/us/playlist/lista-teste/pl.123456789',
                true,
            ),
        )->toHtml();

        $this->assertStringContainsString(
            'https://embed.music.apple.com/us/playlist/lista-teste/pl.123456789',
            $conteudo,
        );

        $this->assertStringContainsString(
            'height="450"',
            $conteudo,
        );
    }

    /**
     * Confirma que a opção de incorporação do Apple Music é respeitada.
     *
     * @since 2.0.0
     */
    #[Test]
    public function apple_music_nao_incorpora_quando_opcao_esta_desativada(): void
    {
        $conteudo = app(
            RenderizadorIncorporacoes::class,
        )->renderizarLigacao(
            $this->criarLigacao(
                PlataformaLigacao::AppleMusic,
                'https://music.apple.com/pt/album/album-teste/1234567890',
                false,
            ),
        )->toHtml();

        $this->assertStringNotContainsString(
            '<iframe',
            $conteudo,
        );

        $this->assertStringContainsString(
            'Abrir no Apple Music',
            $conteudo,
        );
    }

    /**
     * Confirma que um formato desconhecido fica apenas como ligação externa.
     *
     * @since 2.0.0
     */
    #[Test]
    public function apple_music_formato_desconhecido_fica_apenas_externo(): void
    {
        $conteudo = app(
            RenderizadorIncorporacoes::class,
        )->renderizarLigacao(
            $this->criarLigacao(
                PlataformaLigacao::AppleMusic,
                'https://music.apple.com/us/artist/artista-teste/1234567890',
                true,
            ),
        )->toHtml();

        $this->assertStringNotContainsString(
            '<iframe',
            $conteudo,
        );

        $this->assertStringContainsString(
            'Abrir no Apple Music',
            $conteudo,
        );
    }

    /**
     * Confirma que uma etiqueta personalizada é escapada como texto.
     *
     * @since 2.0.0
     */
    #[Test]
    public function outro_utiliza_etiqueta_personalizada_escapada(): void
    {
        $conteudo = app(
            RenderizadorIncorporacoes::class,
        )->renderizarLigacao(
            $this->criarLigacao(
                PlataformaLigacao::Outro,
                'https://example.com/critica',
                false,
                '<Crítica>',
            ),
        )->toHtml();

        $this->assertStringContainsString(
            '&lt;Crítica&gt;',
            $conteudo,
        );

        $this->assertStringNotContainsString(
            '<Crítica>',
            $conteudo,
        );
    }

    /**
     * Cria uma ligação sem persistência para testes do renderizador.
     *
     * @since 2.0.0
     */
    private function criarLigacao(
        PlataformaLigacao $plataforma,
        string $url,
        bool $incorporar,
        ?string $etiqueta = null,
    ): LigacaoSeccaoMetalThursday {
        $ligacao = new LigacaoSeccaoMetalThursday;

        $ligacao->plataforma = $plataforma;
        $ligacao->url = $url;
        $ligacao->incorporar = $incorporar;
        $ligacao->etiqueta = $etiqueta;

        return $ligacao;
    }
}
