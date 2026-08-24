<?php

declare(strict_types=1);

namespace App\Http\Middleware\MetalThursday;

use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\ReservaMetalThursday;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Contextualiza um pedido de preparação através da reserva indicada na rota.
 *
 * A reserva pendente é a fonte autoritativa para a data e para o autor.
 * Qualquer valor recebido do cliente para esses campos é substituído antes de
 * o pedido chegar ao FormRequest e ao controlador.
 *
 * Apenas o responsável da própria reserva pode prosseguir.
 *
 * @since 2.0.0
 */
final class ContextualizarReservaMetalThursday
{
    /**
     * Processa o pedido associado a uma reserva de MetalThursday.
     *
     * @param  Request  $pedido  Pedido HTTP atual.
     * @param  Closure(Request): Response  $seguinte  Próximo elemento da cadeia.
     * @return Response Resposta produzida pela cadeia HTTP.
     *
     * @since 2.0.0
     */
    public function handle(
        Request $pedido,
        Closure $seguinte,
    ): Response {
        $reserva =
            $pedido->route(
                'reservaMetalThursday',
            );

        $utilizador =
            $pedido->user(
                'sessao',
            );

        if (
            ! $reserva instanceof ReservaMetalThursday
            || ! $utilizador instanceof Utilizador
            || ! $reserva->estaPendente()
            || ! is_numeric(
                $reserva->responsavel_id,
            )
            || (int) $reserva->responsavel_id
            !== (int) $utilizador->getKey()
        ) {
            abort(
                Response::HTTP_FORBIDDEN,
            );
        }

        $pedido->merge([
            'data' => $reserva->data->format(
                'Y-m-d',
            ),

            'autor_id' => (int) $reserva->responsavel_id,
        ]);

        return $seguinte(
            $pedido,
        );
    }
}
