<?php

declare(strict_types=1);

namespace App\Models\Geografia;

use App\Models\Musica\Artista;
use Carbon\CarbonInterface;
use Database\Factories\Geografia\OrigemGeograficaFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Representa uma origem geográfica disponível na aplicação.
 *
 * Uma origem geográfica pode representar um país, uma nação constituinte,
 * um território ou uma origem internacional agregada.
 *
 * @property int $id
 * @property string $nome
 * @property string $codigo
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read Collection<int, Artista> $artistas
 *
 * @since 2.0.0
 */
class OrigemGeografica extends Model
{
    /** @use HasFactory<OrigemGeograficaFactory> */
    use HasFactory;

    /**
     * Comprimento máximo do nome.
     *
     * @since 2.0.0
     */
    public const COMPRIMENTO_MAXIMO_NOME = 100;

    /**
     * Comprimento mínimo do código.
     *
     * @since 2.0.0
     */
    public const COMPRIMENTO_MINIMO_CODIGO = 2;

    /**
     * Comprimento máximo do código.
     *
     * @since 2.0.0
     */
    public const COMPRIMENTO_MAXIMO_CODIGO = 8;

    /**
     * Nome físico da tabela associada ao modelo.
     *
     * @var string
     *
     * @since 2.0.0
     */
    protected $table = 'origens_geograficas';

    /**
     * Atributos permitidos em operações de atribuição em massa.
     *
     * @var list<string>
     *
     * @since 2.0.0
     */
    protected $fillable = [
        'nome',
        'codigo',
    ];

    /**
     * Cria a factory associada ao modelo.
     *
     * @return OrigemGeograficaFactory Factory das origens geográficas.
     *
     * @since 2.0.0
     */
    protected static function newFactory(): OrigemGeograficaFactory
    {
        return OrigemGeograficaFactory::new();
    }

    /**
     * Normaliza e valida o nome da origem geográfica.
     *
     * Os espaços exteriores e consecutivos são normalizados. Quebras de
     * linha, tabulações e restantes caracteres de controlo não são aceites.
     *
     * @return Attribute<string, string> Atributo do nome.
     *
     * @throws InvalidArgumentException Quando o nome não é válido.
     *
     * @since 2.0.0
     */
    protected function nome(): Attribute
    {
        return Attribute::make(
            set: static function (
                mixed $valor,
            ): string {
                if (! is_string($valor)) {
                    throw new InvalidArgumentException(
                        'O nome da origem geográfica deve ser uma sequência de caracteres.',
                    );
                }

                if (
                    preg_match(
                        '//u',
                        $valor,
                    ) !== 1
                ) {
                    throw new InvalidArgumentException(
                        'O nome da origem geográfica contém texto inválido.',
                    );
                }

                if (
                    preg_match(
                        '/[\x00-\x1F\x7F]/',
                        $valor,
                    ) === 1
                ) {
                    throw new InvalidArgumentException(
                        'O nome da origem geográfica contém caracteres inválidos.',
                    );
                }

                $nomeNormalizado = Str::squish(
                    $valor,
                );

                if ($nomeNormalizado === '') {
                    throw new InvalidArgumentException(
                        'O nome da origem geográfica não pode estar vazio.',
                    );
                }

                if (
                    mb_strlen(
                        $nomeNormalizado,
                    ) > self::COMPRIMENTO_MAXIMO_NOME
                ) {
                    throw new InvalidArgumentException(
                        sprintf(
                            'O nome da origem geográfica não pode exceder %d caracteres.',
                            self::COMPRIMENTO_MAXIMO_NOME,
                        ),
                    );
                }

                return $nomeNormalizado;
            },
        );
    }

    /**
     * Normaliza e valida o código da origem geográfica.
     *
     * O código pode corresponder a um código ISO, a uma subdivisão geográfica
     * ou a um identificador próprio da aplicação.
     *
     * Apenas espaços ASCII exteriores e diferenças de capitalização são
     * normalizados. Restantes caracteres, incluindo caracteres de controlo,
     * permanecem intactos para serem rejeitados pelo formato permitido.
     *
     * @return Attribute<string, string> Atributo do código.
     *
     * @throws InvalidArgumentException Quando o código não é válido.
     *
     * @since 2.0.0
     */
    protected function codigo(): Attribute
    {
        return Attribute::make(
            set: static function (
                mixed $valor,
            ): string {
                if (! is_string($valor)) {
                    throw new InvalidArgumentException(
                        'O código da origem geográfica deve ser uma sequência de caracteres.',
                    );
                }

                $codigoNormalizado = strtoupper(
                    trim(
                        $valor,
                        ' ',
                    ),
                );

                $comprimento = strlen(
                    $codigoNormalizado,
                );

                if (
                    $comprimento < self::COMPRIMENTO_MINIMO_CODIGO
                    || $comprimento > self::COMPRIMENTO_MAXIMO_CODIGO
                    || preg_match(
                        '/\A[A-Z0-9]+(?:-[A-Z0-9]+)*\z/',
                        $codigoNormalizado,
                    ) !== 1
                ) {
                    throw new InvalidArgumentException(
                        sprintf(
                            'O código da origem geográfica deve conter entre %d e %d caracteres alfanuméricos, podendo incluir hífenes interiores.',
                            self::COMPRIMENTO_MINIMO_CODIGO,
                            self::COMPRIMENTO_MAXIMO_CODIGO,
                        ),
                    );
                }

                return $codigoNormalizado;
            },
        );
    }

    /**
     * Obtém os artistas associados à origem geográfica.
     *
     * Os artistas são devolvidos por ordem alfabética.
     *
     * @return HasMany<Artista, $this> Relação com os artistas.
     *
     * @since 2.0.0
     */
    public function artistas(): HasMany
    {
        return $this
            ->hasMany(
                Artista::class,
                'origem_geografica_id',
            )
            ->orderBy(
                'nome',
            )
            ->orderBy(
                'id',
            );
    }
}
