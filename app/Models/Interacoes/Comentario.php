<?php

declare(strict_types=1);

namespace App\Models\Interacoes;

use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\MetalThursday;
use App\Models\MetalThursday\SeccaoMetalThursday;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use InvalidArgumentException;

/**
 * Representa um comentário publicado numa entidade da aplicação.
 *
 * Um comentário pode pertencer a uma MetalThursday ou a uma das respetivas
 * secções. Pode também responder a outro comentário da mesma entidade.
 *
 * A hierarquia persistida preserva o comentário concretamente respondido.
 * A profundidade visual é uma responsabilidade exclusiva da interface.
 *
 * @property int $id
 * @property int|null $utilizador_id
 * @property string $conteudo
 * @property 'metal_thursday'|'seccao_metal_thursday' $tipo_comentavel
 * @property int $comentavel_id
 * @property int|null $comentario_pai_id
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property CarbonInterface|null $deleted_at
 * @property int $quantidade_gostos
 * @property bool $gostado_pelo_utilizador_autenticado
 * @property-read Utilizador|null $utilizador
 * @property-read MetalThursday|SeccaoMetalThursday|null $comentavel
 * @property-read Comentario|null $comentarioPai
 * @property-read Collection<int, Comentario> $respostas
 * @property-read Collection<int, Gosto> $gostos
 * @property int $quantidade_respostas
 * @property CarbonInterface|null $conteudo_eliminado_em
 * @property CarbonInterface|null $editado_em
 *
 * @since 1.0.0
 */
class Comentario extends Model
{
    use SoftDeletes;

    /**
     * Comprimento mínimo permitido para o conteúdo.
     *
     * @since 2.0.0
     */
    public const COMPRIMENTO_MINIMO_CONTEUDO = 1;

    /**
     * Comprimento máximo permitido para o conteúdo.
     *
     * Este valor coincide com a restrição definida na base de dados.
     *
     * @since 2.0.0
     */
    public const COMPRIMENTO_MAXIMO_CONTEUDO = 2000;

    /**
     * Nome físico da tabela associada ao modelo.
     *
     * @var string
     *
     * @since 1.0.0
     */
    protected $table = 'comentarios';

    /**
     * Atributos permitidos em operações de atribuição em massa.
     *
     * O tipo e o identificador da entidade comentada são preenchidos pela
     * relação polimórfica e não podem ser atribuídos diretamente através de
     * dados externos.
     *
     * @var list<string>
     *
     * @since 1.0.0
     */
    protected $fillable = [
        'utilizador_id',
        'conteudo',
        'comentario_pai_id',
    ];

    /**
     * Define as conversões automáticas dos atributos.
     *
     * Os dois últimos atributos são calculados pelas consultas de
     * apresentação e não correspondem a colunas físicas da tabela.
     *
     * @return array<string, string> Conversões dos atributos.
     *
     * @since 2.0.0
     */
    protected function casts(): array
    {
        return [
            'utilizador_id' => 'integer',

            'comentavel_id' => 'integer',

            'comentario_pai_id' => 'integer',

            'quantidade_gostos' => 'integer',

            'gostado_pelo_utilizador_autenticado' => 'boolean',

            'quantidade_respostas' => 'integer',

            'conteudo_eliminado_em' => 'datetime',

            'editado_em' => 'datetime',
        ];
    }

    /**
     * Normaliza e valida o conteúdo do comentário.
     *
     * As quebras de linha são preservadas num formato uniforme. Espaços
     * ASCII, tabulações e quebras de linha exteriores são removidos, mas o
     * conteúdo interior não é comprimido para permitir parágrafos e
     * formatação textual simples.
     *
     * @return Attribute<string, string> Atributo do conteúdo.
     *
     * @throws InvalidArgumentException Quando o conteúdo não é válido.
     *
     * @since 2.0.0
     */
    protected function conteudo(): Attribute
    {
        return Attribute::make(
            set: static function (
                mixed $valor,
            ): string {
                if (! is_string($valor)) {
                    throw new InvalidArgumentException(
                        'O conteúdo do comentário deve ser uma sequência de caracteres.',
                    );
                }

                if (
                    preg_match(
                        '//u',
                        $valor,
                    ) !== 1
                ) {
                    throw new InvalidArgumentException(
                        'O conteúdo do comentário contém texto inválido.',
                    );
                }

                if (
                    preg_match(
                        '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/',
                        $valor,
                    ) === 1
                ) {
                    throw new InvalidArgumentException(
                        'O conteúdo do comentário contém caracteres inválidos.',
                    );
                }

                $conteudoNormalizado = str_replace(
                    [
                        "\r\n",
                        "\r",
                    ],
                    "\n",
                    $valor,
                );

                $conteudoNormalizado = trim(
                    $conteudoNormalizado,
                    " \t\n",
                );

                $comprimento = mb_strlen(
                    $conteudoNormalizado,
                );

                if (
                    $comprimento < self::COMPRIMENTO_MINIMO_CONTEUDO
                ) {
                    throw new InvalidArgumentException(
                        'O conteúdo do comentário é obrigatório.',
                    );
                }

                if (
                    $comprimento
                    > self::COMPRIMENTO_MAXIMO_CONTEUDO
                ) {
                    throw new InvalidArgumentException(
                        sprintf(
                            'O comentário não pode ter mais de %d caracteres.',
                            self::COMPRIMENTO_MAXIMO_CONTEUDO,
                        ),
                    );
                }

                return $conteudoNormalizado;
            },
        );
    }

    /**
     * Obtém o utilizador que publicou o comentário.
     *
     * A relação pode ser nula quando o utilizador foi posteriormente
     * eliminado.
     *
     * @return BelongsTo<Utilizador, $this> Relação com o utilizador.
     *
     * @since 1.0.0
     */
    public function utilizador(): BelongsTo
    {
        return $this->belongsTo(
            Utilizador::class,
            'utilizador_id',
        );
    }

    /**
     * Obtém a entidade associada ao comentário.
     *
     * Os aliases polimórficos permitidos são:
     *
     * - `metal_thursday`;
     * - `seccao_metal_thursday`.
     *
     * A relação pode devolver nulo quando a entidade foi eliminada
     * logicamente e não foi incluída explicitamente na consulta.
     *
     * @return MorphTo<Model, $this> Relação com a entidade comentada.
     *
     * @since 1.0.0
     */
    public function comentavel(): MorphTo
    {
        return $this->morphTo(
            name: 'comentavel',
            type: 'tipo_comentavel',
            id: 'comentavel_id',
        );
    }

    /**
     * Obtém o comentário ao qual este comentário responde.
     *
     * O comentário pai pode já ter sido eliminado logicamente, pelo que a
     * relação inclui registos eliminados.
     *
     * @return BelongsTo<Comentario, $this> Relação com o comentário pai.
     *
     * @since 1.0.0
     */
    public function comentarioPai(): BelongsTo
    {
        return $this
            ->belongsTo(
                self::class,
                'comentario_pai_id',
            )
            ->withTrashed();
    }

    /**
     * Obtém as respostas diretas ao comentário.
     *
     * @return HasMany<Comentario, $this> Relação com as respostas.
     *
     * @since 1.0.0
     */
    public function respostas(): HasMany
    {
        return $this->hasMany(
            self::class,
            'comentario_pai_id',
        );
    }

    /**
     * Obtém os gostos atribuídos ao comentário.
     *
     * @return HasMany<Gosto, $this> Relação com os gostos.
     *
     * @since 1.0.0
     */
    public function gostos(): HasMany
    {
        return $this->hasMany(
            Gosto::class,
            'comentario_id',
        );
    }

    /**
     * Limita a consulta aos comentários principais.
     *
     * Um comentário principal não responde a qualquer outro comentário.
     *
     * @param  Builder<Comentario>  $construtor  Consulta dos comentários.
     * @return Builder<Comentario> Consulta filtrada.
     *
     * @since 2.0.0
     */
    public function scopePrincipais(
        Builder $construtor,
    ): Builder {
        return $construtor->whereNull(
            'comentario_pai_id',
        );
    }

    /**
     * Ordena os comentários cronologicamente.
     *
     * O identificador é utilizado como segundo critério para manter a ordem
     * estável quando vários comentários possuem a mesma data de criação.
     *
     * @param  Builder<Comentario>  $construtor  Consulta dos comentários.
     * @return Builder<Comentario> Consulta ordenada.
     *
     * @since 2.0.0
     */
    public function scopeOrdenadosCronologicamente(
        Builder $construtor,
    ): Builder {
        return $construtor
            ->orderBy(
                'created_at',
            )
            ->orderBy(
                'id',
            );
    }

    /**
     * Ordena os comentários dos mais recentes para os mais antigos.
     *
     * O identificador é utilizado como segundo critério para manter uma ordem
     * determinística quando vários comentários possuem a mesma data de criação.
     *
     * @param  Builder<Comentario>  $construtor  Consulta dos comentários.
     * @return Builder<Comentario> Consulta ordenada.
     *
     * @since 2.0.0
     */
    public function scopeOrdenadosMaisRecentes(
        Builder $construtor,
    ): Builder {
        return $construtor
            ->orderByDesc(
                'created_at',
            )
            ->orderByDesc(
                'id',
            );
    }

    /**
     * Carrega os dados necessários para apresentar os comentários.
     *
     * A consulta inclui o utilizador, as quantidades de gostos e respostas
     * diretas e, quando existe autenticação, a indicação de que o utilizador
     * atual atribuiu gosto ao comentário.
     *
     * As respostas propriamente ditas não são carregadas. A respetiva consulta é
     * efetuada apenas quando o utilizador expande um ramo da conversa.
     *
     * @param  Builder<Comentario>  $construtor  Consulta dos comentários.
     * @param  int|null  $identificadorUtilizador  Utilizador autenticado.
     * @return Builder<Comentario> Consulta preparada.
     *
     * @throws InvalidArgumentException Quando o identificador não é positivo.
     *
     * @since 2.0.0
     */
    public function scopeComDadosApresentacao(
        Builder $construtor,
        ?int $identificadorUtilizador,
    ): Builder {
        $construtor
            ->with([
                'utilizador:id,nome,fotografia',
            ])
            ->withCount([
                'gostos as quantidade_gostos',
                'respostas as quantidade_respostas',
            ]);

        if ($identificadorUtilizador === null) {
            return $construtor;
        }

        if ($identificadorUtilizador < 1) {
            throw new InvalidArgumentException(
                'O identificador do utilizador deve ser positivo.',
            );
        }

        return $construtor->withExists([
            'gostos as gostado_pelo_utilizador_autenticado' => static fn (
                Builder $construtor,
            ): Builder => $construtor->where(
                'utilizador_id',
                $identificadorUtilizador,
            ),
        ]);
    }

    /**
     * Indica se o conteúdo do comentário foi eliminado.
     *
     * O comentário pode continuar ativo na árvore quando possui respostas,
     * preservando a estrutura da conversa sem voltar a expor o conteúdo
     * eliminado.
     *
     * @return bool Verdadeiro quando o conteúdo foi eliminado.
     *
     * @since 2.0.0
     */
    public function temConteudoEliminado(): bool
    {
        return $this->conteudo_eliminado_em !== null;
    }
}
