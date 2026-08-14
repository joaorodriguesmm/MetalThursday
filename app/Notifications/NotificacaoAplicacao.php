<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Autenticacao\Utilizador;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use LogicException;

/**
 * Define o comportamento comum das notificações da aplicação.
 *
 * Todas as notificações são guardadas na base de dados. O envio por e-mail
 * depende das preferências do destinatário e da existência de um endereço de
 * e-mail válido.
 *
 * @since 1.0.0
 */
abstract class NotificacaoAplicacao extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Obtém os canais utilizados para enviar a notificação.
     *
     * A notificação é sempre guardada na base de dados. O canal de e-mail
     * apenas é considerado quando o utilizador possui primeiro um endereço
     * disponível. Desta forma, as preferências de e-mail não são consultadas
     * para destinatários que nunca poderiam receber esse canal.
     *
     * @param  object  $notificavel  Entidade que recebe a notificação.
     * @return array<int, string> Canais utilizados.
     *
     * @throws LogicException Quando o destinatário não é um utilizador válido.
     *
     * @since 1.0.0
     */
    public function via(
        object $notificavel,
    ): array {
        $utilizador =
            $this->obterUtilizador(
                $notificavel,
            );

        $canais = [
            'database',
        ];

        if (
            $this->utilizadorPossuiEmail(
                $utilizador,
            )
            && $this->deveEnviarPorEmail(
                $utilizador,
            )
        ) {
            $canais[] =
                'mail';
        }

        return $canais;
    }

    /**
     * Constrói a mensagem enviada por e-mail.
     *
     * A ação é opcional. Quando a notificação concreta não fornecer
     * simultaneamente texto e endereço, o botão não é apresentado.
     *
     * @param  object  $notificavel  Entidade que recebe a notificação.
     * @return MailMessage Mensagem de e-mail.
     *
     * @throws LogicException Quando o destinatário não é um utilizador válido.
     *
     * @since 1.0.0
     */
    public function toMail(
        object $notificavel,
    ): MailMessage {
        $utilizador =
            $this->obterUtilizador(
                $notificavel,
            );

        $mensagem = (new MailMessage)
            ->subject(
                $this->obterAssunto(
                    $utilizador,
                ),
            )
            ->greeting(
                $this->obterSaudacao(
                    $utilizador,
                ),
            )
            ->line(
                $this->obterLinhaMensagem(
                    $utilizador,
                ),
            );

        $textoAcao =
            $this->obterTextoAcao(
                $utilizador,
            );

        $urlAcao =
            $this->obterUrlAcao(
                $utilizador,
            );

        if (
            is_string($textoAcao)
            && trim($textoAcao) !== ''
            && is_string($urlAcao)
            && trim($urlAcao) !== ''
        ) {
            $mensagem->action(
                $textoAcao,
                $urlAcao,
            );
        }

        return $mensagem->line(
            'Obrigado por fazeres parte da comunidade MetalThursday!',
        );
    }

    /**
     * Determina se a notificação deve ser enviada por e-mail.
     *
     * @param  Utilizador  $utilizador  Utilizador destinatário.
     * @return bool Verdadeiro quando o envio está autorizado.
     *
     * @since 1.0.0
     */
    abstract protected function deveEnviarPorEmail(
        Utilizador $utilizador,
    ): bool;

    /**
     * Obtém o assunto da mensagem de e-mail.
     *
     * @param  Utilizador  $utilizador  Utilizador destinatário.
     * @return string Assunto da mensagem.
     *
     * @since 1.0.0
     */
    abstract protected function obterAssunto(
        Utilizador $utilizador,
    ): string;

    /**
     * Obtém a linha principal da mensagem.
     *
     * @param  Utilizador  $utilizador  Utilizador destinatário.
     * @return string Conteúdo principal.
     *
     * @since 1.0.0
     */
    abstract protected function obterLinhaMensagem(
        Utilizador $utilizador,
    ): string;

    /**
     * Obtém o texto apresentado no botão da mensagem.
     *
     * Pode devolver nulo quando a notificação não possuir uma ação.
     *
     * @param  Utilizador  $utilizador  Utilizador destinatário.
     * @return string|null Texto do botão ou nulo.
     *
     * @since 1.0.0
     */
    abstract protected function obterTextoAcao(
        Utilizador $utilizador,
    ): ?string;

    /**
     * Obtém o endereço utilizado pelo botão da mensagem.
     *
     * Pode devolver nulo quando a notificação não possuir uma ação.
     *
     * @param  Utilizador  $utilizador  Utilizador destinatário.
     * @return string|null Endereço da ação ou nulo.
     *
     * @since 1.0.0
     */
    abstract protected function obterUrlAcao(
        Utilizador $utilizador,
    ): ?string;

    /**
     * Obtém e valida o utilizador destinatário.
     *
     * @param  object  $notificavel  Entidade recebida pelo Laravel.
     * @return Utilizador Utilizador destinatário.
     *
     * @throws LogicException Quando a entidade não é um utilizador.
     *
     * @since 2.0.0
     */
    private function obterUtilizador(
        object $notificavel,
    ): Utilizador {
        if (! $notificavel instanceof Utilizador) {
            throw new LogicException(
                'A notificação apenas pode ser enviada a um utilizador válido.',
            );
        }

        return $notificavel;
    }

    /**
     * Determina se o utilizador possui um endereço de e-mail.
     *
     * @param  Utilizador  $utilizador  Utilizador verificado.
     * @return bool Verdadeiro quando existe um endereço.
     *
     * @since 2.0.0
     */
    private function utilizadorPossuiEmail(
        Utilizador $utilizador,
    ): bool {
        return is_string(
            $utilizador->email,
        )
            && trim(
                $utilizador->email,
            ) !== '';
    }

    /**
     * Obtém a saudação personalizada da mensagem.
     *
     * É utilizado apenas o primeiro elemento do nome completo. Quando o nome
     * não está disponível, é apresentada uma saudação genérica.
     *
     * @param  Utilizador  $utilizador  Utilizador destinatário.
     * @return string Saudação da mensagem.
     *
     * @since 2.0.0
     */
    private function obterSaudacao(
        Utilizador $utilizador,
    ): string {
        $nome =
            is_string($utilizador->nome)
            ? trim(
                $utilizador->nome,
            )
            : '';

        if ($nome === '') {
            return 'Olá!';
        }

        $partes = preg_split(
            '/\s+/u',
            $nome,
        );

        $primeiroNome =
            is_array($partes)
            && isset($partes[0])
            && is_string($partes[0])
            ? trim(
                $partes[0],
            )
            : '';

        if ($primeiroNome === '') {
            return 'Olá!';
        }

        return sprintf(
            'Olá %s!',
            $primeiroNome,
        );
    }
}
