<?php

declare(strict_types=1);

namespace Tests\Feature\Servicos\MetalThursday;

use App\Enumeracoes\PlataformaLigacao;
use App\Servicos\MetalThursday\ServicoLigacoesSecaoMetalThursday;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa a identificação das plataformas das ligações de uma secção.
 *
 * @since 2.0.0
 */
final class ServicoLigacoesSecaoMetalThursdayTest extends TestCase
{
    /**
     * Confirma a detecção dos serviços suportados e dos respectivos
     * subdomínios relevantes.
     *
     * @since 2.0.0
     */
    #[Test]
    public function deteta_plataformas_pelo_dominio_do_url(): void
    {
        $servico = app(
            ServicoLigacoesSecaoMetalThursday::class,
        );

        $casos = [
            'https://open.spotify.com/track/abc123' => PlataformaLigacao::Spotify,
            'https://spotify.link/abc123' => PlataformaLigacao::Spotify,
            'https://music.apple.com/pt/album/exemplo/123' => PlataformaLigacao::AppleMusic,
            'https://www.youtube.com/watch?v=abc123' => PlataformaLigacao::YouTube,
            'https://music.youtube.com/watch?v=abc123' => PlataformaLigacao::YouTube,
            'https://youtu.be/abc123' => PlataformaLigacao::YouTube,
            'https://bandcamp.com/exemplo' => PlataformaLigacao::Outro,
        ];

        foreach ($casos as $url => $plataformaEsperada) {
            self::assertSame(
                $plataformaEsperada,
                $servico->detetarPlataforma(
                    $url,
                ),
                sprintf(
                    'A plataforma do URL %s não foi detectada correctamente.',
                    $url,
                ),
            );
        }
    }

    /**
     * Confirma que nomes de serviços dentro de outros domínios não são
     * classificados como plataformas reconhecidas.
     *
     * @since 2.0.0
     */
    #[Test]
    public function nao_aceita_dominios_enganadores_como_plataformas_conhecidas(): void
    {
        $servico = app(
            ServicoLigacoesSecaoMetalThursday::class,
        );

        foreach (
            [
                'https://youtube.com.exemplo.test/video',
                'https://spotify.com.exemplo.test/faixa',
                'https://music.apple.com.exemplo.test/album',
                'https://youtu.be.exemplo.test/video',
            ] as $url
        ) {
            self::assertSame(
                PlataformaLigacao::Outro,
                $servico->detetarPlataforma(
                    $url,
                ),
            );
        }
    }

    /**
     * Confirma a normalização do contrato persistível e a desactivação da
     * incorporação para plataformas personalizadas.
     *
     * @since 2.0.0
     */
    #[Test]
    public function normaliza_lista_de_ligacoes(): void
    {
        $servico = app(
            ServicoLigacoesSecaoMetalThursday::class,
        );

        $ligacoes = $servico->normalizarDados([
            [
                'url' => 'https://open.spotify.com/track/abc123',
                'etiqueta' => null,
                'incorporar' => '1',
            ],
            [
                'url' => 'https://exemplo.test/critica',
                'etiqueta' => '  Ler   crítica  ',
                'incorporar' => true,
            ],
        ]);

        self::assertSame(
            [
                [
                    'etiqueta' => null,
                    'url' => 'https://open.spotify.com/track/abc123',
                    'incorporar' => true,
                ],
                [
                    'etiqueta' => 'Ler crítica',
                    'url' => 'https://exemplo.test/critica',
                    'incorporar' => false,
                ],
            ],
            $ligacoes,
        );
    }

    /**
     * Confirma que uma nova ligação personalizada exige uma etiqueta legível.
     *
     * @since 2.0.0
     */
    #[Test]
    public function exige_etiqueta_numa_ligacao_personalizada(): void
    {
        $this->expectException(
            \InvalidArgumentException::class,
        );

        app(
            ServicoLigacoesSecaoMetalThursday::class,
        )->normalizarDados([
            [
                'url' => 'https://exemplo.test/recurso',
                'etiqueta' => null,
                'incorporar' => false,
            ],
        ]);
    }
}
