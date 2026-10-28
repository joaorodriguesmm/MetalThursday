<?php

declare(strict_types=1);

namespace Tests\Feature\View\Components\MetalThursday;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class AcessibilidadeModalAvaliacaoTest extends TestCase
{
    #[Test]
    public function grupo_de_estrelas_possui_nome_e_descricao_acessiveis(): void
    {
        $html = view(
            'metal-thursday.parciais._modal-avaliacao',
            [
                'pontuacoesDisponiveis' => range(1, 10),
                'pontuacaoMaxima' => 10,
            ],
        )->render();

        $correspondencias = [];

        $resultado = preg_match(
            '/<div\b(?=[^>]*\bdata-estrelas-avaliacao\b)[^>]*>/s',
            $html,
            $correspondencias,
        );

        self::assertSame(
            1,
            $resultado,
            'O grupo de estrelas deve existir no HTML renderizado.',
        );

        $grupo = $correspondencias[0];

        self::assertStringContainsString(
            'role="group"',
            $grupo,
            'As estrelas devem formar um grupo acessível.',
        );

        self::assertStringContainsString(
            'aria-label="Selecionar pontuação"',
            $grupo,
        );

        self::assertStringContainsString(
            'aria-describedby="feedback-avaliacao erro-pontuacao-avaliacao"',
            $grupo,
            'O grupo deve estar associado ao feedback e ao erro.',
        );

        self::assertStringContainsString(
            'id="feedback-avaliacao"',
            $html,
        );

        self::assertStringContainsString(
            'id="erro-pontuacao-avaliacao"',
            $html,
        );
    }
}
