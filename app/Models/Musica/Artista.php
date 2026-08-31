<?php

declare(strict_types=1);

namespace App\Models\Musica;

use App\Models\Geografia\OrigemGeografica;
use App\Traits\Auditoria\RegistaAutoria;
use Carbon\CarbonInterface;
use Database\Factories\Musica\ArtistaFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use InvalidArgumentException;
use LogicException;

/**
 * Representa um artista musical.
 *
 * Cada artista pertence a uma origem geográfica e pode estar associado a
 * vários géneros musicais.
 *
 * O nome não identifica univocamente o artista. Artistas distintos podem
 * possuir o mesmo nome.
 *
 * @property int $id
 * @property string $nome
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
 */
class Artista extends Model
{
    /** @use HasFactory<ArtistaFactory> */
    use HasFactory;

    use RegistaAutoria;
    use SoftDeletes;

    /**
     * Comprimento máximo do nome.
     *
     * @since 2.0.0
     */
    public const COMPRIMENTO_MAXIMO_NOME = 255;

    /**
     * Nome da tabela intermédia entre artistas e géneros.
     *
     * @since 2.0.0
     */
    private const TABELA_ARTISTA_GENERO =
        'artista_genero';

    /**
     * Nome físico da tabela associada ao modelo.
     *
     * @var string
     *
     * @since 1.0.0
     */
    protected $table = 'artistas';

    /**
     * Atributos permitidos em operações de atribuição em massa.
     *
     * A origem geográfica deve ser associada explicitamente através da
     * relação Eloquent `origemGeografica`.
     *
     * @var list<string>
     *
     * @since 1.0.0
     */
    protected $fillable = [
        'nome',
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
            'origem_geografica_id' => 'integer',

            'criado_por_id' => 'integer',

            'atualizado_por_id' => 'integer',
        ];
    }

    /**
     * Cria a factory associada ao modelo.
     *
     * @return ArtistaFactory Factory dos artistas.
     *
     * @since 2.0.0
     */
    protected static function newFactory(): ArtistaFactory
    {
        return ArtistaFactory::new();
    }

    /**
     * Normaliza e valida o nome do artista.
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
                        'O nome do artista deve ser uma sequência de caracteres.',
                    );
                }

                if (
                    preg_match(
                        '//u',
                        $valor,
                    ) !== 1
                ) {
                    throw new InvalidArgumentException(
                        'O nome do artista contém texto inválido.',
                    );
                }

                if (
                    preg_match(
                        '/[\x00-\x1F\x7F]/',
                        $valor,
                    ) === 1
                ) {
                    throw new InvalidArgumentException(
                        'O nome do artista contém caracteres inválidos.',
                    );
                }

                $nomeNormalizado = Str::squish(
                    $valor,
                );

                if ($nomeNormalizado === '') {
                    throw new InvalidArgumentException(
                        'O nome do artista não pode estar vazio.',
                    );
                }

                if (
                    mb_strlen(
                        $nomeNormalizado,
                    ) > self::COMPRIMENTO_MAXIMO_NOME
                ) {
                    throw new InvalidArgumentException(
                        sprintf(
                            'O nome do artista não pode exceder %d caracteres.',
                            self::COMPRIMENTO_MAXIMO_NOME,
                        ),
                    );
                }

                return $nomeNormalizado;
            },
        );
    }

    /**
     * Obtém o rótulo contextual utilizado na seleção do artista.
     *
     * O nome é complementado pela origem geográfica e pelos géneros para que
     * artistas homónimos permaneçam distinguíveis sem alterar o respetivo nome
     * canónico.
     *
     * @return string Rótulo contextual do artista.
     *
     * @throws LogicException Quando as relações carregadas contêm dados
     *                        persistidos inválidos.
     *
     * @since 2.0.0
     */
    public function obterRotuloSelecao(): string
    {
        $origemGeografica =
            $this->origemGeografica;

        if (! $origemGeografica instanceof OrigemGeografica) {
            throw new LogicException(
                'O artista não possui uma origem geográfica válida.',
            );
        }

        $nomesGeneros = [];

        foreach ($this->generos as $genero) {
            if (! $genero instanceof Genero) {
                throw new LogicException(
                    'O artista possui um género persistido inválido.',
                );
            }

            $nomesGeneros[] =
                $genero->nome;
        }

        $partesContexto = [
            $origemGeografica->nome,
        ];

        if ($nomesGeneros !== []) {
            $partesContexto[] =
                implode(
                    ', ',
                    $nomesGeneros,
                );
        }

        return $this->nome
            .' — '
            .implode(
                ' · ',
                $partesContexto,
            );
    }

    /**
     * Obtém a origem geográfica do artista.
     *
     * @return BelongsTo<OrigemGeografica, $this> Relação com a origem.
     *
     * @since 2.0.0
     */
    public function origemGeografica(): BelongsTo
    {
        return $this->belongsTo(
            OrigemGeografica::class,
            'origem_geografica_id',
        );
    }

    /**
     * Obtém os géneros musicais associados ao artista.
     *
     * Os géneros eliminados logicamente não são incluídos e os restantes são
     * devolvidos por ordem alfabética.
     *
     * @return BelongsToMany<Genero, $this> Relação com os géneros.
     *
     * @since 1.0.0
     */
    public function generos(): BelongsToMany
    {
        return $this
            ->belongsToMany(
                Genero::class,
                self::TABELA_ARTISTA_GENERO,
                'artista_id',
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
