<?php

declare(strict_types=1);

namespace App\Models\Interacoes;

use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\MetalThursday;
use App\Models\MetalThursday\SeccaoMetalThursday;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

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
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property CarbonInterface|null $deleted_at
 * @property-read Utilizador|null $utilizador
 * @property-read Model|null $comentavel
 * @property-read Comentario|null $comentarioPai
 * @property-read Collection<int, Comentario> $respostas
 * @property-read Collection<int, Gosto> $gostos
 * @property-read int $quantidade_gostos
 * @property-read bool $gostado_pelo_utilizador_autenticado
 *
 * @since 1.0.0
 *
 * @version 2.1.0
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
     * Relações carregadas automaticamente.
     *
     * O utilizador é normalmente necessário na apresentação dos comentários,
     * pelo que é carregado juntamente com cada comentário.
     *
     * @var array<int, string>
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    protected $with = [
        'utilizador',
    ];

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
            'comentavel_id' => 'integer',
            'comentario_pai_id' => 'integer',
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
            ->orderBy('created_at')
            ->orderBy('id');
    }

    /**
     * Obtém a quantidade de gostos do comentário.
     *
     * Quando a contagem ou a relação já está carregada, o resultado é obtido
     * em memória. Caso contrário, é executada uma consulta de contagem.
     *
     * @return Attribute<int, never> Quantidade de gostos.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    protected function quantidadeGostos(): Attribute
    {
        return Attribute::get(
            function (): int {
                if (
                    array_key_exists(
                        'gostos_count',
                        $this->attributes,
                    )
                ) {
                    return (int) $this->attributes['gostos_count'];
                }

                if ($this->relationLoaded('gostos')) {
                    return $this
                        ->getRelation('gostos')
                        ->count();
                }

                return $this
                    ->gostos()
                    ->count();
            },
        );
    }

    /**
     * Determina se o utilizador autenticado gosta do comentário.
     *
     * Quando a relação dos gostos já estiver carregada, a verificação é
     * efetuada em memória. Caso contrário, é executada uma consulta.
     *
     * @return Attribute<bool, never> Estado do gosto do utilizador
     *                                autenticado.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    protected function gostadoPeloUtilizadorAutenticado(): Attribute
    {
        return Attribute::get(
            function (): bool {
                $utilizadorId = Auth::id();

                if ($utilizadorId === null) {
                    return false;
                }

                if ($this->relationLoaded('gostos')) {
                    return $this
                        ->getRelation('gostos')
                        ->contains(
                            'utilizador_id',
                            (int) $utilizadorId,
                        );
                }

                return $this
                    ->gostos()
                    ->where(
                        'utilizador_id',
                        $utilizadorId,
                    )
                    ->exists();
            },
        );
    }

    /**
     * Obtém a MetalThursday associada ao comentário.
     *
     * Um comentário pode pertencer diretamente a uma MetalThursday ou a uma
     * secção pertencente a uma MetalThursday.
     *
     * Este método é um auxiliar de domínio e não uma relação Eloquent.
     *
     * @return MetalThursday|null MetalThursday associada ou nula.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function metalThursdayAssociado(): ?MetalThursday
    {
        $comentavel = $this->comentavel;

        if ($comentavel instanceof MetalThursday) {
            return $comentavel;
        }

        if ($comentavel instanceof SeccaoMetalThursday) {
            return $comentavel->metalThursday;
        }

        return null;
    }
}
