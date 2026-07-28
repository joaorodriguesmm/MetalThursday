<?php

declare(strict_types=1);

namespace App\Models\Interacoes;

use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\MetalThursday;
use App\Models\MetalThursday\SeccaoMetalThursday;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use LogicException;

/**
 * Representa um comentário publicado numa entidade comentável.
 *
 * Os comentários podem pertencer diretamente a diferentes entidades através
 * de uma relação polimórfica. Também podem responder a outro comentário,
 * formando uma estrutura hierárquica.
 *
 * @property int $id
 * @property int|null $utilizador_id
 * @property string $conteudo
 * @property string $tipo_comentavel
 * @property int $comentavel_id
 * @property int|null $comentario_pai_id
 * @property int $quantidade_gostos
 * @property bool $gostado_pelo_utilizador_autenticado
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property CarbonInterface|null $deleted_at
 * @property-read Utilizador|null $utilizador
 * @property-read Model|null $comentavel
 * @property-read Comentario|null $comentarioPai
 * @property-read Collection<int, Comentario> $respostas
 * @property-read Collection<int, Gosto> $gostos
 *
 * @since 1.0.0
 *
 * @version 3.0.0
 */
class Comentario extends Model
{
    use SoftDeletes;

    /**
     * Nome físico da tabela associada ao modelo.
     *
     * @var string
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    protected $table = 'comentarios';

    /**
     * Atributos permitidos em operações de atribuição em massa.
     *
     * O utilizador e a entidade comentada devem ser associados explicitamente
     * através das relações, evitando aceitar identificadores provenientes
     * diretamente do pedido HTTP.
     *
     * @var array<int, string>
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    protected $fillable = [
        'conteudo',
        'comentario_pai_id',
    ];

    /**
     * Define as conversões automáticas dos atributos.
     *
     * Os atributos `quantidade_gostos` e
     * `gostado_pelo_utilizador_autenticado` são adicionados pelas consultas
     * de apresentação e não correspondem a colunas físicas da tabela.
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
            'utilizador_id' =>
            'integer',

            'comentavel_id' =>
            'integer',

            'comentario_pai_id' =>
            'integer',

            'quantidade_gostos' =>
            'integer',

            'gostado_pelo_utilizador_autenticado' =>
            'boolean',
        ];
    }

    /**
     * Obtém o utilizador responsável pelo comentário.
     *
     * A relação pode ser nula quando a conta do utilizador tiver sido
     * eliminada, uma vez que o histórico do comentário é preservado.
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

    /**
     * Obtém a entidade à qual o comentário pertence.
     *
     * Os nomes das colunas são indicados explicitamente porque utilizam
     * nomenclatura portuguesa.
     *
     * @return MorphTo<Model, $this> Relação com a entidade comentada.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
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
     * Obtém os gostos associados ao comentário.
     *
     * @return HasMany<Gosto, $this> Relação com os gostos.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function gostos(): HasMany
    {
        return $this->hasMany(
            Gosto::class,
            'comentario_id',
        );
    }

    /**
     * Obtém o comentário ao qual este comentário responde.
     *
     * O comentário pai continua acessível mesmo quando tiver sido eliminado
     * logicamente, preservando a estrutura da conversa.
     *
     * @return BelongsTo<Comentario, $this> Relação com o comentário pai.
     *
     * @since 1.0.0
     *
     * @version 2.1.0
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
     * As respostas eliminadas logicamente não são incluídas.
     *
     * @return HasMany<Comentario, $this> Relação com as respostas.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function respostas(): HasMany
    {
        return $this
            ->hasMany(
                self::class,
                'comentario_pai_id',
            )
            ->orderBy(
                'created_at',
            )
            ->orderBy(
                'id',
            );
    }

    /**
     * Adiciona os dados necessários à apresentação de comentários.
     *
     * A consulta carrega o autor, calcula a quantidade total de gostos e,
     * quando existe um utilizador autenticado, determina se esse utilizador
     * atribuiu gosto ao comentário.
     *
     * Este scope prepara apenas o nível atual da consulta. As respostas
     * descendentes devem receber o mesmo scope através do serviço ou
     * controlador responsável por carregar a árvore.
     *
     * @param  Builder<Comentario>  $consulta  Consulta dos comentários.
     * @param  int|null  $identificadorUtilizador  Utilizador autenticado.
     * @return Builder<Comentario> Consulta preparada.
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    public function scopeComDadosApresentacao(
        Builder $consulta,
        ?int $identificadorUtilizador = null,
    ): Builder {
        $consulta
            ->with(
                'utilizador',
            )
            ->withCount([
                'gostos as quantidade_gostos',
            ]);

        if (
            $identificadorUtilizador === null
            || $identificadorUtilizador < 1
        ) {
            return $consulta;
        }

        return $consulta->withCount([
            'gostos as gostado_pelo_utilizador_autenticado' =>
            static function (
                Builder $consultaGostos,
            ) use (
                $identificadorUtilizador,
            ): void {
                $consultaGostos->where(
                    'utilizador_id',
                    $identificadorUtilizador,
                );
            },
        ]);
    }

    /**
     * Limita a consulta aos comentários principais.
     *
     * @param  Builder<Comentario>  $consulta  Consulta dos comentários.
     * @return Builder<Comentario> Consulta limitada.
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    public function scopePrincipais(
        Builder $consulta,
    ): Builder {
        return $consulta->whereNull(
            'comentario_pai_id',
        );
    }

    /**
     * Ordena os comentários cronologicamente.
     *
     * O identificador é utilizado como segundo critério para garantir uma
     * ordenação determinística quando dois comentários possuem o mesmo
     * momento de criação.
     *
     * @param  Builder<Comentario>  $consulta  Consulta dos comentários.
     * @return Builder<Comentario> Consulta ordenada.
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    public function scopeOrdenadosCronologicamente(
        Builder $consulta,
    ): Builder {
        return $consulta
            ->orderBy(
                'created_at',
            )
            ->orderBy(
                'id',
            );
    }

    /**
     * Obtém a MetalThursday associada ao comentário.
     *
     * Um comentário pode pertencer diretamente a uma MetalThursday ou a uma
     * secção pertencente a uma MetalThursday.
     *
     * As relações necessárias devem ser previamente carregadas para impedir
     * consultas implícitas durante a utilização deste auxiliar.
     *
     * @return MetalThursday|null MetalThursday associada ou nula.
     *
     * @throws LogicException Quando uma relação necessária não está carregada
     *                        ou possui um tipo inesperado.
     *
     * @since 1.0.0
     *
     * @version 3.0.0
     */
    public function metalThursdayAssociado(): ?MetalThursday
    {
        if (! $this->relationLoaded('comentavel')) {
            throw new LogicException(
                'A relação "comentavel" deve estar carregada antes de obter a MetalThursday associada.',
            );
        }

        $comentavel = $this->getRelation(
            'comentavel',
        );

        if ($comentavel instanceof MetalThursday) {
            return $comentavel;
        }

        if (! $comentavel instanceof SeccaoMetalThursday) {
            return null;
        }

        if (! $comentavel->relationLoaded('metalThursday')) {
            throw new LogicException(
                'A relação "metalThursday" da secção deve estar carregada.',
            );
        }

        $metalThursday = $comentavel->getRelation(
            'metalThursday',
        );

        if (
            $metalThursday !== null
            && ! $metalThursday instanceof MetalThursday
        ) {
            throw new LogicException(
                'A relação "metalThursday" da secção possui um tipo inesperado.',
            );
        }

        return $metalThursday;
    }
}
