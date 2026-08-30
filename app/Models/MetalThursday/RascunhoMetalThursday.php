<?php

declare(strict_types=1);

namespace App\Models\MetalThursday;

use Carbon\CarbonInterface;
use Database\Factories\MetalThursday\RascunhoMetalThursdayFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Representa um rascunho privado de preparação de uma MetalThursday.
 *
 * Cada rascunho pertence a uma única reserva e contém apenas os dados
 * editáveis do formulário. A data e o responsável continuam a ser
 * determinados pela própria reserva e não são duplicados no rascunho.
 *
 * O rascunho pode conter informação incompleta. A validação definitiva do
 * conteúdo ocorre apenas quando a preparação é convertida numa MetalThursday.
 *
 * @property int $id
 * @property int $reserva_metal_thursday_id
 * @property array<string, mixed> $dados
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read ReservaMetalThursday $reservaMetalThursday
 *
 * @since 2.0.0
 */
class RascunhoMetalThursday extends Model
{
    /** @use HasFactory<RascunhoMetalThursdayFactory> */
    use HasFactory;

    /**
     * Nome físico da tabela associada ao modelo.
     *
     * @var string
     *
     * @since 2.0.0
     */
    protected $table = 'rascunhos_metal_thursday';

    /**
     * Atributos permitidos em operações de atribuição em massa.
     *
     * A reserva é associada explicitamente pela camada responsável pela
     * persistência do rascunho.
     *
     * @var list<string>
     *
     * @since 2.0.0
     */
    protected $fillable = [
        'dados',
    ];

    /**
     * Define as conversões automáticas dos atributos.
     *
     * @return array<string, string> Conversões dos atributos.
     *
     * @since 2.0.0
     */
    protected function casts(): array
    {
        return [
            'reserva_metal_thursday_id' => 'integer',

            'dados' => 'array',
        ];
    }

    /**
     * Cria a factory associada ao modelo.
     *
     * @return RascunhoMetalThursdayFactory Factory dos rascunhos.
     *
     * @since 2.0.0
     */
    protected static function newFactory(): RascunhoMetalThursdayFactory
    {
        return RascunhoMetalThursdayFactory::new();
    }

    /**
     * Obtém a reserva à qual pertence o rascunho.
     *
     * @return BelongsTo<ReservaMetalThursday, $this> Relação com a reserva.
     *
     * @since 2.0.0
     */
    public function reservaMetalThursday(): BelongsTo
    {
        return $this->belongsTo(
            ReservaMetalThursday::class,
            'reserva_metal_thursday_id',
        );
    }
}
