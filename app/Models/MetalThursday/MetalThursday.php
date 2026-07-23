<?php

declare(strict_types=1);

namespace App\Models\MetalThursday;

use App\Models\Autenticacao\Utilizador;
use App\Models\Interacoes\Audicao;
use App\Models\Interacoes\Avaliacao;
use App\Models\Interacoes\Comentario;
use App\Traits\Auditoria\RegistaAutoria;
use App\Traits\Interacoes\TemAudicoes;
use App\Traits\Interacoes\TemAvaliacoes;
use App\Traits\Interacoes\TemComentarios;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Database\Factories\MetalThursday\MetalThursdayFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Representa uma MetalThursday.
 *
 * Cada MetalThursday pertence a uma edição, pode possuir um autor, um próximo
 * nomeado e várias secções. Também suporta comentários, avaliações e registos
 * de audição através de relações polimórficas.
 *
 * @property int $id
 * @property string|null $nome
 * @property CarbonImmutable $data
 * @property int $edicao_id
 * @property int|null $autor_id
 * @property int|null $proximo_nomeado_id
 * @property int|null $criado_por_id
 * @property int|null $atualizado_por_id
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property CarbonInterface|null $deleted_at
 * @property-read Edicao $edicao
 * @property-read Utilizador|null $autor
 * @property-read Utilizador|null $proximoNomeado
 * @property-read Collection<int, SeccaoMetalThursday> $seccoes
 * @property-read Collection<int, Comentario> $comentarios
 * @property-read Collection<int, Avaliacao> $avaliacoes
 * @property-read Collection<int, Audicao> $audicoes
 * @property-read Avaliacao|null $avaliacaoUtilizadorAutenticado
 * @property-read Audicao|null $audicaoUtilizadorAutenticado
 * @property-read int|null $numero_semana_na_edicao
 * @property-read float $pontuacao_utilizador_autenticado
 * @property-read bool $ouvido_pelo_utilizador_autenticado
 *
 * @since 1.0.0
 *
 * @version 2.1.0
 */
class MetalThursday extends Model
{
    /** @use HasFactory<MetalThursdayFactory> */
    use HasFactory;

    use RegistaAutoria;
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
    protected $table = 'metal_thursdays';

    /**
     * Atributos permitidos em operações de atribuição em massa.
     *
     * Os identificadores de auditoria são preenchidos automaticamente pelo
     * trait {@see RegistaAutoria}.
     *
     * @var array<int, string>
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    protected $fillable = [
        'nome',
        'data',
        'edicao_id',
        'autor_id',
        'proximo_nomeado_id',
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
            'data' => 'immutable_date',
            'edicao_id' => 'integer',
            'autor_id' => 'integer',
            'proximo_nomeado_id' => 'integer',
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
     * @return MetalThursdayFactory Factory das MetalThursdays.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    protected static function newFactory(): MetalThursdayFactory
    {
        return MetalThursdayFactory::new();
    }

    /**
     * Normaliza o nome da MetalThursday antes da persistência.
     *
     * Um nome vazio é convertido em nulo porque a coluna é opcional.
     *
     * @return Attribute<string|null, string|null> Atributo do nome.
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
            ): ?string {
                if (! is_string($valor)) {
                    return null;
                }

                $nomeNormalizado = trim(
                    $valor,
                );

                return $nomeNormalizado !== ''
                    ? $nomeNormalizado
                    : null;
            },
        );
    }

    /**
     * Obtém a edição à qual pertence a MetalThursday.
     *
     * @return BelongsTo<Edicao, $this> Relação com a edição.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function edicao(): BelongsTo
    {
        return $this->belongsTo(
            Edicao::class,
            'edicao_id',
        );
    }

    /**
     * Obtém o autor da MetalThursday.
     *
     * @return BelongsTo<Utilizador, $this> Relação com o autor.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function autor(): BelongsTo
    {
        return $this->belongsTo(
            Utilizador::class,
            'autor_id',
        );
    }

    /**
     * Obtém o próximo utilizador nomeado.
     *
     * @return BelongsTo<Utilizador, $this> Relação com o próximo nomeado.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function proximoNomeado(): BelongsTo
    {
        return $this->belongsTo(
            Utilizador::class,
            'proximo_nomeado_id',
        );
    }

    /**
     * Obtém as secções da MetalThursday pela ordem definida.
     *
     * O identificador é utilizado como segundo critério para garantir uma
     * ordenação determinística caso existam temporariamente posições
     * repetidas.
     *
     * @return HasMany<SeccaoMetalThursday, $this> Relação com as secções.
     *
     * @since 1.0.0
     *
     * @version 2.1.0
     */
    public function seccoes(): HasMany
    {
        return $this
            ->hasMany(
                SeccaoMetalThursday::class,
                'metal_thursday_id',
            )
            ->orderBy('ordem')
            ->orderBy('id');
    }

    /**
     * Obtém o número sequencial da MetalThursday dentro da edição.
     *
     * A posição é determinada pela data, considerando apenas MetalThursdays
     * não eliminadas logicamente.
     *
     * @return Attribute<int|null, never> Número da semana na edição.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    protected function numeroSemanaNaEdicao(): Attribute
    {
        return Attribute::get(
            function (): ?int {
                if (
                    ! $this->exists
                    || $this->edicao_id === null
                    || $this->data === null
                ) {
                    return null;
                }

                $numeroSemana = self::query()
                    ->where(
                        'edicao_id',
                        $this->edicao_id,
                    )
                    ->whereDate(
                        'data',
                        '<=',
                        $this->data,
                    )
                    ->count();

                return $numeroSemana > 0
                    ? $numeroSemana
                    : null;
            },
        );
    }
}
