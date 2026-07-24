<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Autenticacao\Utilizador;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use LogicException;

/**
 * Envia a ligação necessária para redefinir a palavra-passe.
 *
 * Esta notificação utiliza exclusivamente o canal de e-mail e é enviada
 * imediatamente, evitando atrasos numa operação sensível de autenticação.
 *
 * @since 1.0.0
 *
 * @version 2.0.0
 */
final class NotificacaoRedefinicaoPalavraPasse extends ResetPassword
{
    /**
     * Constrói a mensagem de redefinição da palavra-passe.
     *
     * @param  mixed  $notificavel  Entidade que recebe a notificação.
     * @return MailMessage Mensagem de redefinição.
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
                'A notificação de redefinição da palavra-passe apenas pode ser enviada a um utilizador válido.',
            );
        }

        $urlRedefinicao =
            $this->obterUrlRedefinicao(
                $notificavel,
            );

        $minutosExpiracao =
            $this->obterMinutosExpiracao();

        return (new MailMessage)
            ->subject(
                'MetalThursday — Redefinição da palavra-passe',
            )
            ->greeting(
                $this->obterSaudacao(
                    $notificavel,
                ),
            )
            ->line(
                'Recebeste este e-mail porque foi efetuado um pedido de redefinição da palavra-passe da tua conta.',
            )
            ->action(
                'Redefinir palavra-passe',
                $urlRedefinicao,
            )
            ->line(
                sprintf(
                    'Esta ligação irá expirar dentro de %d minutos.',
                    $minutosExpiracao,
                ),
            )
            ->line(
                'Caso não tenhas solicitado esta alteração, não precisas de realizar qualquer ação.',
            );
    }

    /**
     * Obtém o endereço completo da página de redefinição.
     *
     * O endereço de e-mail é incluído para preencher e identificar a conta
     * correspondente ao token recebido.
     *
     * @param  Utilizador  $utilizador  Utilizador destinatário.
     * @return string Endereço absoluto da redefinição.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function obterUrlRedefinicao(
        Utilizador $utilizador,
    ): string {
        return url(
            route(
                'password.reset',
                [
                    'token' => $this->token,

                    'email' => $utilizador->getEmailForPasswordReset(),
                ],
                false,
            ),
        );
    }

    /**
     * Obtém a duração configurada para o token de redefinição.
     *
     * É utilizado um valor seguro de 60 minutos quando a configuração não
     * contém um número inteiro positivo.
     *
     * @return int Número de minutos.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function obterMinutosExpiracao(): int
    {
        $gestorPalavrasPasse =
            config(
                'auth.defaults.passwords',
            );

        if (
            ! is_string($gestorPalavrasPasse)
            || trim($gestorPalavrasPasse) === ''
        ) {
            return 60;
        }

        $minutos = config(
            sprintf(
                'auth.passwords.%s.expire',
                $gestorPalavrasPasse,
            ),
            60,
        );

        if (
            ! is_numeric($minutos)
            || (int) $minutos < 1
        ) {
            return 60;
        }

        return (int) $minutos;
    }

    /**
     * Obtém a saudação personalizada.
     *
     * @param  Utilizador  $utilizador  Utilizador destinatário.
     * @return string Saudação da mensagem.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function obterSaudacao(
        Utilizador $utilizador,
    ): string {
        $nome =
            is_string($utilizador->nome)
            ? trim($utilizador->nome)
            : '';

        if ($nome === '') {
            return 'Olá!';
        }

        $partesNome = preg_split(
            '/\s+/u',
            $nome,
        );

        $primeiroNome =
            is_array($partesNome)
            && isset($partesNome[0])
            && is_string($partesNome[0])
            ? trim($partesNome[0])
            : '';

        return $primeiroNome !== ''
            ? sprintf(
                'Olá %s!',
                $primeiroNome,
            )
            : 'Olá!';
    }
}
