<?php

namespace App\Notifications;

use App\Models\MetalThursday;
use App\Models\User;
use Carbon\Carbon;

/**
 * Notificação enviada a um utilizador quando ele é nomeado.
 *
 * @since 1.0
 * @version 1.0
 */
class UserNominated extends AppNotification
{
    protected MetalThursday $metalThursday;

    public function __construct(MetalThursday $metalThursday)
    {
        $this->afterCommit();
        $this->metalThursday = $metalThursday;
    }

    protected function shouldSendEmail(User $notifiable): bool
    {
        // Envia email se o utilizador tiver a permissão geral ou a específica para nomeações
        return $notifiable->hasEmailPermission('all') || $notifiable->hasEmailPermission('nomination-alert');
    }

    public function toArray(User $notifiable): array
    {
        return [
            'message' => $this->getMessageLine($notifiable),
            'url'     => $this->getActionUrl($notifiable),
            'icon'    => 'bi-trophy-fill',
            'color'   => 'text-warning',
        ];
    }

    protected function getSubject(User $notifiable): string
    {
        return "Foste nomeado para a próxima MetalThursday!";
    }

    protected function getMessageLine(User $notifiable): string
    {
        $authorName = $this->metalThursday->author->name;
        // Calcula a data da próxima quinta-feira a partir da data da MT
        $deadline = Carbon::parse($this->metalThursday->date)->next(Carbon::THURSDAY)->format('d/m/Y');

        return "Foste nomeado por {$authorName}! Prepara e submete a tua MetalThursday até à próxima quinta-feira, dia {$deadline}.";
    }

    protected function getActionText(User $notifiable): string
    {
        return 'Ver a MetalThursday';
    }

    protected function getActionUrl(User $notifiable): string
    {
        // Leva o utilizador para a publicação onde foi nomeado
        return route('metalthursday.show', $this->metalThursday);
    }
}
