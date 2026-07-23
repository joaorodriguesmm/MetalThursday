<?php

declare(strict_types=1);

namespace App\Http\Controllers\Utilizadores;

use App\Http\Controllers\Controller;
use App\Models\Autenticacao\Utilizador;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;

/**
 * Gere as notificações do utilizador autenticado.
 *
 * @since 1.0.0
 *
 * @version 2.0.0
 */
final class ControladorNotificacao extends Controller
{
    /**
     * Número de notificações apresentadas por página.
     *
     * @var int
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const ITENS_POR_PAGINA = 15;

    /**
     * Apresenta as notificações do utilizador autenticado.
     *
     * @param  Request  $pedido  Pedido HTTP.
     * @return View Página das notificações.
     *
     * @throws AuthenticationException Quando não existe autenticação válida.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function index(
        Request $pedido,
    ): View {
        $utilizador = $this->obterUtilizadorAutenticado(
            $pedido,
        );

        $notificacoes = $utilizador
            ->notifications()
            ->select([
                'id',
                'type',
                'notifiable_type',
                'notifiable_id',
                'data',
                'read_at',
                'created_at',
                'updated_at',
            ])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(
                self::ITENS_POR_PAGINA,
            )
            ->withQueryString();

        return view(
            'notifications.index',
            [
                /*
                 * A chave permanece temporariamente em inglês até à revisão
                 * da vista.
                 */
                'notifications' => $notificacoes,
            ],
        );
    }

    /**
     * Marca uma notificação do utilizador autenticado como lida.
     *
     * A notificação é procurada através da relação do utilizador, impedindo
     * que uma notificação pertencente a outra conta seja consultada ou
     * alterada.
     *
     * @param  Request  $pedido  Pedido HTTP.
     * @param  string  $identificadorNotificacao  Identificador da notificação.
     * @return RedirectResponse Redirecionamento para a página anterior.
     *
     * @throws AuthenticationException Quando não existe autenticação válida.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function marcarComoLida(
        Request $pedido,
        string $identificadorNotificacao,
    ): RedirectResponse {
        $utilizador = $this->obterUtilizadorAutenticado(
            $pedido,
        );

        /** @var DatabaseNotification $notificacao */
        $notificacao = $utilizador
            ->notifications()
            ->whereKey(
                $identificadorNotificacao,
            )
            ->firstOrFail();

        if ($notificacao->unread()) {
            $notificacao->markAsRead();
        }

        return back()->with(
            'estado',
            'Notificação marcada como lida.',
        );
    }

    /**
     * Marca todas as notificações não lidas como lidas.
     *
     * A atualização é executada diretamente na base de dados, evitando
     * carregar todas as notificações para memória.
     *
     * @param  Request  $pedido  Pedido HTTP.
     * @return RedirectResponse Redirecionamento para a página anterior.
     *
     * @throws AuthenticationException Quando não existe autenticação válida.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function marcarTodasComoLidas(
        Request $pedido,
    ): RedirectResponse {
        $utilizador = $this->obterUtilizadorAutenticado(
            $pedido,
        );

        $utilizador
            ->unreadNotifications()
            ->update([
                'read_at' => now(),
            ]);

        return back()->with(
            'estado',
            'Todas as notificações foram marcadas como lidas.',
        );
    }

    /**
     * Obtém o utilizador autenticado.
     *
     * @param  Request  $pedido  Pedido HTTP.
     * @return Utilizador Utilizador autenticado.
     *
     * @throws AuthenticationException Quando não existe autenticação válida.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function obterUtilizadorAutenticado(
        Request $pedido,
    ): Utilizador {
        $utilizador = $pedido->user();

        if (! $utilizador instanceof Utilizador) {
            throw new AuthenticationException(
                'É necessário iniciar sessão para consultar notificações.',
            );
        }

        return $utilizador;
    }
}
