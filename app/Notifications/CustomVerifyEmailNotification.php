<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

/**
 * Define a notiificação de verificação de e-mail.
 *
 * @since 1.0
 *
 * @version 1.0
 */
class CustomVerifyEmailNotification extends Notification
{
    /**
     * Obtém os canais de notificação.
     *
     * @param  mixed  $notifiable  - Utilizador a receber a notificação.
     * @return array - Canais de notificação.
     *
     * @since 1.0
     *
     * @version 1.0
     */
    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Obtém o e-mail de verificação de e-mail.
     *
     * @param  mixed  $notifiable  - Utilizador a receber a notificação.
     * @return MailMessage - E-mail de verificação de e-mail.
     *
     * @since 1.0
     *
     * @version 1.0
     */
    public function toMail(mixed $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('MetalThursday - Verificação de E-mail')
            ->greeting('Olá '.$notifiable->first_name.',')
            ->line('Obrigado por concluires o registo no MetalThursday!')
            ->line('Clica no botão abaixo para verificar o teu e-mail.')
            ->action('Verificar E-mail', $this->verificationUrl($notifiable));
    }

    /**
     * Gera o URL de verificação de email.
     *
     * @param  mixed  $notifiable  - Utilizador a receber a notificação.
     * @return string - URL de verificação.
     *
     * @since 1.0
     *
     * @version 1.0
     */
    protected function verificationUrl(mixed $notifiable): string
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(config('auth.verification.expire', 60)),
            ['id' => $notifiable->getKey(), 'hash' => sha1($notifiable->getEmailForVerification())]
        );
    }
}
