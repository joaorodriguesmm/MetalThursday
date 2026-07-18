<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Gere as notificações do utilizador autenticado.
 *
 * @since 1.0
 * @version 1.0
 */
class NotificationController extends Controller
{
    /**
     * Mostra todas as notificações do utilizador autenticado.
     *
     * @return View - Página de notificações.
     *
     * @since 1.0
     * @version 1.0
     */
    public function index(): View
    {
        $notifications = Auth::user()
                             ->notifications()
                             ->latest()
                             ->paginate(15);

        return view('notifications.index', compact('notifications'));
    }


    /**
     * Marca uma notificação específica como lida.
     *
     * @param string $id Id da notificação.
     * @return RedirectResponse - Redirecionamento para a página de notificações.
     *
     * @since 1.0
     * @version 1.0
     */
    public function markAsRead(Notification $notification): RedirectResponse
    {
        if ($notification->notifiable_id !== Auth::id()) {
            abort(403);
        }

        $notification->markAsRead();
        return back()->with('success', 'Notificação marcada como lida.');
    }

    /**
     * Marca todas as notificações não lidas como lidas para o utilizador autenticado.
     *
     * @return RedirectResponse - Redirecionamento para a página de notificações.
     *
     * @since 1.0
     * @version 1.0
     */
    public function markAllAsRead(): RedirectResponse
    {
        Auth::user()->unreadNotifications->markAsRead();
        return back()->with('success', 'Todas as notificações marcadas como lidas.');
    }
}
