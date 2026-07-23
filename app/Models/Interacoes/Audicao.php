<?php

declare(strict_types=1);

namespace App\Models\Interacoes;

use App\Models\Autenticacao\Utilizador;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Representa o registo de audição de uma entidade da aplicação.
 *
 * A entidade ouvida é associada através de uma relação polimórfica, permitindo
 * registar audições de diferentes tipos de conteúdo.
 *
 * Cada utilizador pode registar apenas uma audição para a mesma entidade,
 * conforme a restrição única definida na tabela `audicoes`.
 *
 * @property int $id
 * @property int $utilizador_id
 * @property string $tipo_audivel
 * @property int $audivel_id
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read Utilizador $utilizador
 * @property-read Model|null $audivel
 *
 * @since 1.0.0
 *
 * @version 2.1.0
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
     * As colunas da relação polimórfica devem ser atribuídas através da
     * relação Eloquent da entidade ouvida.
     *
     * @var array<int, string>
     *
     * @since 1.0.0
     *
     * @version 2.0.0
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
            'audivel_id' => 'integer',
        ];
    }

    /**
     * Obtém a entidade que foi ouvida.
     *
     * Os nomes das colunas são indicados explicitamente porque utilizam
     * nomenclatura portuguesa.
     *
     * @return MorphTo<Model, $this> Relação com a entidade ouvida.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function audivel(): MorphTo
    {
        return $this->morphTo(
            name: 'audivel',
            type: 'tipo_audivel',
            id: 'audivel_id',
        );
    }

    /**
     * Obtém o utilizador responsável pela audição.
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
