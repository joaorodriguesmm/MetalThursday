<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Autenticacao\Utilizador;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Impede a utilização da aplicação por utilizadores suspensos.
 *
 * O middleware é executado no grupo `web`, depois da inicialização da sessão.
 * Quando o guard encontra um utilizador suspenso através da sessão atual ou
 * de um cookie persistente, a autenticação é terminada, a sessão é invalidada
 * e o token CSRF é renovado.
 *
 * Pedidos que esperam JSON recebem uma resposta proibida. Os restantes são
 * redirecionados para o formulário de início de sessão.
 *
 * @since 2.0.0
 */
final class GarantirAcessoAtivo
{
    /**
     * Processa o pedido HTTP.
     *
     * Visitantes e utilizadores com acesso ativo prosseguem normalmente.
     *
     * @param  Request  $pedido  Pedido HTTP.
     * @param  Closure(Request): Response  $seguinte  Próximo middleware.
     * @return Response Resposta HTTP.
     *
     * @since 2.0.0
     */
    public function handle(
        Request $pedido,
        Closure $seguinte,
    ): Response {
        $utilizador = Auth::guard(
            'sessao',
        )->user();

        if (
            ! $utilizador instanceof Utilizador
            || $utilizador->temAcessoAtivo()
        ) {
            return $seguinte(
                $pedido,
            );
        }

        $this->terminarSessao(
            $pedido,
        );

        $mensagem =
            'A tua conta encontra-se suspensa.';

        if ($pedido->expectsJson()) {
            return response()->json(
                [
                    'mensagem' => $mensagem,
                ],
                Response::HTTP_FORBIDDEN,
            );
        }

        return to_route(
            'login',
        )->withErrors([
            'email' => $mensagem,
        ]);
    }

    /**
     * Termina a autenticação e invalida integralmente a sessão atual.
     *
     * @param  Request  $pedido  Pedido HTTP.
     *
     * @since 2.0.0
     */
    private function terminarSessao(
        Request $pedido,
    ): void {
        Auth::guard(
            'sessao',
        )->logout();

        $pedido
            ->session()
            ->invalidate();

        $pedido
            ->session()
            ->regenerateToken();
    }
}
