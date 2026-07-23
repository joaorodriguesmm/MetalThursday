<?php

declare(strict_types=1);

namespace App\Models\Interacoes;

use App\Models\Autenticacao\Utilizador;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Representa um gosto atribuído a um comentário.
 *
 * Cada utilizador pode gostar apenas uma vez do mesmo comentário. Esta regra
 * é garantida pela restrição única existente na tabela `gostos`.
 *
 * @property int $id
 * @property int $utilizador_id
 * @property int $comentario_id
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read Comentario $comentario
 * @property-read Utilizador $utilizador
 *
 * @since 1.0.0
 *
 * @version 2.1.0
 */
class Gosto extends Model
{
    /**
     * Nome físico da tabela associada ao modelo.
     *
     * @var string
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    protected $table = 'gostos';

    /**
     * Atributos permitidos em operações de atribuição em massa.
     *
     * O comentário deve ser associado através da relação `gostos()` do
     * comentário. Apenas o utilizador é recebido na criação do registo.
     *
     * @var array<int, string>
     *
     * @since 1.0.0
     *
     * @version 2.1.0
     */
    protected $fillable = [
        'utilizador_id',
    ];

    /**
     * Define as conversões automáticas dos atributos.
     *
     * @return array<string, string> Conversões dos atributos.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    protected function casts(): array
    {
        return [
            'utilizador_id' => 'integer',
            'comentario_id' => 'integer',
        ];
    }

    /**
     * Obtém o comentário ao qual foi atribuído o gosto.
     *
     * O comentário continua acessível caso tenha sido eliminado logicamente,
     * preservando a relação histórica do gosto.
     *
     * @return BelongsTo<Comentario, $this> Relação com o comentário.
     *
     * @since 1.0.0
     *
     * @version 2.1.0
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

    /**
     * Obtém o utilizador que atribuiu o gosto.
     *
     * @return BelongsTo<Utilizador, $this> Relação com o utilizador.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function utilizador(): BelongsTo
    {
        return $this->belongsTo(
            Utilizador::class,
            'utilizador_id',
        );
    }
}
