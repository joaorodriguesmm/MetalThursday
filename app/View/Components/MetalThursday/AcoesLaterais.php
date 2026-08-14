<?php

declare(strict_types=1);

namespace App\View\Components\MetalThursday;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\View\Component;

/**
 * Prepara os acessos rápidos às áreas de gestão da MetalThursday.
 *
 * O componente determina qual das páginas representadas se encontra ativa.
 * A autorização de cada acesso é aplicada na vista através da respetiva
 * política.
 *
 * @since 1.0.0
 */
final class AcoesLaterais extends Component
{
    /**
     * Indica se a página de criação de uma MetalThursday está ativa.
     *
     * @since 2.0.0
     */
    public readonly bool $paginaCriacaoMetalThursdayAtiva;

    /**
     * Indica se uma página da área de bandas está ativa.
     *
     * @since 2.0.0
     */
    public readonly bool $paginaBandasAtiva;

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
        $this->paginaCriacaoMetalThursdayAtiva =
            $pedido->routeIs(
                'metal-thursday.criar',
            );

        $this->paginaBandasAtiva =
            $pedido->routeIs(
                'bandas.*',
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
