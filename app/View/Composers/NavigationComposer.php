<?php

namespace App\View\Composers;

use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Composer para o menu de navegação.
 *
 * @since 1.0
 * @version 1.0
 */
class NavigationComposer
{
    /**
     * Liga os dados à view.
     *
     * @param View $view - View a ligar os dados.
     * @return void
     *
     * @since 1.0
     * @version 1.0
     */
    public function compose(View $view): void
    {
        $unreadNotificationsCount = 0;
        if (Auth::check()) {
            $unreadNotificationsCount = Auth::user()->unreadNotifications->count();
        }

        $view->with('unreadNotificationsCount', $unreadNotificationsCount);
    }
}
