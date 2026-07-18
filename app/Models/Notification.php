<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Gere a tabela 'notifications' da base de dados.
 *
 * @since 1.0
 * @version 1.0
 */
class Notification extends Model
{
    public $incrementing = false;
    protected $keyType   = 'string';

    protected $fillable = [
        'id',
        'type',
        'notifiable_type',
        'notifiable_id',
        'data',
        'read_at',
    ];

    protected $casts = [
        'data'    => 'array',
        'read_at' => 'datetime',
    ];

    /**
     * Obtém o utilizador que recebe a notificação.
     *
     * @return MorphTo - Relação com a tabela users.
     *
     * @since 1.0
     * @version 1.0
     */
    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Marca a notificação como lida.
     *
     * @return void
     *
     * @since 1.0
     * @version 1.0
     */
    public function markAsRead(): void
    {
        if (is_null($this->read_at)) {
            $this->forceFill(['read_at' => $this->freshTimestamp()])->save();
        }
    }

    /**
     * Marca a notificação como não lida.
     *
     * @return void
     *
     * @since 1.0
     * @version 1.0
     */
    public function markAsUnread(): void
    {
        if (!is_null($this->read_at)) {
            $this->forceFill(['read_at' => null])->save();
        }
    }

    /**
     * Obtém se a notificação foi lida.
     *
     * @return bool - Verdadeiro se a notificação foi lida.
     *
     * @since 1.0
     * @version 1.0
     */
    public function read(): bool
    {
        return $this->read_at !== null;
    }

    /**
     * Obtém se a notificação não foi lida.
     *
     * @return bool - Verdadeiro se a notificação não foi lida.
     *
     * @since 1.0
     * @version 1.0
     */
    public function unread(): bool
    {
        return $this->read_at === null;
    }
}
