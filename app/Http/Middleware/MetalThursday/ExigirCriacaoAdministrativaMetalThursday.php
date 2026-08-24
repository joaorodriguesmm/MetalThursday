<?php

declare(strict_types=1);

namespace App\Http\Middleware\MetalThursday;

use App\Models\Autenticacao\Utilizador;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restringe o fluxo genérico de criação de MetalThursday à administração.
 *
 * Utilizadores comuns publicam exclusivamente através da respetiva reserva
 * explícita. Esta verificação ocorre antes da resolução do FormRequest, pelo
 * que uma submissão genérica não autorizada é rejeitada antes da validação.
 *
 * @since 2.0.0
 */
final class ExigirCriacaoAdministrativaMetalThursday
{
    /**
     * Processa o pedido do fluxo administrativo de criação.
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
        $utilizador =
            $pedido->user(
                'sessao',
            );

        if (
            ! $utilizador instanceof Utilizador
            || ! $utilizador->possuiPrivilegiosAdministrativos()
        ) {
            abort(
                Response::HTTP_FORBIDDEN,
            );
        }

        return $seguinte(
            $pedido,
        );
    }
}
