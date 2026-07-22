<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * Renderiza o layout da aplicação para utilizadores sem sessão iniciada.
 *
 * @since 1.0
 *
 * @version 1.0
 */
class GuestLayout extends Component
{
    /**
     * Obtém o layout da aplicação.
     *
     * @return View - Layout da aplicação.
     *
     * @since 1.0
     *
     * @version 1.0
     */
    public function render(): View
    {
        return view('layouts.guest');
    }
}
