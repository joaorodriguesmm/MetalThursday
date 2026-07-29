<?php

declare(strict_types=1);

namespace App\Models\Notificacoes;

use App\Models\Autenticacao\Utilizador;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Notifications\DatabaseNotification;

/**
 * Representa uma notificação persistida na base de dados.
 *
 * O modelo estende o contrato técnico do Laravel, mas utiliza a tabela em
 * português definida pelo MetalThursday.
 *
 * @property string $id
 * @property string $type
 * @property string $notifiable_type
 * @property int $notifiable_id
 * @property array<string, mixed> $data
 * @property CarbonImmutable|null $read_at
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read Utilizador $notifiable
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
final class NotificacaoPersistida extends DatabaseNotification
{
    /**
     * Nome físico da tabela associada ao modelo.
     *
     * @var string
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    protected $table = 'notificacoes';

    /**
     * Define as conversões automáticas dos atributos.
     *
     * @var array<string, string>
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    protected $casts = [
        'data' => 'array',
        'read_at' => 'immutable_datetime',
    ];

    /**
     * Marca a notificação como lida.
     *
     * A operação é idempotente.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function marcarComoLida(): void
    {
        $this->markAsRead();
    }

    /**
     * Marca a notificação como não lida.
     *
     * A operação é idempotente.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function marcarComoNaoLida(): void
    {
        $this->markAsUnread();
    }

    /**
     * Determina se a notificação já foi lida.
     *
     * @return bool Verdadeiro quando existe uma data de leitura.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function estaLida(): bool
    {
        return $this->read();
    }

    /**
     * Determina se a notificação ainda está por ler.
     *
     * @return bool Verdadeiro quando não existe uma data de leitura.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function estaPorLer(): bool
    {
        return $this->unread();
    }
}
