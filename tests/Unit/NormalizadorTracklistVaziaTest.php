<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Requests\MetalThursday\Suporte\NormalizadorDadosMetalThursday;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Confirma a normalização de tracklists vazias recebidas pelo formulário.
 */
final class NormalizadorTracklistVaziaTest extends TestCase
{
    #[Test]
    public function interpreta_tracklist_omitida_como_lista_vazia(): void
    {
        $seccoes = NormalizadorDadosMetalThursday::normalizarSeccoes([
            [
                'lancamento' => [
                    'titulo' => 'Álbum antigo',
                    'tipo' => null,
                    'ano_original' => '1987',
                ],
            ],
        ]);

        self::assertIsArray($seccoes);
        self::assertSame([], $seccoes[0]['lancamento']['faixas']);
        self::assertSame('Álbum antigo', $seccoes[0]['lancamento']['titulo']);
    }

    #[Test]
    public function preserva_tracklist_explicitamente_invalida(): void
    {
        $seccoes = NormalizadorDadosMetalThursday::normalizarSeccoes([
            [
                'lancamento' => [
                    'titulo' => 'Álbum antigo',
                    'faixas' => 'valor inválido',
                ],
            ],
        ]);

        self::assertIsArray($seccoes);
        self::assertSame('valor inválido', $seccoes[0]['lancamento']['faixas']);
    }

    #[Test]
    public function preserva_tracklist_preenchida(): void
    {
        $faixas = [['titulo' => 'Primeira música']];
        $seccoes = NormalizadorDadosMetalThursday::normalizarSeccoes([
            ['lancamento' => ['titulo' => 'Álbum', 'faixas' => $faixas]],
        ]);

        self::assertIsArray($seccoes);
        self::assertSame($faixas, $seccoes[0]['lancamento']['faixas']);
    }
}
