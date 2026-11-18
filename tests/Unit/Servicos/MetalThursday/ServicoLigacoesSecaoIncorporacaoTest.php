<?php

declare(strict_types=1);

namespace Tests\Unit\Servicos\MetalThursday;

use App\Servicos\MetalThursday\ServicoLigacoesSecaoMetalThursday;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Verifica o contrato de configuração das incorporações por ligação.
 */
final class ServicoLigacoesSecaoIncorporacaoTest extends TestCase
{
    /**
     * O comportamento anterior permanece predefinido.
     */
    #[Test]
    public function ligacoes_antigas_mantem_incorporacao_automatica(): void
    {
        $normalizadas = app(ServicoLigacoesSecaoMetalThursday::class)
            ->normalizarDados([
                [
                    'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                    'etiqueta' => null,
                    'incorporar' => true,
                ],
            ]);

        self::assertNull($normalizadas[0]['tipo_incorporacao']);
        self::assertNull($normalizadas[0]['url_incorporacao_alternativa']);
        self::assertTrue($normalizadas[0]['incorporar']);
    }

    /**
     * Guarda as escolhas sem substituir o endereço original.
     */
    #[Test]
    public function youtube_aceita_playlist_e_incorporacao_alternativa(): void
    {
        $original = 'https://music.youtube.com/playlist?list=OLAK5uy_Test1234567890';
        $alternativa = 'https://youtu.be/dQw4w9WgXcQ';

        $normalizadas = app(ServicoLigacoesSecaoMetalThursday::class)
            ->normalizarDados([
                [
                    'url' => $original,
                    'etiqueta' => null,
                    'incorporar' => true,
                    'tipo_incorporacao' => 'video',
                    'url_incorporacao_alternativa' => $alternativa,
                ],
            ]);

        self::assertSame($original, $normalizadas[0]['url']);
        self::assertSame('video', $normalizadas[0]['tipo_incorporacao']);
        self::assertSame($alternativa, $normalizadas[0]['url_incorporacao_alternativa']);
    }

    /**
     * Impede a utilização de incorporações alternativas noutras plataformas.
     */
    #[Test]
    public function spotify_rejeita_configuracao_manual_youtube(): void
    {
        $this->expectException(InvalidArgumentException::class);

        app(ServicoLigacoesSecaoMetalThursday::class)
            ->normalizarDados([
                [
                    'url' => 'https://open.spotify.com/track/11dFghVXANMlKmJXsNCbNl',
                    'etiqueta' => null,
                    'incorporar' => true,
                    'tipo_incorporacao' => 'video',
                ],
            ]);
    }

    /**
     * Um host semelhante não pode ser utilizado como origem alternativa.
     */
    #[Test]
    public function youtube_rejeita_url_alternativo_de_host_semelhante(): void
    {
        $this->expectException(InvalidArgumentException::class);

        app(ServicoLigacoesSecaoMetalThursday::class)
            ->normalizarDados([
                [
                    'url' => 'https://music.youtube.com/watch?v=dQw4w9WgXcQ',
                    'etiqueta' => null,
                    'incorporar' => true,
                    'url_incorporacao_alternativa' => 'https://youtube.com.example.org/watch?v=dQw4w9WgXcQ',
                ],
            ]);
    }
}
