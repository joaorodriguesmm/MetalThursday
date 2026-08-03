<?php

declare(strict_types=1);

namespace App\Models\Interacoes;

use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\MetalThursday;
use App\Models\MetalThursday\SeccaoMetalThursday;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Representa uma audição registada por um utilizador.
 *
 * Uma audição pode pertencer a uma MetalThursday ou a uma das respetivas
 * secções. Cada utilizador pode marcar cada entidade como ouvida apenas uma
 * vez.
 *
 * A unicidade é garantida pela base de dados através do conjunto
 * `utilizador_id`, `tipo_audivel` e `audivel_id`.
 *
 * @property int $id
 * @property int $utilizador_id
 * @property 'metal_thursday'|'seccao_metal_thursday' $tipo_audivel
 * @property int $audivel_id
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read Utilizador $utilizador
 * @property-read MetalThursday|SeccaoMetalThursday|null $audivel
 *
 * @since 1.0.0
 *
 * @version 3.0.0
 */
class Audicao extends Model
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
    protected $table = 'audicoes';

    /**
     * Atributos permitidos em operações de atribuição em massa.
     *
     * O tipo e o identificador da entidade ouvida são preenchidos pela
     * relação polimórfica e não podem ser atribuídos diretamente através de
     * dados externos.
     *
     * @var list<string>
     *
     * @since 1.0.0
     *
     * @version 3.0.0
     */
    protected $fillable = [
        'utilizador_id',
    ];

    /**
     * Define as conversões automáticas dos identificadores.
     *
     * @return array<string, string> Conversões dos atributos.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    protected function casts(): array
    {
        return [
            'utilizador_id' => 'integer',

            'audivel_id' => 'integer',
        ];
    }

    /**
     * Obtém o utilizador que registou a audição.
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

    /**
     * Obtém a entidade associada à audição.
     *
     * Os aliases polimórficos permitidos são:
     *
     * - `metal_thursday`;
     * - `seccao_metal_thursday`.
     *
     * A relação pode devolver nulo quando a entidade foi eliminada
     * logicamente e não foi incluída explicitamente na consulta.
     *
     * @return MorphTo<Model, $this> Relação com a entidade ouvida.
     *
     * @since 1.0.0
     *
     * @version 3.0.0
     */
    public function audivel(): MorphTo
    {
        return $this->morphTo(
            name: 'audivel',
            type: 'tipo_audivel',
            id: 'audivel_id',
        );
    }
}
