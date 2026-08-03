<?php

declare(strict_types=1);

namespace App\Models\Musica;

use App\Models\Geografia\OrigemGeografica;
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
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Representa uma banda musical.
 *
 * Cada banda pertence a uma origem geográfica e pode estar associada a vários
 * géneros musicais.
 *
 * A coluna gerada `nome_ativo` garante a unicidade do nome entre bandas não
 * eliminadas logicamente e não constitui um atributo editável da aplicação.
 *
 * @property int $id
 * @property string $nome
 * @property string|null $nome_ativo
 * @property int $origem_geografica_id
 * @property int|null $criado_por_id
 * @property int|null $atualizado_por_id
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property CarbonInterface|null $deleted_at
 * @property-read OrigemGeografica $origemGeografica
 * @property-read Collection<int, Genero> $generos
 *
 * @since 1.0.0
 *
 * @version 3.1.0
 */
class Banda extends Model
{
    /** @use HasFactory<BandaFactory> */
    use HasFactory;

    use RegistaAutoria;
    use SoftDeletes;

    /**
     * Comprimento máximo do nome.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public const COMPRIMENTO_MAXIMO_NOME = 255;

    /**
     * Nome da tabela intermédia entre bandas e géneros.
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
     * A origem geográfica deve ser associada explicitamente através da
     * relação {@see origemGeografica()}. A coluna `nome_ativo` é gerada pela
     * base de dados e não pode ser atribuída pela aplicação.
     *
     * @var list<string>
     *
     * @since 1.0.0
     *
     * @version 3.1.0
     */
    protected $fillable = [
        'nome',
    ];

    /**
     * Atributos internos omitidos das representações serializadas.
     *
     * @var list<string>
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    protected $hidden = [
        'nome_ativo',
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
            'origem_geografica_id' => 'integer',

            'criado_por_id' => 'integer',

            'atualizado_por_id' => 'integer',
        ];
    }

    /**
     * Cria a factory associada ao modelo.
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
     * Normaliza e valida o nome da banda.
     *
     * @return Attribute<string, string> Atributo do nome.
     *
     * @throws InvalidArgumentException Quando o nome não é válido.
     *
     * @since 2.0.0
     *
     * @version 2.1.0
     */
    protected function nome(): Attribute
    {
        return Attribute::make(
            set: static function (
                mixed $valor,
            ): string {
                if (! is_string($valor)) {
                    throw new InvalidArgumentException(
                        'O nome da banda deve ser uma sequência de caracteres.',
                    );
                }

                if (
                    preg_match(
                        '//u',
                        $valor,
                    ) !== 1
                ) {
                    throw new InvalidArgumentException(
                        'O nome da banda contém texto inválido.',
                    );
                }

                if (
                    preg_match(
                        '/[\x00-\x1F\x7F]/',
                        $valor,
                    ) === 1
                ) {
                    throw new InvalidArgumentException(
                        'O nome da banda contém caracteres inválidos.',
                    );
                }

                $nomeNormalizado = Str::squish(
                    $valor,
                );

                if ($nomeNormalizado === '') {
                    throw new InvalidArgumentException(
                        'O nome da banda não pode estar vazio.',
                    );
                }

                if (
                    mb_strlen(
                        $nomeNormalizado,
                    ) > self::COMPRIMENTO_MAXIMO_NOME
                ) {
                    throw new InvalidArgumentException(
                        sprintf(
                            'O nome da banda não pode exceder %d caracteres.',
                            self::COMPRIMENTO_MAXIMO_NOME,
                        ),
                    );
                }

                return $nomeNormalizado;
            },
        );
    }

    /**
     * Obtém a origem geográfica da banda.
     *
     * @return BelongsTo<OrigemGeografica, $this> Relação com a origem.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function origemGeografica(): BelongsTo
    {
        return $this->belongsTo(
            OrigemGeografica::class,
            'origem_geografica_id',
        );
    }

    /**
     * Obtém os géneros musicais associados à banda.
     *
     * Os géneros eliminados logicamente não são incluídos e os restantes são
     * devolvidos por ordem alfabética.
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
            ->orderBy(
                'generos.nome',
            )
            ->orderBy(
                'generos.id',
            );
    }
}
