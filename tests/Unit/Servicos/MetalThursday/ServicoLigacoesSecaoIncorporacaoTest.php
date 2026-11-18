<?php

declare(strict_types=1);

namespace Tests\Unit\Servicos\MetalThursday;

use App\Servicos\MetalThursday\ServicoLigacoesSecaoMetalThursday;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Verifica que apenas vídeos individuais podem ser incorporados.
 */
final class ServicoLigacoesSecaoIncorporacaoTest extends TestCase
{
    /**
     * Ligações individuais continuam incorporáveis.
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

        self::assertTrue($normalizadas[0]['incorporar']);
    }

    /**
     * Mesmo com a opção ativa, playlists ficam apenas como ligação externa.
     */
    #[Test]
    public function youtube_desativa_incorporacao_de_playlists(): void
    {
        $original = 'https://music.youtube.com/playlist?list=OLAK5uy_Test1234567890';
        $normalizadas = app(ServicoLigacoesSecaoMetalThursday::class)
            ->normalizarDados([
                [
                    'url' => $original,
                    'etiqueta' => null,
                    'incorporar' => true,
                ],
            ]);

        self::assertSame($original, $normalizadas[0]['url']);
        self::assertFalse($normalizadas[0]['incorporar']);
    }

    /**
     * Campos de configuração descontinuados deixam de ser aceites.
     */
    #[Test]
    public function youtube_rejeita_configuracao_manual_descontinuada(): void
    {
        $this->expectException(InvalidArgumentException::class);

        app(ServicoLigacoesSecaoMetalThursday::class)
            ->normalizarDados([
                [
                    'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                    'etiqueta' => null,
                    'incorporar' => true,
                    'tipo_incorporacao' => 'video',
                ],
            ]);
    }

    /**
     * Não é possível introduzir um URL alternativo via submissão direta.
     */
    #[Test]
    public function youtube_rejeita_url_alternativo(): void
    {
        $this->expectException(InvalidArgumentException::class);

        app(ServicoLigacoesSecaoMetalThursday::class)
            ->normalizarDados([
                [
                    'url' => 'https://music.youtube.com/watch?v=dQw4w9WgXcQ',
                    'etiqueta' => null,
                    'incorporar' => true,
                    'url_incorporacao_alternativa' => 'https://youtu.be/M7lc1UVf-VE',
                ],
            ]);
    }

    /**
     * Um URL com vídeo e lista não pode incorporar a faixa isoladamente.
     */
    #[Test]
    public function youtube_desativa_incorporacao_com_video_e_lista(): void
    {
        $normalizadas = app(ServicoLigacoesSecaoMetalThursday::class)
            ->normalizarDados([[
                'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ&list=PL1234567890',
                'etiqueta' => null,
                'incorporar' => true,
            ]]);

        self::assertFalse($normalizadas[0]['incorporar']);
    }
}
