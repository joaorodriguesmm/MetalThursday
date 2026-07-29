<?php

declare(strict_types=1);

namespace App\View\Components;

use Illuminate\Contracts\View\View;

/**
 * Prepara o layout principal da área autenticada da aplicação.
 *
 * Os dados comuns do documento são preparados pela classe LayoutBase.
 *
 * @since 1.0.0
 *
 * @version 4.0.0
 */
final class LayoutAplicacao extends LayoutBase
{
    /**
     * Obtém a vista do layout principal da aplicação.
     *
     * @return View Vista do layout.
     *
     * @since 1.0.0
     *
     * @version 3.0.0
     */
    public function render(): View
    {
        return view(
            'layouts.aplicacao',
        );
    }
}
