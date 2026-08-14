<?php

declare(strict_types=1);

namespace App\Models\Interacoes;

use App\Models\Autenticacao\Utilizador;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Representa um gosto atribuído por um utilizador a um comentário.
 *
 * Cada utilizador pode atribuir apenas um gosto a cada comentário. Essa
 * unicidade é garantida pela base de dados através do par
 * `utilizador_id` e `comentario_id`.
 *
 * @property int $id
 * @property int $utilizador_id
 * @property int $comentario_id
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read Utilizador $utilizador
 * @property-read Comentario $comentario
 *
 * @since 1.0.0
 */
class Gosto extends Model
{
    /**
     * Nome físico da tabela associada ao modelo.
     *
     * @var string
     *
     * @since 1.0.0
     */
    protected $table = 'gostos';

    /**
     * Atributos permitidos em operações de atribuição em massa.
     *
     * Os identificadores são definidos exclusivamente pelo fluxo responsável
     * por alternar o gosto de um utilizador autenticado.
     *
     * @var list<string>
     *
     * @since 1.0.0
     */
    protected $fillable = [
        'utilizador_id',
        'comentario_id',
    ];

    /**
     * Define as conversões automáticas dos identificadores.
     *
     * @return array<string, string> Conversões dos atributos.
     *
     * @since 2.0.0
     */
    protected function casts(): array
    {
        return [
            'utilizador_id' => 'integer',

            'comentario_id' => 'integer',
        ];
    }

    /**
     * Obtém o utilizador que atribuiu o gosto.
     *
     * @return BelongsTo<Utilizador, $this> Relação com o utilizador.
     *
     * @since 1.0.0
     */
    public function utilizador(): BelongsTo
    {
        return $this->belongsTo(
            Utilizador::class,
            'utilizador_id',
        );
    }

    /**
     * Obtém o comentário que recebeu o gosto.
     *
     * A relação inclui comentários eliminados logicamente porque o registo do
     * gosto apenas é eliminado automaticamente quando o comentário é
     * eliminado fisicamente.
     *
     * @return BelongsTo<Comentario, $this> Relação com o comentário.
     *
     * @since 1.0.0
     */
    public function comentario(): BelongsTo
    {
        return $this
            ->belongsTo(
                Comentario::class,
                'comentario_id',
            )
            ->withTrashed();
    }
}
