<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Define a notificação de redefinição de password.
 *
 * @since 1.0
 *
 * @version 1.0
 */
class CustomResetPasswordNotification extends Notification
{
    public string $token;

    /**
     * Cria uma nova notificação de redefinição de password.
     *
     * @param  string  $token  - Token de redefinição de password.
     * @return void
     *
     * @since 1.0
     *
     * @version 1.0
     */
    public function __construct(string $token)
    {
        $this->token = $token;
    }

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
     * Obtém o e-mail de redefinição de password.
     *
     * @param  mixed  $notifiable  - Utilizador.
     * @return MailMessage - E-mail de redefinição de password.
     *
     * @since 1.0
     *
     * @version 1.0
     */
    public function toMail(mixed $notifiable): MailMessage
    {
        $resetUrl = url(route('password.reset', ['token' => $this->token], false));
        $expire = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

        return (new MailMessage)
            ->subject('MetalThursday - Redefinição de palavra-passe')
            ->greeting('Olá, '.$notifiable->first_name.',')
            ->line('Recebeste este e-mail porque foi recebido um pedido de redefinição de palavra-passe para a tua conta.')
            ->action('Redefinir palavra-passe', $resetUrl)
            ->line("Este link de redefinição de palavra-passe irá expirar em {$expire} minutos.")
            ->line('Se não pediste uma redefinição de palavra-passe, nenhuma ação adicional é necessária.');
    }
}
