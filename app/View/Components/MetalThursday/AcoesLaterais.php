<?php

declare(strict_types=1);

namespace App\View\Components\MetalThursday;

use App\Models\Autenticacao\Utilizador;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\View\Component;

/**
 * Prepara os acessos rápidos às áreas de gestão da MetalThursday.
 *
 * O componente determina qual das páginas representadas se encontra ativa e
 * se a ação genérica de criação deve ser apresentada. Esta ação fica reservada
 * a utilizadores com privilégios administrativos; utilizadores comuns
 * publicam através da respetiva reserva pendente.
 *
 * @since 1.0.0
 */
final class AcoesLaterais extends Component
{
    /**
     * Indica se a ação genérica de criação deve ser apresentada.
     *
     * @since 2.0.0
     */
    public readonly bool $apresentaAcaoCriacaoMetalThursday;

    /**
     * Indica se a página de criação de uma MetalThursday está ativa.
     *
     * @since 2.0.0
     */
    public readonly bool $paginaCriacaoMetalThursdayAtiva;

    /**
     * Indica se uma página da área de artistas está ativa.
     *
     * @since 2.0.0
     */
    public readonly bool $paginaArtistasAtiva;

    /**
     * Indica se uma página da área de edições está ativa.
     *
     * @since 2.0.0
     */
    public readonly bool $paginaEdicoesAtiva;

    /**
     * Indica se uma página da área de géneros está ativa.
     *
     * @since 2.0.0
     */
    public readonly bool $paginaGenerosAtiva;

    /**
     * Cria uma nova instância do componente.
     *
     * @param  Request  $pedido  Pedido HTTP atual.
     *
     * @since 1.0.0
     */
    public function __construct(
        Request $pedido,
    ) {
        $utilizador =
            $pedido->user(
                'sessao',
            );

        $this->apresentaAcaoCriacaoMetalThursday =
            $utilizador instanceof Utilizador
            && $utilizador->possuiPrivilegiosAdministrativos();

        $this->paginaCriacaoMetalThursdayAtiva =
            $pedido->routeIs(
                'metal-thursday.criar',
            );

        $this->paginaArtistasAtiva =
            $pedido->routeIs(
                'artistas.*',
            );

        $this->paginaEdicoesAtiva =
            $pedido->routeIs(
                'edicoes.*',
            );

        $this->paginaGenerosAtiva =
            $pedido->routeIs(
                'generos.*',
            );
    }

    /**
     * Obtém a vista do componente.
     *
     * @return View Vista das ações laterais.
     *
     * @since 1.0.0
     */
    public function render(): View
    {
        return view(
            'components.metal-thursday.acoes-laterais',
        );
    }
}
