<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Models\Autenticacao\Utilizador;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Factory as FabricaAutenticacao;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\View\Component;

/**
 * Prepara a navegação principal da aplicação.
 *
 * O componente identifica o utilizador autenticado, determina as páginas
 * ativas e obtém a quantidade de notificações por ler.
 *
 * @since 1.0.0
 *
 * @version 3.0.0
 */
final class Navegacao extends Component
{
    /**
     * Nome apresentado para a aplicação.
     *
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    public readonly string $nomeAplicacao;

    /**
     * Utilizador autenticado.
     *
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    public readonly Utilizador $utilizadorAutenticado;

    /**
     * Quantidade de notificações por ler.
     *
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    public readonly int $numeroNotificacoesNaoLidas;

    /**
     * Indica se a página inicial está ativa.
     *
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    public readonly bool $paginaInicialAtiva;

    /**
     * Indica se uma página do perfil está ativa.
     *
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    public readonly bool $paginaPerfilAtiva;

    /**
     * Cria uma nova instância do componente.
     *
     * @param  Request  $pedido  Pedido HTTP atual.
     * @param  FabricaAutenticacao  $autenticacao  Gestor de autenticação.
     * @param  string  $nomeAplicacao  Nome fornecido pelo layout.
     *
     * @throws AuthenticationException Quando não existe um utilizador
     *                                 autenticado válido.
     *
     * @since 1.0.0
     *
     * @version 3.0.0
     */
    public function __construct(
        Request $pedido,
        FabricaAutenticacao $autenticacao,
        string $nomeAplicacao,
    ) {
        $nomeNormalizado = trim(
            $nomeAplicacao,
        );

        $this->nomeAplicacao = $nomeNormalizado !== ''
            ? $nomeNormalizado
            : 'MetalThursday';

        $utilizador = $autenticacao
            ->guard(
                'web',
            )
            ->user();

        if (! $utilizador instanceof Utilizador) {
            throw new AuthenticationException(
                'É necessário iniciar sessão para apresentar a navegação.',
            );
        }

        $identificadorUtilizador = $utilizador->getKey();

        if (
            ! is_numeric($identificadorUtilizador)
            || (int) $identificadorUtilizador < 1
        ) {
            throw new AuthenticationException(
                'Não foi possível identificar o utilizador autenticado.',
            );
        }

        $this->utilizadorAutenticado = $utilizador;

        $this->numeroNotificacoesNaoLidas = $utilizador
            ->unreadNotifications()
            ->count();

        $this->paginaInicialAtiva = $pedido->routeIs(
            'inicio',
        );

        $this->paginaPerfilAtiva = $pedido->routeIs(
            'perfil.*',
        );
    }

    /**
     * Obtém a view do componente.
     *
     * @return View View da navegação.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function render(): View
    {
        return view(
            'components.navegacao',
        );
    }
}
