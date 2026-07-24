<?php

declare(strict_types=1);

namespace App\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * Renderiza o layout principal para utilizadores não autenticados.
 *
 * @since 1.0.0
 *
 * @version 2.0.0
 */
final class LayoutConvidado extends Component
{
    /**
     * Obtém a vista do layout destinado a utilizadores não autenticados.
     *
     * O nome `render` permanece em inglês por corresponder ao contrato
     * convencional dos componentes Blade do Laravel.
     *
     * @return View Vista do layout para utilizadores não autenticados.
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
