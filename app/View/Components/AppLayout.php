<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * Renderiza o layout da aplicação para utilizadores com sessão iniciada.
 *
 * @since 1.0
 * @version 1.0
 */
class AppLayout extends Component
{
    /**
     * Obtém a o layout da aplicação.
     *
     * @return View - Layout da aplicação.
     *
     * @since 1.0
     * @version 1.0
     */
    public function render(): View
    {
        return view('layouts.app');
    }
}
