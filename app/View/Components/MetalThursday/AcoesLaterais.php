<?php

declare(strict_types=1);

namespace App\View\Components\MetalThursday;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\View\Component;

/**
 * Prepara os acessos rápidos das áreas de gestão da MetalThursday.
 *
 * O componente determina qual das páginas representadas se encontra ativa.
 * A autorização de cada acesso continua a ser aplicada pela respetiva
 * política durante a apresentação da view.
 *
 * @since 1.0.0
 *
 * @version 3.0.0
 */
final class AcoesLaterais extends Component
{
    /**
     * Indica se a página de criação de MetalThursdays está ativa.
     *
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    public readonly bool $paginaCriacaoMetalThursdayAtiva;

    /**
     * Indica se uma página de bandas está ativa.
     *
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    public readonly bool $paginaBandasAtiva;

    /**
     * Indica se uma página de edições está ativa.
     *
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    public readonly bool $paginaEdicoesAtiva;

    /**
     * Indica se uma página de géneros está ativa.
     *
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    public readonly bool $paginaGenerosAtiva;

    /**
     * Cria uma nova instância do componente.
     *
     * @param  Request  $pedido  Pedido HTTP atual.
     *
     * @since 1.0.0
     *
     * @version 3.0.0
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
     * Obtém a view do componente.
     *
     * @return View View das ações laterais.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function render(): View
    {
        return view(
            'components.metal-thursday.acoes-laterais',
        );
    }
}
