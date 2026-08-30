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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;

/**
 * Representa uma MetalThursday.
 *
 * Cada MetalThursday pertence a uma edição, pode possuir um autor e um
 * próximo utilizador nomeado, contém várias secções e suporta comentários,
 * avaliações e registos de audição através de relações polimórficas.
 *
 * A data é única em toda a aplicação, incluindo entre registos eliminados
 * logicamente, conforme garantido pela base de dados.
 *
 * @property int $id
 * @property string|null $nome
 * @property CarbonImmutable $data
 * @property CarbonImmutable|null $publicacao_notificada_em
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
     * Comprimento máximo permitido para o nome.
     *
     * @since 2.0.0
     */
    public const COMPRIMENTO_MAXIMO_NOME = 255;

    /**
     * Alias do número sequencial da MetalThursday dentro da edição.
     *
     * @since 2.0.0
     */
    public const COLUNA_NUMERO_SEMANA_NA_EDICAO =
        'numero_semana_na_edicao';

    /**
     * Coluna que regista quando a publicação foi notificada.
     *
     * Um valor nulo indica que a publicação ainda necessita de processamento.
     *
     * @since 2.0.0
     */
    public const COLUNA_PUBLICACAO_NOTIFICADA_EM =
        'publicacao_notificada_em';

    /**
     * Alias utilizado pela subconsulta que calcula a posição na edição.
     *
     * @since 2.0.0
     */
    private const ALIAS_METAL_THURSDAYS_ANTERIORES =
        'metal_thursdays_anteriores';

    /**
     * Nome físico da tabela associada ao modelo.
     *
     * @var string
     *
     * @since 1.0.0
     */
    protected $table = 'metal_thursdays';

    /**
     * Atributos permitidos em operações de atribuição em massa.
     *
     * Os identificadores relacionais são recebidos exclusivamente através de
     * dados validados e aplicados pelo serviço responsável pela persistência.
     *
     * Os identificadores de auditoria são preenchidos automaticamente pelo
     * trait {@see RegistaAutoria}.
     *
     * @var list<string>
     *
     * @since 1.0.0
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
     */
    protected function casts(): array
    {
        return [
            'data' => 'immutable_date',

            self::COLUNA_PUBLICACAO_NOTIFICADA_EM => 'immutable_datetime',

            self::COLUNA_NUMERO_SEMANA_NA_EDICAO => 'integer',

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
     * @return MetalThursdayFactory Factory das MetalThursdays.
     *
     * @since 2.0.0
     */
    protected static function newFactory(): MetalThursdayFactory
    {
        return MetalThursdayFactory::new();
    }

    /**
     * Normaliza e valida o nome opcional da MetalThursday.
     *
     * Um valor nulo ou vazio representa a ausência de um nome específico.
     * Espaços exteriores e consecutivos são normalizados. Caracteres de
     * controlo, incluindo tabulações e quebras de linha, não são aceites.
     *
     * @return Attribute<string|null, string|null> Atributo do nome.
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
            ): ?string {
                if ($valor === null) {
                    return null;
                }

                if (! is_string($valor)) {
                    throw new InvalidArgumentException(
                        'O nome da MetalThursday deve ser uma sequência de caracteres.',
                    );
                }

                if (
                    preg_match(
                        '//u',
                        $valor,
                    ) !== 1
                ) {
                    throw new InvalidArgumentException(
                        'O nome da MetalThursday contém texto inválido.',
                    );
                }

                if (
                    preg_match(
                        '/[\x00-\x1F\x7F]/',
                        $valor,
                    ) === 1
                ) {
                    throw new InvalidArgumentException(
                        'O nome da MetalThursday contém caracteres inválidos.',
                    );
                }

                $nomeNormalizado = preg_replace(
                    '/\s+/u',
                    ' ',
                    $valor,
                );

                if (! is_string($nomeNormalizado)) {
                    throw new InvalidArgumentException(
                        'Não foi possível normalizar o nome da MetalThursday.',
                    );
                }

                $nomeNormalizado = trim(
                    $nomeNormalizado,
                );

                if ($nomeNormalizado === '') {
                    return null;
                }

                if (
                    mb_strlen(
                        $nomeNormalizado,
                    ) > self::COMPRIMENTO_MAXIMO_NOME
                ) {
                    throw new InvalidArgumentException(
                        sprintf(
                            'O nome da MetalThursday não pode ter mais de %d caracteres.',
                            self::COMPRIMENTO_MAXIMO_NOME,
                        ),
                    );
                }

                return $nomeNormalizado;
            },
        );
    }

    /**
     * Obtém a edição à qual pertence a MetalThursday.
     *
     * A edição continua acessível quando foi eliminada logicamente,
     * preservando o contexto histórico da MetalThursday.
     *
     * @return BelongsTo<Edicao, $this> Relação com a edição.
     *
     * @since 1.0.0
     */
    public function edicao(): BelongsTo
    {
        return $this
            ->belongsTo(
                Edicao::class,
                'edicao_id',
            )
            ->withTrashed();
    }

    /**
     * Obtém o autor da MetalThursday.
     *
     * A relação pode ser nula quando não foi definido um autor ou quando o
     * utilizador foi posteriormente eliminado.
     *
     * @return BelongsTo<Utilizador, $this> Relação com o autor.
     *
     * @since 1.0.0
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
     * A relação pode ser nula quando ainda não existe uma nomeação ou quando
     * o utilizador nomeado foi posteriormente eliminado.
     *
     * @return BelongsTo<Utilizador, $this> Relação com o próximo nomeado.
     *
     * @since 1.0.0
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
     * ordenação determinística.
     *
     * As secções eliminadas logicamente não são incluídas.
     *
     * @return HasMany<SeccaoMetalThursday, $this> Relação com as secções.
     *
     * @since 1.0.0
     */
    public function seccoes(): HasMany
    {
        return $this
            ->hasMany(
                SeccaoMetalThursday::class,
                'metal_thursday_id',
            )
            ->orderBy(
                'ordem',
            )
            ->orderBy(
                'id',
            );
    }

    /**
     * Limita a consulta às MetalThursdays já publicadas.
     *
     * A publicação é derivada exclusivamente da data da MetalThursday. No
     * próprio dia, a MetalThursday considera-se publicada desde as 00:00 no
     * fuso horário configurado pela aplicação.
     *
     * @param  Builder<MetalThursday>  $construtor  Consulta das
     *                                              MetalThursdays.
     * @param  CarbonInterface|null  $referencia  Instante utilizado como
     *                                            referência opcional.
     * @return Builder<MetalThursday> Consulta limitada às publicadas.
     *
     * @since 2.0.0
     */
    public function scopePublicadas(
        Builder $construtor,
        ?CarbonInterface $referencia = null,
    ): Builder {
        return $construtor->where(
            $construtor
                ->getModel()
                ->qualifyColumn(
                    'data',
                ),
            '<=',
            self::obterDataReferenciaPublicacao(
                $referencia,
            ),
        );
    }

    /**
     * Limita a consulta às MetalThursdays preparadas mas ainda não publicadas.
     *
     * @param  Builder<MetalThursday>  $construtor  Consulta das
     *                                              MetalThursdays.
     * @param  CarbonInterface|null  $referencia  Instante utilizado como
     *                                            referência opcional.
     * @return Builder<MetalThursday> Consulta limitada às preparadas.
     *
     * @since 2.0.0
     */
    public function scopePreparadasPorPublicar(
        Builder $construtor,
        ?CarbonInterface $referencia = null,
    ): Builder {
        return $construtor->where(
            $construtor
                ->getModel()
                ->qualifyColumn(
                    'data',
                ),
            '>',
            self::obterDataReferenciaPublicacao(
                $referencia,
            ),
        );
    }

    /**
     * Restringe a consulta às MetalThursdays já publicadas cuja notificação de
     * publicação ainda não foi processada.
     *
     * Registos eliminados logicamente continuam excluídos pelo comportamento
     * normal do modelo.
     *
     * @param  Builder<MetalThursday>  $construtor  Consulta modificada.
     * @return Builder<MetalThursday> Consulta das publicações por notificar.
     *
     * @since 2.0.0
     */
    public function scopePublicadasPorNotificar(
        Builder $construtor,
    ): Builder {
        return $construtor
            ->publicadas()
            ->whereNull(
                $construtor
                    ->getModel()
                    ->qualifyColumn(
                        self::COLUNA_PUBLICACAO_NOTIFICADA_EM,
                    ),
            );
    }

    /**
     * Indica se esta MetalThursday já se encontra publicada.
     *
     * @param  CarbonInterface|null  $referencia  Instante utilizado como
     *                                            referência opcional.
     * @return bool Verdadeiro quando a data já chegou.
     *
     * @throws LogicException Quando o modelo não possui uma data válida.
     *
     * @since 2.0.0
     */
    public function estaPublicada(
        ?CarbonInterface $referencia = null,
    ): bool {
        $data =
            $this->data;

        if (! $data instanceof CarbonInterface) {
            throw new LogicException(
                'A MetalThursday não possui uma data válida.',
            );
        }

        return $data->format(
            'Y-m-d',
        ) <= self::obterDataReferenciaPublicacao(
            $referencia,
        );
    }

    /**
     * Indica se esta MetalThursday está preparada para publicação futura.
     *
     * @param  CarbonInterface|null  $referencia  Instante utilizado como
     *                                            referência opcional.
     * @return bool Verdadeiro quando a data ainda não chegou.
     *
     * @throws LogicException Quando o modelo não possui uma data válida.
     *
     * @since 2.0.0
     */
    public function estaPreparada(
        ?CarbonInterface $referencia = null,
    ): bool {
        return ! $this->estaPublicada(
            $referencia,
        );
    }

    /**
     * Obtém a data local utilizada na decisão de publicação.
     *
     * Um instante explícito é convertido para o fuso horário da aplicação antes
     * da comparação. Sem referência, é utilizado o instante atual nesse mesmo
     * fuso horário.
     *
     * @param  CarbonInterface|null  $referencia  Instante de referência.
     * @return string Data no formato AAAA-MM-DD.
     *
     * @since 2.0.0
     */
    private static function obterDataReferenciaPublicacao(
        ?CarbonInterface $referencia = null,
    ): string {
        $fusoHorario =
            config(
                'app.timezone',
            );

        $instante =
            $referencia instanceof CarbonInterface
            ? CarbonImmutable::instance(
                $referencia,
            )->setTimezone(
                $fusoHorario,
            )
            : CarbonImmutable::now(
                $fusoHorario,
            );

        return $instante->format(
            'Y-m-d',
        );
    }

    /**
     * Acrescenta à consulta o número sequencial de cada MetalThursday na
     * respetiva edição.
     *
     * O cálculo é executado pela base de dados na mesma consulta dos
     * registos. São consideradas apenas MetalThursdays ativas da mesma
     * edição com data anterior ou igual à do registo exterior.
     *
     * @param  Builder<MetalThursday>  $construtor  Consulta das
     *                                              MetalThursdays.
     * @return Builder<MetalThursday> Consulta com o agregado acrescentado.
     *
     * @since 2.0.0
     */
    public function scopeComNumeroSemanaNaEdicao(
        Builder $construtor,
    ): Builder {
        $modelo = $construtor->getModel();

        if ($construtor->getQuery()->columns === null) {
            $construtor->select(
                $modelo->qualifyColumn(
                    '*',
                ),
            );
        }

        $subconsulta = DB::table(
            sprintf(
                '%s as %s',
                $modelo->getTable(),
                self::ALIAS_METAL_THURSDAYS_ANTERIORES,
            ),
        )
            ->selectRaw(
                'COUNT(*)',
            )
            ->whereColumn(
                self::ALIAS_METAL_THURSDAYS_ANTERIORES.'.edicao_id',
                $modelo->qualifyColumn(
                    'edicao_id',
                ),
            )
            ->whereColumn(
                self::ALIAS_METAL_THURSDAYS_ANTERIORES.'.data',
                '<=',
                $modelo->qualifyColumn(
                    'data',
                ),
            )
            ->whereNull(
                self::ALIAS_METAL_THURSDAYS_ANTERIORES.'.deleted_at',
            );

        return $construtor->addSelect([
            self::COLUNA_NUMERO_SEMANA_NA_EDICAO => $subconsulta,
        ]);
    }

    /**
     * Carrega explicitamente o número sequencial desta MetalThursday na
     * respetiva edição.
     *
     * O valor calculado é sincronizado como atributo original para impedir
     * que uma gravação posterior tente persistir o alias da consulta.
     *
     * @return $this Modelo com o número da semana carregado.
     *
     * @since 2.0.0
     */
    public function carregarNumeroSemanaNaEdicao(): self
    {
        $numeroSemana = null;

        if (
            $this->exists
            && ! $this->trashed()
            && is_numeric(
                $this->getKey(),
            )
        ) {
            $registoNumerado = self::query()
                ->select([
                    $this->getQualifiedKeyName(),
                ])
                ->comNumeroSemanaNaEdicao()
                ->whereKey(
                    $this->getKey(),
                )
                ->first();

            if ($registoNumerado instanceof self) {
                $valor = $registoNumerado->getAttribute(
                    self::COLUNA_NUMERO_SEMANA_NA_EDICAO,
                );

                if (is_numeric($valor)) {
                    $numeroSemana = (int) $valor;
                }
            }
        }

        $this->setAttribute(
            self::COLUNA_NUMERO_SEMANA_NA_EDICAO,
            $numeroSemana,
        );

        $this->syncOriginalAttribute(
            self::COLUNA_NUMERO_SEMANA_NA_EDICAO,
        );

        return $this;
    }
}
