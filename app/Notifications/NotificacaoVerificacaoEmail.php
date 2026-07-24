<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Autenticacao\Utilizador;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use LogicException;

/**
 * Envia a ligação necessária para verificar o endereço de e-mail.
 *
 * A criação da ligação temporária assinada e a respetiva expiração são
 * asseguradas pela notificação oficial do Laravel.
 *
 * @since 1.0.0
 *
 * @version 2.0.0
 */
final class NotificacaoVerificacaoEmail extends VerifyEmail
{
    /**
     * Constrói a mensagem de verificação do endereço de e-mail.
     *
     * @param  mixed  $notificavel  Entidade que recebe a notificação.
     * @return MailMessage Mensagem de verificação.
     *
     * @throws LogicException Quando o destinatário não é um utilizador
     *                        válido.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function toMail(
        mixed $notificavel,
    ): MailMessage {
        if (! $notificavel instanceof Utilizador) {
            throw new LogicException(
                'A notificação de verificação do endereço de e-mail apenas pode ser enviada a um utilizador válido.',
            );
        }

        return (new MailMessage)
            ->subject(
                'MetalThursday — Verificação do endereço de e-mail',
            )
            ->greeting(
                sprintf(
                    'Olá %s!',
                    $notificavel->primeiro_nome,
                ),
            )
            ->line(
                'Obrigado por concluíres o registo no MetalThursday!',
            )
            ->line(
                'Confirma o teu endereço de e-mail através do botão abaixo.',
            )
            ->action(
                'Verificar endereço de e-mail',
                $this->verificationUrl(
                    $notificavel,
                ),
            )
            ->line(
                'Caso não tenhas criado esta conta, não precisas de realizar qualquer ação.',
            );
    }
}
