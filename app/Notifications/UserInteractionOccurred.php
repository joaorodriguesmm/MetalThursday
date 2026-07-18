<?php

namespace App\Notifications;

use App\Models\Comment;
use App\Models\MetalThursday;
use App\Models\MtSection;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Define a notificação de interação de utilizador.
 *
 * @since 1.0
 * @version 1.0
 */
class UserInteractionOccurred extends AppNotification
{
    public string $subjectClass;
    public int $subjectId;
    public User $causer;
    public string $actionText;
    private ?Model $retrievedSubject = null;

    /**
     * Cria uma nova notificação de interação de utilizador.
     *
     * @param mixed $interactionSubject - O objeto da interação.
     * @param User $causer - O utilizador que realizou a interação.
     * @param string $actionText - O texto que descreve a interação.
     *
     * @since 1.0
     * @version 1.0
     */
    public function __construct($interactionSubject, User $causer, string $actionText)
    {
        $this->afterCommit();

        $this->subjectClass = get_class($interactionSubject);
        $this->subjectId = $interactionSubject->id;
        $this->causer = $causer;
        $this->actionText = $actionText;
    }

    /**
     * Obtém o objeto da interação.
     *
     * @return Model|null - O objeto da interação.
     *
     * @since 1.0
     * @version 1.0
     */
    private function getInteractionSubject(): ?Model
    {
        if ($this->retrievedSubject) {
            return $this->retrievedSubject;
        }
        return $this->retrievedSubject = $this->subjectClass::find($this->subjectId);
    }

    /**
     * Obtém o post pai (MetalThursday) da interação.
     *
     * @return MetalThursday|null - O post pai (MetalThursday) da interação.
     *
     * @since 1.0
     * @version 1.0
     */
    private function getParentPost(): ?MetalThursday
    {
        $subject = $this->getInteractionSubject();

        if ($subject instanceof MetalThursday) {
            return $subject;
        }
        if ($subject instanceof MtSection) {
            return $subject->metalThursday;
        }
        if ($subject instanceof Comment) {
            return $subject->metalThursday()->first();
        }
        return null;
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
    protected function shouldSendEmail(User $notifiable): bool
    {
        $parentPost = $this->getParentPost();
        if (!$parentPost) {
            return false;
        }

        $isAuthor = $notifiable->id === $parentPost->author_id;

        return $notifiable->hasEmailPermission('all')
            || $notifiable->hasEmailPermission('new-interactions')
            || ($isAuthor && $notifiable->hasEmailPermission('interactions-on-my-posts'));
    }

    /**
     * Obtém a representação da notificação em array para guardar na base de dados.
     *
     * @param User $notifiable - O utilizador que recebe a notificação.
     * @return array - A representação da notificação em array.
     *
     * @since 1.0
     * @version 1.0
     */
    public function toArray(User $notifiable): array
    {
        return [
            'message' => $this->getMessageLine($notifiable),
            'url'     => $this->getActionUrl($notifiable),
            'icon'    => 'bi-chat-quote-fill',
            'color'   => 'text-info',
        ];
    }

    /**
     * Obtém o assunto da notificação.
     *
     * @param User $notifiable - O utilizador que recebe a notificação.
     * @return string - O assunto da notificação.
     *
     * @since 1.0
     * @version 1.0
     */
    protected function getSubject(User $notifiable): string
    {
        $action = $this->actionText;
        $subjectName = $this->getSubjectName();
        $baseMessage = '';

        $interactionSubject = $this->getInteractionSubject();

        if ($interactionSubject instanceof Comment) {
            if ($notifiable->id === $interactionSubject->user_id) {
                $baseMessage = " {$action} teu comentário em \"{$subjectName}\"";
            } else {
                $baseMessage = " {$action} um comentário de {$interactionSubject->user->name} em \"{$subjectName}\"";
            }
        } else {
            $baseMessage = " {$action} {$subjectName}";
        }

        $corrections = [
            ' do o ' => ' do ', ' do a ' => ' da ', ' em o ' => ' no ',
            ' em a ' => ' na ', ' a o ' => ' ao ', ' a a ' => ' à ',
        ];

        $cleanMessage = str_replace(array_keys($corrections), array_values($corrections), " " . $baseMessage);

        return "Nova Interação: {$this->causer->name} " . trim($cleanMessage);
    }

    /**
     * Obtém a linha da mensagem da notificação.
     *
     * @param User $notifiable - O utilizador que recebe a notificação.
     * @return string - A linha da mensagem da notificação.
     *
     * @since 1.0
     * @version 1.0
     */
    protected function getMessageLine(User $notifiable): string
    {
        $causerName = $this->causer->name;
        $action = $this->actionText;
        $subjectName = $this->getSubjectName();

        $baseMessage = '';
        $interactionSubject = $this->getInteractionSubject();

        if ($interactionSubject instanceof Comment) {
            if ($notifiable->id === $interactionSubject->user_id) {
                $baseMessage = "{$causerName} {$action} teu comentário em {$subjectName}.";
            } else {
                $commentAuthorName = $interactionSubject->user->name;
                $baseMessage = "{$causerName} {$action} um comentário de {$commentAuthorName} em {$subjectName}.";
            }
        } else {
            $baseMessage = "{$causerName} {$action} {$subjectName}.";
        }

        $corrections = [
            ' do o ' => ' do ', ' do a ' => ' da ', ' em o ' => ' no ',
            ' em a ' => ' na ', ' a o ' => ' ao ', ' a a ' => ' à ',
        ];

        return str_replace(array_keys($corrections), array_values($corrections), $baseMessage);
    }

    /**
     * Obtém o texto do botão da notificação.
     *
     * @param User $notifiable - O utilizador que recebe a notificação.
     * @return string - O texto do botão da notificação.
     *
     * @since 1.0
     * @version 1.0
     */
    protected function getActionText(User $notifiable): string
    {
        return 'Ver Atividade';
    }

    /**
     * Obtém a URL do botão da notificação.
     *
     * @param User $notifiable - O utilizador que recebe a notificação.
     * @return string - A URL do botão da notificação.
     *
     * @since 1.0
     * @version 1.0
     */
    protected function getActionUrl(User $notifiable): string
    {
        $parentPost = $this->getParentPost();

        if (!$parentPost) {
            return route('home');
        }

        return route('metalthursday.show', $parentPost);
    }

    /**
     * Obtém o nome do assunto da notificação.
     *
     * @return string - O nome do assunto da notificação.
     *
     * @since 1.0
     * @version 1.0
     */
    private function getSubjectName(): string
    {
        $subject = $this->getInteractionSubject();
        if ($subject instanceof Comment) {
            return $this->getDetailedDescription($subject->commentable);
        }
        return $this->getDetailedDescription($subject);
    }

    /**
     * Gera uma descrição detalhada para um objeto (MetalThursday ou MtSection).
     *
     * @param mixed $item
     * @return string
     */
    private function getDetailedDescription($item): string
    {
        if (!$item) {
            return 'algo que foi entretanto removido';
        }

        if ($item instanceof MetalThursday) {
            $editionName = $item->edition?->name ?? 'Edição Desconhecida';
            $weekNumber = $item->week_number_in_edition ?? 'N/A';
            $title = "a MetalThursday {$editionName} - Semana {$weekNumber}";
            if ($item->name) {
                $title .= " - {$item->name}";
            }
            return $title;
        }

        if ($item instanceof MtSection) {
            $sectionTypeName = $item->sectionType?->name ?? 'secção';
            $bandName = $item->band?->name ?? 'Banda Desconhecida';
            $sectionTitle = $item->title ?? 'sem título';
            if (!$item->sectionType?->has_details) {
                return "a secção de texto {$sectionTitle}";
            }
            $article = substr(strtolower($sectionTypeName), -1) === 'a' ? 'a' : 'o';
            return "{$article} {$sectionTypeName} {$bandName} - {$sectionTitle}";
        }

        return 'algo';
    }
}
