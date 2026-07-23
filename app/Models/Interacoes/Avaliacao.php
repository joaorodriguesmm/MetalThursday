<?php

declare(strict_types=1);

namespace App\Models\Interacoes;

use App\Models\Autenticacao\Utilizador;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Representa uma avaliação atribuída a uma entidade da aplicação.
 *
 * A entidade avaliada é associada através de uma relação polimórfica,
 * permitindo avaliar diferentes tipos de conteúdo, como uma MetalThursday
 * ou uma das respetivas secções.
 *
 * Cada utilizador pode atribuir apenas uma avaliação à mesma entidade,
 * conforme a restrição única definida na tabela `avaliacoes`.
 *
 * @property int $id
 * @property int $utilizador_id
 * @property float $pontuacao
 * @property string $tipo_avaliavel
 * @property int $avaliavel_id
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read Utilizador $utilizador
 * @property-read Model|null $avaliavel
 *
 * @since 1.0.0
 *
 * @version 2.1.0
 */
class Avaliacao extends Model
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
    protected $table = 'avaliacoes';

    /**
     * Atributos permitidos em operações de atribuição em massa.
     *
     * As colunas da relação polimórfica devem ser preenchidas através da
     * relação Eloquent da entidade avaliada.
     *
     * @var array<int, string>
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    protected $fillable = [
        'utilizador_id',
        'pontuacao',
    ];

    /**
     * Define as conversões automáticas dos atributos.
     *
     * @return array<string, string> Conversões dos atributos.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    protected function casts(): array
    {
        return [
            'utilizador_id' => 'integer',
            'avaliavel_id' => 'integer',
            'pontuacao' => 'float',
        ];
    }

    /**
     * Obtém a entidade que foi avaliada.
     *
     * Os nomes das colunas são indicados explicitamente porque utilizam
     * nomenclatura portuguesa.
     *
     * @return MorphTo<Model, $this> Relação com a entidade avaliada.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function avaliavel(): MorphTo
    {
        return $this->morphTo(
            name: 'avaliavel',
            type: 'tipo_avaliavel',
            id: 'avaliavel_id',
        );
    }

    /**
     * Obtém o utilizador que atribuiu a avaliação.
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
