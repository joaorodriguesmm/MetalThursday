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
 * As operações de leitura são executadas diretamente pelos controladores
 * através de consultas condicionais, evitando métodos de conveniência sem
 * utilização e atualizações não atómicas.
 *
 * @property string $id
 * @property string $type
 * @property string $notifiable_type
 * @property int $notifiable_id
 * @property array<string, mixed> $data
 * @property CarbonImmutable|null $read_at
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read Utilizador|null $notifiable
 *
 * @since 2.0.0
 */
final class NotificacaoPersistida extends DatabaseNotification
{
    /**
     * Nome físico da tabela associada ao modelo.
     *
     * @var string
     *
     * @since 2.0.0
     */
    protected $table = 'notificacoes';

    /**
     * Define as conversões automáticas dos atributos.
     *
     * @var array<string, string>
     *
     * @since 2.0.0
     */
    protected $casts = [
        'data' => 'array',
        'read_at' => 'immutable_datetime',
    ];
}
