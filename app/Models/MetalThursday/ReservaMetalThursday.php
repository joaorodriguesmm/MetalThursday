<?php

declare(strict_types=1);

namespace App\Models\MetalThursday;

use App\Models\Autenticacao\Utilizador;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Database\Factories\MetalThursday\ReservaMetalThursdayFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;

/**
 * Representa uma reserva para uma MetalThursday.
 *
 * Cada reserva corresponde a uma quinta-feira única. Pode permanecer sem
 * responsável quando não existe nenhum utilizador elegível e continua
 * pendente até ser associada à MetalThursday que a cumpriu.
 *
 * @property int $id
 * @property CarbonImmutable $data
 * @property int|null $responsavel_id
 * @property int|null $metal_thursday_id
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read Utilizador|null $responsavel
 * @property-read MetalThursday|null $metalThursday
 *
 * @since 2.0.0
 */
class ReservaMetalThursday extends Model
{
    /** @use HasFactory<ReservaMetalThursdayFactory> */
    use HasFactory;

    /**
     * Nome físico da tabela associada ao modelo.
     *
     * @var string
     *
     * @since 2.0.0
     */
    protected $table = 'reservas_metal_thursday';

    /**
     * Atributos permitidos em operações de atribuição em massa.
     *
     * @var list<string>
     *
     * @since 2.0.0
     */
    protected $fillable = [
        'data',
        'responsavel_id',
        'metal_thursday_id',
    ];

    /**
     * Regista as validações executadas antes da persistência.
     *
     * @since 2.0.0
     */
    protected static function booted(): void
    {
        static::saving(
            static function (
                self $reserva,
            ): void {
                $reserva->validarData();
            },
        );
    }

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
            'data' => 'immutable_date',

            'responsavel_id' => 'integer',

            'metal_thursday_id' => 'integer',
        ];
    }

    /**
     * Cria a factory associada ao modelo.
     *
     * @return ReservaMetalThursdayFactory Factory das reservas.
     *
     * @since 2.0.0
     */
    protected static function newFactory(): ReservaMetalThursdayFactory
    {
        return ReservaMetalThursdayFactory::new();
    }

    /**
     * Obtém o utilizador responsável pela reserva.
     *
     * A relação pode ser nula quando não existia nenhum utilizador elegível
     * ou quando o utilizador foi posteriormente eliminado.
     *
     * @return BelongsTo<Utilizador, $this> Relação com o responsável.
     *
     * @since 2.0.0
     */
    public function responsavel(): BelongsTo
    {
        return $this->belongsTo(
            Utilizador::class,
            'responsavel_id',
        );
    }

    /**
     * Obtém a MetalThursday que cumpriu a reserva.
     *
     * Uma MetalThursday eliminada logicamente continua acessível para
     * preservar o histórico da reserva.
     *
     * @return BelongsTo<MetalThursday, $this> Relação com a MetalThursday.
     *
     * @since 2.0.0
     */
    public function metalThursday(): BelongsTo
    {
        return $this
            ->belongsTo(
                MetalThursday::class,
                'metal_thursday_id',
            )
            ->withTrashed();
    }

    /**
     * Indica se a reserva ainda está pendente.
     *
     * @return bool Verdadeiro quando nenhuma MetalThursday a cumpriu.
     *
     * @since 2.0.0
     */
    public function estaPendente(): bool
    {
        return $this->metal_thursday_id === null;
    }

    /**
     * Indica se a reserva já foi cumprida.
     *
     * @return bool Verdadeiro quando existe uma MetalThursday associada.
     *
     * @since 2.0.0
     */
    public function estaCumprida(): bool
    {
        return ! $this->estaPendente();
    }

    /**
     * Valida a data reservada.
     *
     * Cada reserva corresponde obrigatoriamente a uma quinta-feira.
     *
     * @throws InvalidArgumentException Quando a data não é válida ou não
     *                                  corresponde a uma quinta-feira.
     *
     * @since 2.0.0
     */
    private function validarData(): void
    {
        $data = $this->data;

        if (! $data instanceof CarbonInterface) {
            throw new InvalidArgumentException(
                'A data da reserva é obrigatória.',
            );
        }

        if (! $data->isThursday()) {
            throw new InvalidArgumentException(
                'A data da reserva tem de corresponder a uma quinta-feira.',
            );
        }
    }
}
