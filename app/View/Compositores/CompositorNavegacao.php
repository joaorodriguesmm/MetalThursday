<?php

declare(strict_types=1);

namespace App\View\Compositores;

use App\Models\Autenticacao\Utilizador;
use Illuminate\Contracts\Auth\Factory as FabricaAutenticacao;
use Illuminate\View\View;

/**
 * Disponibiliza os dados necessários à vista de navegação.
 *
 * Atualmente, o compositor fornece o número de notificações não lidas do
 * utilizador autenticado.
 *
 * @since 1.0.0
 *
 * @version 2.0.0
 */
final class CompositorNavegacao
{
    /**
     * Cria o compositor da navegação.
     *
     * @param  FabricaAutenticacao  $autenticacao  Serviço de autenticação.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function __construct(
        private readonly FabricaAutenticacao $autenticacao,
    ) {}

    /**
     * Associa os dados necessários à vista de navegação.
     *
     * O nome `compose` permanece em inglês por corresponder ao método
     * convencional utilizado pelos compositores de vistas do Laravel.
     *
     * @param  View  $vista  Vista de navegação.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function compose(
        View $vista,
    ): void {
        $utilizador =
            $this
                ->autenticacao
                ->guard()
                ->user();

        $numeroNotificacoesNaoLidas =
            $utilizador instanceof Utilizador
            ? $utilizador
                ->unreadNotifications()
                ->count()
            : 0;

        $vista->with(
            'numeroNotificacoesNaoLidas',
            $numeroNotificacoesNaoLidas,
        );
    }
}
