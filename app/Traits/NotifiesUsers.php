<?php

namespace App\Traits;

use App\Models\User;
use App\Notifications\UserInteractionOccurred;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;

/**
 * Envia notificações de interação.
 *
 * @since 1.0
 * @version 1.0
 */
trait NotifiesUsers
{
    /**
     * Envia notificação de interação para os utilizadores que não fizeram a ação.
     *
     * @param mixed $subject - O objeto que recebeu a interação (comentário, secção, etc.).
     * @param string $actionText - O texto que descreve a ação (ex: "comentou", "gostou do").
     * @return void
     *
     * @since 1.0
     * @version 1.0
     */
    protected function notifyOtherUsers(mixed $subject, string $actionText): void
    {
        $causer = Auth::user();
        if (!$causer) {
            return;
        }

        $recipients = User::selectable()->where('id', '!=', $causer->id)->get();

        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new UserInteractionOccurred($subject, $causer, $actionText));
        }
    }
}
