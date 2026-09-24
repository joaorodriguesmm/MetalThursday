<?php

declare(strict_types=1);

namespace Tests\Feature\Servicos\MetalThursday;

use App\Servicos\MetalThursday\ServicoControlosListagemMetalThursday;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa a preparação dos controlos da listagem MetalThursday.
 *
 * @since 2.0.0
 */
final class ServicoControlosListagemMetalThursdayTest extends TestCase
{
    #[Test]
    public function prepara_pesquisa_vista_e_ordenacao_validas(): void
    {
        config([
            'filtros.metal_thursday' => [],
        ]);

        $pedido = Request::create(
            '/',
            'GET',
            [
                'pesquisa' => 'Metallica',
                'ordenar_por' => 'classificacao',
                'direcao_ordenacao' => 'ascendente',
            ],
        );

        $dados = app(
            ServicoControlosListagemMetalThursday::class,
        )->obterDados(
            $pedido,
            'simplificada',
            20,
        );

        self::assertSame(
            'Metallica',
            $dados['pesquisaAtual'],
        );

        self::assertSame(
            'simplificada',
            $dados['vistaAtual'],
        );

        self::assertSame(
            20,
            $dados['porPagina'],
        );

        self::assertSame(
            'classificacao',
            $dados['ordenacaoAtual'],
        );

        self::assertSame(
            'ascendente',
            $dados['direcaoOrdenacaoAtual'],
        );

        self::assertSame(
            'Ver vista completa',
            $dados['textoBotaoAlternarVista'],
        );
    }

    #[Test]
    public function utiliza_valores_predefinidos_perante_ordenacao_invalida(): void
    {
        config([
            'filtros.metal_thursday' => [],
        ]);

        $pedido = Request::create(
            '/',
            'GET',
            [
                'ordenar_por' => 'desconhecida',
                'direcao_ordenacao' => 'desc',
                'pesquisa' => ['invalida'],
            ],
        );

        $dados = app(
            ServicoControlosListagemMetalThursday::class,
        )->obterDados(
            $pedido,
            'desconhecida',
            10,
        );

        self::assertSame(
            '',
            $dados['pesquisaAtual'],
        );

        self::assertSame(
            'completa',
            $dados['vistaAtual'],
        );

        self::assertSame(
            'data',
            $dados['ordenacaoAtual'],
        );

        self::assertSame(
            'descendente',
            $dados['direcaoOrdenacaoAtual'],
        );

        self::assertSame(
            'Ver vista simplificada',
            $dados['textoBotaoAlternarVista'],
        );
    }
}
