<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Models\MetalThursday\SeccaoMetalThursday;
use App\Servicos\Incorporacoes\RenderizadorIncorporacoes;
use Illuminate\Contracts\View\View;
use Illuminate\Support\HtmlString;
use Illuminate\View\Component;

/**
 * Apresenta a incorporação associada a uma secção da MetalThursday.
 *
 * A validação da ligação e a construção do HTML são delegadas ao
 * RenderizadorIncorporacoes.
 *
 * @since 3.0.0
 *
 * @version 1.0.0
 */
final class Incorporacao extends Component
{
    /**
     * Conteúdo HTML validado da incorporação.
     *
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    public readonly HtmlString $conteudo;

    /**
     * Cria o componente.
     *
     * @param  SeccaoMetalThursday  $seccao  Secção apresentada.
     * @param  RenderizadorIncorporacoes  $renderizadorIncorporacoes
     *                                                                Serviço responsável pela validação e renderização.
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    public function __construct(
        SeccaoMetalThursday $seccao,
        RenderizadorIncorporacoes $renderizadorIncorporacoes,
    ) {
        $this->conteudo =
            $renderizadorIncorporacoes->renderizar(
                $seccao,
            );
    }

    /**
     * Obtém a view do componente.
     *
     * @return View View da incorporação.
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    public function render(): View
    {
        return view(
            'components.incorporacao',
        );
    }
}
