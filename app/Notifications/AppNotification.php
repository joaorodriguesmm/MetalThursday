<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Define a estrutura base para as notificações da aplicação.
 *
 * @since 1.0
 * @version 1.0
 */
abstract class AppNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Obtém os canais de notificação para o utilizador.
     *
     * @param User $notifiable - O utilizador que recebe a notificação.
     * @return array - Canais de notificação.
     *
     * @since 1.0
     * @version 1.0
     */
    public function via(User $notifiable): array
    {
        $channels = ['database'];

        if ($this->shouldSendEmail($notifiable) && !empty($notifiable->email)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * Obtém o e-mail de notificação para o utilizador.
     *
     * @param User $notifiable - O utilizador que recebe a notificação.
     * @return MailMessage - O e-mail de notificação.
     *
     * @since 1.0
     * @version 1.0
     */
    public function toMail(User $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->getSubject($notifiable))
            ->greeting('Olá ' . $notifiable->first_name . ',')
            ->line($this->getMessageLine($notifiable))
            ->action($this->getActionText($notifiable), $this->getActionUrl($notifiable))
            ->line('Obrigado por fazeres parte da comunidade MetalThursday!');
    }

    /**
     * Obtém se deve enviar um e-mail para o utilizador.
     *
     * @param User $notifiable - O utilizador que recebe a notificação.
     * @return bool - Se deve enviar um e-mail para o utilizador.
     *
     * @since 1.0
     * @version 1.0
     */
    abstract protected function shouldSendEmail(User $notifiable): bool;

    /**
     * Obtém o assunto da notificação.
     *
     * @param User $notifiable - O utilizador que recebe a notificação.
     * @return string - O assunto da notificação.
     *
     * @since 1.0
     * @version 1.0
     */
    abstract protected function getSubject(User $notifiable): string;

    /**
     * Obtém a linha da mensagem da notificação.
     *
     * @param User $notifiable - O utilizador que recebe a notificação.
     * @return string - A linha da mensagem da notificação.
     *
     * @since 1.0
     * @version 1.0
     */
    abstract protected function getMessageLine(User $notifiable): string;

    /**
     * Obtém o texto do botão da notificação.
     *
     * @param User $notifiable - O utilizador que recebe a notificação.
     * @return string - O texto do botão da notificação.
     *
     * @since 1.0
     * @version 1.0
     */
    abstract protected function getActionText(User $notifiable): string;

    /**
     * Obtém a URL do botão da notificação.
     *
     * @param User $notifiable - O utilizador que recebe a notificação.
     * @return string - A URL do botão da notificação.
     *
     * @since 1.0
     * @version 1.0
     */
    abstract protected function getActionUrl(User $notifiable): string;
}
