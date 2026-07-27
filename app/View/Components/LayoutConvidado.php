<?php

declare(strict_types=1);

namespace App\View\Components;

use Illuminate\Contracts\View\View;

/**
 * Prepara o layout utilizado nas páginas destinadas a visitantes.
 *
 * Os dados comuns do documento são fornecidos pela classe LayoutBase.
 *
 * @since 1.0.0
 *
 * @version 3.0.0
 */
final class LayoutConvidado extends LayoutBase
{
    /**
     * Obtém a view do layout.
     *
     * @return View View do layout de visitante.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function render(): View
    {
        return view(
            'layouts.convidado',
        );
    }
}
