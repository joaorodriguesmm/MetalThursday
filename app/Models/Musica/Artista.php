<?php

declare(strict_types=1);

namespace App\Models\Musica;

use App\Enumeracoes\EstadoAtividadeArtista;
use App\Models\Comum\Ligacao;
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
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use InvalidArgumentException;
use LogicException;

/**
 * Representa um artista musical.
 *
 * Cada artista pode possuir uma origem geográfica, metadados de atividade,
 * biografia, imagem, identificadores externos e várias ligações.
 *
 * O nome não identifica univocamente o artista. Artistas distintos podem
 * possuir o mesmo nome.
 *
 * @property int $id
 * @property string $nome
 * @property int|null $origem_geografica_id
 * @property int|null $ano_inicio_atividade
 * @property int|null $ano_fim_atividade
 * @property EstadoAtividadeArtista|null $estado_atividade
 * @property string|null $biografia
 * @property string|null $imagem
 * @property string|null $musicbrainz_id
 * @property int|null $discogs_id
 * @property int|null $criado_por_id
 * @property int|null $atualizado_por_id
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property CarbonInterface|null $deleted_at
 * @property-read string|null $url_musicbrainz
 * @property-read string|null $url_discogs
 * @property-read string|null $url_imagem
 * @property-read OrigemGeografica|null $origemGeografica
 * @property-read Collection<int, Genero> $generos
 * @property-read Collection<int, Ligacao> $ligacoes
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
     * Comprimento máximo da biografia persistida.
     *
     * @since 2.0.0
     */
    public const COMPRIMENTO_MAXIMO_BIOGRAFIA = 65535;

    /**
     * Comprimento máximo do endereço externo da imagem.
     *
     * @since 2.0.0
     */
    public const COMPRIMENTO_MAXIMO_URL_IMAGEM = 2048;

    /**
     * Primeiro ano aceite quando é conhecida a atividade do artista.
     *
     * @since 2.0.0
     */
    public const ANO_MINIMO_ATIVIDADE = 1000;

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
     * As relações são geridas explicitamente pelo controlador.
     *
     * @var list<string>
     *
     * @since 1.0.0
     */
    protected $fillable = [
        'nome',
        'ano_inicio_atividade',
        'ano_fim_atividade',
        'estado_atividade',
        'biografia',
        'imagem',
        'musicbrainz_id',
        'discogs_id',
    ];

    /**
     * Define as conversões automáticas dos atributos persistidos.
     *
     * @return array<string, string> Conversões dos atributos.
     *
     * @since 2.0.0
     */
    protected function casts(): array
    {
        return [
            'origem_geografica_id' => 'integer',
            'ano_inicio_atividade' => 'integer',
            'ano_fim_atividade' => 'integer',
            'estado_atividade' => EstadoAtividadeArtista::class,
            'discogs_id' => 'integer',
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
     * @throws InvalidArgumentException Quando o nome não é uma sequência válida,
     *                                  está vazio ou excede o limite permitido.
     *
     * @since 2.0.0
     */
    protected function nome(): Attribute
    {
        return Attribute::make(
            set: static function (mixed $valor): string {
                if (! is_string($valor)) {
                    throw new InvalidArgumentException(
                        'O nome do artista deve ser uma sequência de caracteres.',
                    );
                }

                if (
                    preg_match('//u', $valor) !== 1
                    || preg_match('/[\x00-\x1F\x7F]/', $valor) === 1
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
     * Normaliza a biografia, preservando a estrutura de parágrafos.
     *
     * @return Attribute<string|null, string|null> Atributo da biografia.
     *
     * @throws InvalidArgumentException Quando a biografia contém texto inválido
     *                                  ou excede o limite permitido.
     *
     * @since 2.0.0
     */
    protected function biografia(): Attribute
    {
        return Attribute::make(
            set: static function (mixed $valor): ?string {
                if ($valor === null) {
                    return null;
                }

                if (! is_string($valor)) {
                    throw new InvalidArgumentException(
                        'A biografia do artista deve ser uma sequência de caracteres.',
                    );
                }

                if (
                    preg_match('//u', $valor) !== 1
                    || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $valor) === 1
                ) {
                    throw new InvalidArgumentException(
                        'A biografia do artista contém caracteres inválidos.',
                    );
                }

                $biografia = trim(
                    $valor,
                );

                if ($biografia === '') {
                    return null;
                }

                if (
                    mb_strlen(
                        $biografia,
                    ) > self::COMPRIMENTO_MAXIMO_BIOGRAFIA
                ) {
                    throw new InvalidArgumentException(
                        sprintf(
                            'A biografia do artista não pode exceder %d caracteres.',
                            self::COMPRIMENTO_MAXIMO_BIOGRAFIA,
                        ),
                    );
                }

                return $biografia;
            },
        );
    }

    /**
     * Normaliza e valida o endereço externo da imagem do artista.
     *
     * @return Attribute<string|null, string|null> Atributo da imagem.
     *
     * @throws InvalidArgumentException Quando o endereço é inválido, utiliza um
     *                                  esquema não permitido ou contém
     *                                  credenciais.
     *
     * @since 2.0.0
     */
    protected function imagem(): Attribute
    {
        return Attribute::make(
            set: static function (mixed $valor): ?string {
                if ($valor === null) {
                    return null;
                }

                if (! is_string($valor)) {
                    throw new InvalidArgumentException(
                        'O endereço da imagem deve ser uma sequência de caracteres.',
                    );
                }

                if (
                    preg_match('//u', $valor) !== 1
                    || preg_match('/[\x00-\x1F\x7F]/', $valor) === 1
                ) {
                    throw new InvalidArgumentException(
                        'O endereço da imagem contém caracteres inválidos.',
                    );
                }

                $url = trim(
                    $valor,
                );

                if ($url === '') {
                    return null;
                }

                if (
                    mb_strlen(
                        $url,
                    ) > self::COMPRIMENTO_MAXIMO_URL_IMAGEM
                    || filter_var(
                        $url,
                        FILTER_VALIDATE_URL,
                    ) === false
                ) {
                    throw new InvalidArgumentException(
                        'O endereço da imagem não é válido.',
                    );
                }

                $esquema = mb_strtolower(
                    (string) parse_url(
                        $url,
                        PHP_URL_SCHEME,
                    ),
                );

                if (
                    ! in_array(
                        $esquema,
                        [
                            'http',
                            'https',
                        ],
                        true,
                    )
                ) {
                    throw new InvalidArgumentException(
                        'O endereço da imagem deve utilizar HTTP ou HTTPS.',
                    );
                }

                if (
                    parse_url(
                        $url,
                        PHP_URL_USER,
                    ) !== null
                    || parse_url(
                        $url,
                        PHP_URL_PASS,
                    ) !== null
                ) {
                    throw new InvalidArgumentException(
                        'O endereço da imagem não pode incluir credenciais.',
                    );
                }

                return $url;
            },
        );
    }

    /**
     * Normaliza e valida o identificador MusicBrainz do artista.
     *
     * O identificador é persistido em minúsculas e deve respeitar o formato UUID
     * utilizado pelo MusicBrainz.
     *
     * @return Attribute<string|null, string|null> Atributo do identificador.
     *
     * @throws InvalidArgumentException Quando o identificador não é válido.
     *
     * @since 2.0.0
     */
    protected function musicbrainzId(): Attribute
    {
        return Attribute::make(
            set: static function (mixed $valor): ?string {
                if ($valor === null) {
                    return null;
                }

                if (! is_string($valor)) {
                    throw new InvalidArgumentException(
                        'O identificador MusicBrainz deve ser uma sequência de caracteres.',
                    );
                }

                $identificador =
                    mb_strtolower(
                        trim(
                            $valor,
                        ),
                    );

                if ($identificador === '') {
                    return null;
                }

                if (
                    preg_match(
                        '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
                        $identificador,
                    ) !== 1
                ) {
                    throw new InvalidArgumentException(
                        'O identificador MusicBrainz não é válido.',
                    );
                }

                return $identificador;
            },
        );
    }

    /**
     * Obtém o endereço externo da imagem do artista.
     *
     * @return Attribute<string|null, never> Endereço externo ou nulo.
     *
     * @since 2.0.0
     */
    protected function urlImagem(): Attribute
    {
        return Attribute::make(
            get: function (): ?string {
                $url = $this->getAttributeFromArray(
                    'imagem',
                );

                return is_string($url)
                    && trim($url) !== ''
                    ? $url
                    : null;
            },
        );
    }

    /**
     * Obtém o endereço público do perfil MusicBrainz associado ao artista.
     *
     * @return Attribute<string|null, never> Endereço público do MusicBrainz.
     *
     * @since 2.0.0
     */
    protected function urlMusicbrainz(): Attribute
    {
        return Attribute::make(
            get: function (): ?string {
                $identificador =
                    $this->getAttributeFromArray(
                        'musicbrainz_id',
                    );

                if (
                    ! is_string($identificador)
                    || preg_match(
                        '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
                        $identificador,
                    ) !== 1
                ) {
                    return null;
                }

                return 'https://musicbrainz.org/artist/'
                    .mb_strtolower(
                        $identificador,
                    );
            },
        );
    }

    /**
     * Obtém o endereço público do perfil Discogs associado.
     *
     * @return Attribute<string|null, never> Endereço público do Discogs.
     *
     * @since 2.0.0
     */
    protected function urlDiscogs(): Attribute
    {
        return Attribute::make(
            get: function (): ?string {
                $identificador =
                    $this->getAttributeFromArray(
                        'discogs_id',
                    );

                if (
                    ! is_numeric($identificador)
                    || (int) $identificador < 1
                ) {
                    return null;
                }

                return 'https://www.discogs.com/artist/'
                    .(int) $identificador;
            },
        );
    }

    /**
     * Obtém o rótulo contextual utilizado na seleção do artista.
     *
     * O nome pode ser complementado pela origem geográfica, pelo ano de início
     * conhecido e pelos géneros para distinguir artistas homónimos sem alterar
     * o respetivo nome canónico.
     *
     * @return string Rótulo contextual do artista.
     *
     * @throws LogicException Quando a relação de géneros contém dados
     *                        persistidos inválidos.
     *
     * @since 2.0.0
     */
    public function obterRotuloSelecao(): string
    {
        $origemGeografica =
            $this->origemGeografica;

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

        $rotulo =
            $this->nome;

        if ($origemGeografica instanceof OrigemGeografica) {
            $rotulo .=
                ' — '
                .$origemGeografica->nome;
        }

        $complementos = [];

        $anoInicio =
            $this->getAttributeFromArray(
                'ano_inicio_atividade',
            );

        if (
            is_numeric($anoInicio)
            && (int) $anoInicio > 0
        ) {
            $complementos[] =
                (string) (int) $anoInicio;
        }

        if ($nomesGeneros !== []) {
            $complementos[] =
                implode(
                    ', ',
                    $nomesGeneros,
                );
        }

        if ($complementos !== []) {
            $rotulo .=
                ' · '
                .implode(
                    ' · ',
                    $complementos,
                );
        }

        return $rotulo;
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
     * Os géneros são devolvidos por ordem alfabética e, em caso de empate,
     * pelo respetivo identificador.
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

    /**
     * Obtém as ligações externas do artista pela ordem definida.
     *
     * @return MorphMany<Ligacao, $this> Relação com as ligações.
     *
     * @since 2.0.0
     */
    public function ligacoes(): MorphMany
    {
        return $this
            ->morphMany(
                Ligacao::class,
                'ligavel',
                'tipo_ligavel',
                'ligavel_id',
            )
            ->orderBy(
                'ordem',
            )
            ->orderBy(
                'id',
            );
    }
}
