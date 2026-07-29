<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Models\MetalThursday\SeccaoMetalThursday;
use App\Servicos\Incorporacoes\RenderizadorIncorporacoes;
use Illuminate\Contracts\View\View;
use Illuminate\Support\HtmlString;
use Illuminate\View\Component;

/**
 * Prepara a incorporação associada a uma secção da MetalThursday.
 *
 * A validação da ligação e a construção segura do HTML são delegadas ao
 * serviço RenderizadorIncorporacoes.
 *
 * @since 3.0.0
 *
 * @version 2.0.0
 */
final class Incorporacao extends Component
{
    /**
     * Conteúdo HTML validado da incorporação.
     *
     *
     * @since 3.0.0
     *
     * @version 2.0.0
     */
    public readonly HtmlString $conteudo;

    /**
     * Cria uma nova instância do componente.
     *
     * @param  SeccaoMetalThursday  $seccao  Secção apresentada.
     * @param  RenderizadorIncorporacoes  $renderizadorIncorporacoes  Serviço
     *                                                                responsável
     *                                                                pela validação
     *                                                                e renderização.
     *
     * @since 3.0.0
     *
     * @version 2.0.0
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
     * Obtém a vista do componente.
     *
     * @return View Vista da incorporação.
     *
     * @since 3.0.0
     *
     * @version 2.0.0
     */
    public function render(): View
    {
        return view(
            'components.incorporacao',
        );
    }
}
