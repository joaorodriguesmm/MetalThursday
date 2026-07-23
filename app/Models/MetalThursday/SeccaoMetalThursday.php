<?php

declare(strict_types=1);

namespace App\Models\MetalThursday;

use App\Models\Interacoes\Audicao;
use App\Models\Interacoes\Avaliacao;
use App\Models\Interacoes\Comentario;
use App\Models\Musica\Banda;
use App\Traits\Blameable;
use App\Traits\Interacoes\TemAudicoes;
use App\Traits\Interacoes\TemAvaliacoes;
use App\Traits\Interacoes\TemComentarios;
use Carbon\CarbonInterface;
use Database\Factories\MetalThursday\SeccaoMetalThursdayFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Representa uma secção pertencente a uma MetalThursday.
 *
 * Cada secção possui um tipo e pode incluir uma banda, um título, uma
 * descrição, uma ligação externa, um tipo de incorporação e um ano.
 *
 * As secções também suportam comentários, avaliações e registos de audição
 * através de relações polimórficas.
 *
 * @property int $id
 * @property int $metal_thursday_id
 * @property int $tipo_seccao_id
 * @property int $ordem
 * @property string|null $titulo
 * @property string|null $descricao
 * @property int|null $banda_id
 * @property string|null $ligacao
 * @property string|null $tipo_incorporacao
 * @property int|null $ano
 * @property int|null $criado_por_id
 * @property int|null $atualizado_por_id
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property CarbonInterface|null $deleted_at
 * @property-read MetalThursday $metalThursday
 * @property-read TipoSeccao $tipoSeccao
 * @property-read Banda|null $banda
 * @property-read Collection<int, Comentario> $comentarios
 * @property-read Collection<int, Avaliacao> $avaliacoes
 * @property-read Collection<int, Audicao> $audicoes
 * @property-read Avaliacao|null $avaliacaoUtilizadorAutenticado
 * @property-read Audicao|null $audicaoUtilizadorAutenticado
 * @property-read float $pontuacao_utilizador_autenticado
 * @property-read bool $ouvido_pelo_utilizador_autenticado
 *
 * @since 1.0.0
 *
 * @version 2.2.0
 */
class SeccaoMetalThursday extends Model
{
    use Blameable;

    /** @use HasFactory<SeccaoMetalThursdayFactory> */
    use HasFactory;

    use SoftDeletes;
    use TemAudicoes;
    use TemAvaliacoes;
    use TemComentarios;

    /**
     * Nome físico da tabela associada ao modelo.
     *
     * @var string
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    protected $table = 'seccoes_metal_thursday';

    /**
     * Atributos permitidos em operações de atribuição em massa.
     *
     * A MetalThursday, o tipo de secção e a banda devem ser associados
     * explicitamente através das respetivas relações.
     *
     * Os identificadores de auditoria são preenchidos automaticamente pelo
     * trait {@see Blameable}.
     *
     * @var array<int, string>
     *
     * @since 1.0.0
     *
     * @version 2.1.0
     */
    protected $fillable = [
        'ordem',
        'titulo',
        'descricao',
        'ligacao',
        'tipo_incorporacao',
        'ano',
    ];

    /**
     * Define as conversões automáticas dos atributos.
     *
     * @return array<string, string> Conversões dos atributos.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    protected function casts(): array
    {
        return [
            'metal_thursday_id' => 'integer',
            'tipo_seccao_id' => 'integer',
            'ordem' => 'integer',
            'banda_id' => 'integer',
            'ano' => 'integer',
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
     * @return SeccaoMetalThursdayFactory Factory das secções.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    protected static function newFactory(): SeccaoMetalThursdayFactory
    {
        return SeccaoMetalThursdayFactory::new();
    }

    /**
     * Limita a consulta às secções ordenadas pela posição definida.
     *
     * O identificador é utilizado como segundo critério para garantir uma
     * ordenação determinística quando existirem temporariamente posições
     * repetidas.
     *
     * @param  Builder<SeccaoMetalThursday>  $consulta  Consulta das secções.
     * @return Builder<SeccaoMetalThursday> Consulta ordenada.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function scopeOrdenadas(
        Builder $consulta,
    ): Builder {
        return $consulta
            ->orderBy('ordem')
            ->orderBy('id');
    }

    /**
     * Obtém a MetalThursday à qual pertence a secção.
     *
     * A MetalThursday continua acessível quando tiver sido eliminada
     * logicamente, preservando o contexto histórico da secção.
     *
     * @return BelongsTo<MetalThursday, $this> Relação com a MetalThursday.
     *
     * @since 1.0.0
     *
     * @version 2.1.0
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
     * Obtém o tipo da secção.
     *
     * @return BelongsTo<TipoSeccao, $this> Relação com o tipo da secção.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function tipoSeccao(): BelongsTo
    {
        return $this->belongsTo(
            TipoSeccao::class,
            'tipo_seccao_id',
        );
    }

    /**
     * Obtém a banda associada à secção.
     *
     * A banda continua acessível quando tiver sido eliminada logicamente,
     * preservando o conteúdo histórico da secção.
     *
     * @return BelongsTo<Banda, $this> Relação com a banda.
     *
     * @since 1.0.0
     *
     * @version 2.1.0
     */
    public function banda(): BelongsTo
    {
        return $this
            ->belongsTo(
                Banda::class,
                'banda_id',
            )
            ->withTrashed();
    }
}
