<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Models\Autenticacao\Convite;
use App\Models\Autenticacao\Utilizador;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Access\Gate as Autorizacao;
use Illuminate\Contracts\Auth\Factory as FabricaAutenticacao;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\View\Component;

/**
 * Prepara a navegação principal da aplicação.
 *
 * O componente identifica o utilizador autenticado, determina as páginas
 * ativas, verifica o acesso às áreas administrativas e obtém a quantidade de
 * notificações por ler.
 *
 * @since 1.0.0
 *
 * @version 6.0.0
 */
final class Navegacao extends Component
{
    /**
     * Nome utilizado quando o valor recebido é inválido.
     *
     * @var string
     *
     * @since 4.0.0
     *
     * @version 1.0.0
     */
    private const NOME_APLICACAO_PREDEFINIDO =
        'MetalThursday';

    /**
     * Nome apresentado para a aplicação.
     *
     * @since 3.0.0
     *
     * @version 2.0.0
     */
    public readonly string $nomeAplicacao;

    /**
     * Utilizador autenticado.
     *
     * @since 3.0.0
     *
     * @version 2.0.0
     */
    public readonly Utilizador $utilizadorAutenticado;

    /**
     * Quantidade de notificações por ler.
     *
     * @since 3.0.0
     *
     * @version 2.0.0
     */
    public readonly int $numeroNotificacoesNaoLidas;

    /**
     * Indica se a página inicial está ativa.
     *
     * @since 3.0.0
     *
     * @version 2.0.0
     */
    public readonly bool $paginaInicialAtiva;

    /**
     * Indica se uma página do perfil está ativa.
     *
     * @since 3.0.0
     *
     * @version 2.0.0
     */
    public readonly bool $paginaPerfilAtiva;

    /**
     * Indica se a gestão dos utilizadores está ativa.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public readonly bool $paginaUtilizadoresAtiva;

    /**
     * Indica se a gestão dos convites está ativa.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public readonly bool $paginaConvitesAtiva;

    /**
     * Indica se o utilizador pode gerir utilizadores.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public readonly bool $podeGerirUtilizadores;

    /**
     * Indica se o utilizador pode gerir convites.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public readonly bool $podeGerirConvites;

    /**
     * Cria uma nova instância do componente.
     *
     * @param  Request  $pedido  Pedido HTTP atual.
     * @param  FabricaAutenticacao  $autenticacao  Gestor de autenticação.
     * @param  Autorizacao  $autorizacao  Gestor de autorização.
     * @param  string  $nomeAplicacao  Nome fornecido pelo layout.
     *
     * @throws AuthenticationException Quando não existe um utilizador
     *                                 autenticado e persistido válido.
     *
     * @since 1.0.0
     *
     * @version 6.0.0
     */
    public function __construct(
        Request $pedido,
        FabricaAutenticacao $autenticacao,
        Autorizacao $autorizacao,
        string $nomeAplicacao,
    ) {
        $this->nomeAplicacao =
            $this->normalizarNomeAplicacao(
                $nomeAplicacao,
            );

        $this->utilizadorAutenticado =
            $this->obterUtilizadorAutenticado(
                $autenticacao,
            );

        $this->numeroNotificacoesNaoLidas =
            (int) $this
                ->utilizadorAutenticado
                ->notificacoesPorLer()
                ->count();

        $this->paginaInicialAtiva =
            $pedido->routeIs(
                'inicio',
            );

        $this->paginaPerfilAtiva =
            $pedido->routeIs(
                'perfil.*',
            );

        $this->paginaUtilizadoresAtiva =
            $pedido->routeIs(
                'utilizadores.*',
            );

        $this->paginaConvitesAtiva =
            $pedido->routeIs(
                'convites.*',
            );

        $autorizacaoUtilizador =
            $autorizacao->forUser(
                $this->utilizadorAutenticado,
            );

        $this->podeGerirUtilizadores =
            $autorizacaoUtilizador->allows(
                'viewAny',
                Utilizador::class,
            );

        $this->podeGerirConvites =
            $autorizacaoUtilizador->allows(
                'viewAny',
                Convite::class,
            );
    }

    /**
     * Obtém a vista do componente.
     *
     * @return View Vista da navegação.
     *
     * @since 1.0.0
     *
     * @version 3.0.0
     */
    public function render(): View
    {
        return view(
            'components.navegacao',
        );
    }

    /**
     * Normaliza o nome da aplicação.
     *
     * @param  string  $nomeAplicacao  Nome recebido.
     * @return string Nome normalizado.
     *
     * @since 4.0.0
     *
     * @version 1.0.0
     */
    private function normalizarNomeAplicacao(
        string $nomeAplicacao,
    ): string {
        $nomeNormalizado =
            preg_replace(
                '/\s+/u',
                ' ',
                trim(
                    $nomeAplicacao,
                ),
            );

        if (
            ! is_string($nomeNormalizado)
            || $nomeNormalizado === ''
        ) {
            return self::NOME_APLICACAO_PREDEFINIDO;
        }

        return $nomeNormalizado;
    }

    /**
     * Obtém o utilizador autenticado através do guard da aplicação.
     *
     * @param  FabricaAutenticacao  $autenticacao  Gestor de autenticação.
     * @return Utilizador Utilizador autenticado.
     *
     * @throws AuthenticationException Quando não existe um utilizador
     *                                 autenticado e persistido válido.
     *
     * @since 4.0.0
     *
     * @version 1.0.0
     */
    private function obterUtilizadorAutenticado(
        FabricaAutenticacao $autenticacao,
    ): Utilizador {
        $utilizador =
            $autenticacao
                ->guard(
                    'sessao',
                )
                ->user();

        if (! $utilizador instanceof Utilizador) {
            throw new AuthenticationException(
                'É necessário iniciar sessão para apresentar a navegação.',
                [
                    'sessao',
                ],
            );
        }

        $identificadorUtilizador =
            filter_var(
                $utilizador->getKey(),
                FILTER_VALIDATE_INT,
                [
                    'options' => [
                        'min_range' => 1,
                    ],
                ],
            );

        if (
            ! $utilizador->exists
            || $identificadorUtilizador === false
        ) {
            throw new AuthenticationException(
                'Não foi possível identificar o utilizador autenticado.',
                [
                    'sessao',
                ],
            );
        }

        return $utilizador;
    }
}
