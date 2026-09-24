<?php

declare(strict_types=1);

namespace App\Servicos\MetalThursday;

use Illuminate\Http\Request;

/**
 * Normaliza os parâmetros estruturais da listagem de MetalThursdays.
 *
 * @since 2.0.0
 */
final class ServicoParametrosListagemMetalThursday
{
    /** Identificador da vista completa. */
    public const VISTA_COMPLETA = 'completa';

    /** Identificador da vista simplificada. */
    public const VISTA_SIMPLIFICADA = 'simplificada';

    /** @var list<int> Opções permitidas para o número de registos por página. */
    private const OPCOES_POR_PAGINA = [
        5,
        10,
        20,
        50,
    ];

    /** Número predefinido de registos por página. */
    private const POR_PAGINA_PREDEFINIDO = 10;

    public function obterNumeroPorPagina(
        Request $pedido,
    ): int {
        $numero = filter_var(
            $pedido->query(
                'por_pagina',
                self::POR_PAGINA_PREDEFINIDO,
            ),
            FILTER_VALIDATE_INT,
        );

        if (
            $numero === false
            || ! in_array(
                $numero,
                self::OPCOES_POR_PAGINA,
                true,
            )
        ) {
            return self::POR_PAGINA_PREDEFINIDO;
        }

        return $numero;
    }

    public function obterTipoVista(
        Request $pedido,
    ): string {
        $valor = $pedido->query(
            'vista',
            self::VISTA_COMPLETA,
        );

        if (! is_string($valor)) {
            return self::VISTA_COMPLETA;
        }

        return match (mb_strtolower(
            trim(
                $valor,
            ),
        )) {
            self::VISTA_SIMPLIFICADA => self::VISTA_SIMPLIFICADA,

            default => self::VISTA_COMPLETA,
        };
    }
}
