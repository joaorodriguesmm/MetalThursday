<?php

declare(strict_types=1);

namespace App\Models\Musica;

use App\Models\Geografia\Pais;
use App\Traits\Auditoria\RegistaAutoria;
use Carbon\CarbonInterface;
use Database\Factories\Musica\BandaFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use InvalidArgumentException;

/**
 * Representa uma banda musical.
 *
 * Cada banda pertence a um país e pode estar associada a vários géneros
 * musicais.
 *
 * @property int $id
 * @property string $nome
 * @property int $pais_id
 * @property int|null $criado_por_id
 * @property int|null $atualizado_por_id
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property CarbonInterface|null $deleted_at
 * @property-read Pais $pais
 * @property-read Collection<int, Genero> $generos
 *
 * @since 1.0.0
 *
 * @version 2.1.0
 */
class Banda extends Model
{
    /** @use HasFactory<BandaFactory> */
    use HasFactory;

    use RegistaAutoria;
    use SoftDeletes;

    /**
     * Nome da tabela intermédia entre bandas e géneros.
     *
     * @var string
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const TABELA_BANDA_GENERO =
        'banda_genero';

    /**
     * Nome físico da tabela associada ao modelo.
     *
     * @var string
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    protected $table = 'bandas';

    /**
     * Atributos permitidos em operações de atribuição em massa.
     *
     * O país deve ser associado explicitamente através da relação `pais()`.
     * As colunas de auditoria são preenchidas automaticamente pelo trait
     * {@see RegistaAutoria}.
     *
     * @var array<int, string>
     *
     * @since 1.0.0
     *
     * @version 2.1.0
     */
    protected $fillable = [
        'nome',
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
            'pais_id' => 'integer',
            'criado_por_id' => 'integer',
            'atualizado_por_id' => 'integer',
        ];
    }

    /**
     * Cria a factory associada ao modelo.
     *
     * A associação é explícita porque o modelo e a factory se encontram em
     * namespaces próprios.
     *
     * @return BandaFactory Factory das bandas.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    protected static function newFactory(): BandaFactory
    {
        return BandaFactory::new();
    }

    /**
     * Normaliza o nome da banda antes da persistência.
     *
     * @return Attribute<string, string> Atributo do nome.
     *
     * @throws InvalidArgumentException Quando o nome está vazio.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    protected function nome(): Attribute
    {
        return Attribute::make(
            set: static function (
                mixed $valor,
            ): string {
                $nomeNormalizado = trim(
                    (string) $valor,
                );

                if ($nomeNormalizado === '') {
                    throw new InvalidArgumentException(
                        'O nome da banda não pode estar vazio.',
                    );
                }

                return $nomeNormalizado;
            },
        );
    }

    /**
     * Obtém o país de origem da banda.
     *
     * @return BelongsTo<Pais, $this> Relação com o país.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function pais(): BelongsTo
    {
        return $this->belongsTo(
            Pais::class,
            'pais_id',
        );
    }

    /**
     * Obtém os géneros musicais associados à banda.
     *
     * Os géneros eliminados logicamente não são incluídos e os restantes
     * são devolvidos por ordem alfabética.
     *
     * @return BelongsToMany<Genero, $this> Relação com os géneros.
     *
     * @since 1.0.0
     *
     * @version 2.1.0
     */
    public function generos(): BelongsToMany
    {
        return $this
            ->belongsToMany(
                Genero::class,
                self::TABELA_BANDA_GENERO,
                'banda_id',
                'genero_id',
            )
            ->orderBy('generos.nome')
            ->orderBy('generos.id');
    }
}
