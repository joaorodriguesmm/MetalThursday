<?php

declare(strict_types=1);

namespace Tests\Feature\Servicos\MetalThursday;

use App\Servicos\MetalThursday\ServicoParametrosListagemMetalThursday;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ServicoParametrosListagemMetalThursdayTest extends TestCase
{
    #[Test]
    public function utiliza_valores_predefinidos_perante_parametros_invalidos(): void
    {
        $pedido = Request::create(
            '/',
            'GET',
            [
                'por_pagina' => 13,
                'vista' => ['invalida'],
            ],
        );

        $servico = new ServicoParametrosListagemMetalThursday;

        self::assertSame(
            10,
            $servico->obterNumeroPorPagina(
                $pedido,
            ),
        );

        self::assertSame(
            'completa',
            $servico->obterTipoVista(
                $pedido,
            ),
        );
    }

    #[Test]
    public function aceita_parametros_validos(): void
    {
        $pedido = Request::create(
            '/',
            'GET',
            [
                'por_pagina' => '50',
                'vista' => ' SIMPLIFICADA ',
            ],
        );

        $servico = new ServicoParametrosListagemMetalThursday;

        self::assertSame(
            50,
            $servico->obterNumeroPorPagina(
                $pedido,
            ),
        );

        self::assertSame(
            'simplificada',
            $servico->obterTipoVista(
                $pedido,
            ),
        );
    }
}
