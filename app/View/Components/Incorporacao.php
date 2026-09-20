<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Models\MetalThursday\LigacaoSeccaoMetalThursday;
use App\Servicos\Incorporacoes\RenderizadorIncorporacoes;
use Illuminate\Contracts\View\View;
use Illuminate\Support\HtmlString;
use Illuminate\View\Component;

/**
 * Prepara uma ligação pública de uma secção da MetalThursday.
 *
 * A validação da ligação e a construção segura do HTML são delegadas ao
 * serviço RenderizadorIncorporacoes.
 *
 * @since 2.0.0
 */
final class Incorporacao extends Component
{
    /**
     * Conteúdo HTML validado da ligação.
     *
     * @since 2.0.0
     */
    public readonly HtmlString $conteudo;

    /**
     * Cria uma nova instância do componente.
     *
     * @param  LigacaoSeccaoMetalThursday  $ligacao  Ligação apresentada.
     * @param  RenderizadorIncorporacoes  $renderizadorIncorporacoes  Serviço
     *                                                                responsável
     *                                                                pela validação
     *                                                                e renderização.
     *
     * @since 2.0.0
     */
    public function __construct(
        LigacaoSeccaoMetalThursday $ligacao,
        RenderizadorIncorporacoes $renderizadorIncorporacoes,
    ) {
        $this->conteudo =
            $renderizadorIncorporacoes->renderizarLigacao(
                $ligacao,
            );
    }

    /**
     * Obtém a vista do componente.
     *
     * @return View Vista da incorporação.
     *
     * @since 2.0.0
     */
    public function render(): View
    {
        return view(
            'components.incorporacao',
        );
    }
}
