<?php

namespace App\Notifications;

use App\Models\MetalThursday;
use App\Models\Autenticacao\Utilizador;

/**
 * Define a notificação de criação de MetalThursday.
 *
 * @since 1.0
 *
 * @version 1.0
 */
class NewMetalThursdayCreated extends AppNotification
{
    protected MetalThursday $metalThursday;

    /**
     * Cria uma nova notificação de criação de MetalThursday.
     *
     * @param  MetalThursday  $metalThursday  - A MetalThursday criada.
     * @return void
     *
     * @since 1.0
     *
     * @version 1.0
     */
    public function __construct(MetalThursday $metalThursday)
    {
        $this->metalThursday = $metalThursday;
    }

    /**
     * Obtém se deve enviar um e-mail para o utilizador.
     *
     * @param  Utilizador  $notifiable  - O utilizador que recebe a notificação.
     * @return bool - Se deve enviar um e-mail para o utilizador.
     *
     * @since 1.0
     *
     * @version 1.0
     */
    protected function shouldSendEmail(Utilizador $notifiable): bool
    {
        return $notifiable->hasEmailPermission('all') || $notifiable->hasEmailPermission('new-posts');
    }

    /**
     * Obtém a representação da notificação em array para guardar na base de dados.
     *
     * @param  Utilizador  $notifiable  - O utilizador que recebe a notificação.
     * @return array - A representação da notificação em array.
     *
     * @since 1.0
     *
     * @version 1.0
     */
    public function toArray(Utilizador $notifiable): array
    {
        $mtName = $this->metalThursday->name ?: ($this->metalThursday->edition?->name . ' - Semana ' . $this->metalThursday->week_number_in_edition);
        $authorName = $this->metalThursday->author?->name ?? 'um autor';
        $creatorName = $this->metalThursday->creator?->name ?? 'sistema';

        return [
            'message' => "Uma nova MetalThursday da autoria de {$authorName} foi publicada por {$creatorName}: {$mtName}",
            'url' => $this->getActionUrl($notifiable),
            'icon' => 'bi-fire',
            'color' => 'text-danger',
        ];
    }

    /**
     * Obtém o assunto da notificação.
     *
     * @param  Utilizador  $notifiable  - O utilizador que recebe a notificação.
     * @return string - O assunto da notificação.
     *
     * @since 1.0
     *
     * @version 1.0
     */
    protected function getSubject(Utilizador $notifiable): string
    {
        return 'Nova MetalThursday Disponível: ' . ($this->metalThursday->name ?: ($this->metalThursday->edition?->name . ' - Semana ' . $this->metalThursday->week_number_in_edition));
    }

    /**
     * Obtém a linha da mensagem da notificação.
     *
     * @param  Utilizador  $notifiable  - O utilizador que recebe a notificação.
     * @return string - A linha da mensagem da notificação.
     *
     * @since 1.0
     *
     * @version 1.0
     */
    protected function getMessageLine(Utilizador $notifiable): string
    {
        $authorName = $this->metalThursday->author?->name ?? 'um autor';
        $creatorName = $this->metalThursday->creator?->name ?? 'sistema';
        $mtName = $this->metalThursday->name ?: ($this->metalThursday->edition?->name . ' - Semana ' . $this->metalThursday->week_number_in_edition);

        return "Uma nova MetalThursday da autoria de {$authorName} foi publicada por {$creatorName}: **{$mtName}**";
    }

    /**
     * Obtém o texto do botão da notificação.
     *
     * @param  Utilizador  $notifiable  - O utilizador que recebe a notificação.
     * @return string - O texto do botão da notificação.
     *
     * @since 1.0
     *
     * @version 1.0
     */
    protected function getActionText(Utilizador $notifiable): string
    {
        return 'Ver MetalThursday';
    }

    /**
     * Obtém a URL do botão da notificação.
     *
     * @param  Utilizador  $notifiable  - O utilizador que recebe a notificação.
     * @return string - A URL do botão da notificação.
     *
     * @since 1.0
     *
     * @version 1.0
     */
    protected function getActionUrl(Utilizador $notifiable): string
    {
        return route('metalthursday.show', $this->metalThursday);
    }
}
