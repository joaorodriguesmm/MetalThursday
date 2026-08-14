<?php

declare(strict_types=1);

namespace App\Models\MetalThursday;

use App\Enumeracoes\TipoIncorporacao;
use App\Models\Interacoes\Audicao;
use App\Models\Interacoes\Avaliacao;
use App\Models\Interacoes\Comentario;
use App\Models\Musica\Banda;
use App\Traits\Auditoria\RegistaAutoria;
use App\Traits\Interacoes\TemAudicoes;
use App\Traits\Interacoes\TemAvaliacoes;
use App\Traits\Interacoes\TemComentarios;
use Carbon\CarbonInterface;
use Database\Factories\MetalThursday\SeccaoMetalThursdayFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use InvalidArgumentException;

/**
 * Representa uma secção pertencente a uma MetalThursday.
 *
 * Cada secção possui um tipo, uma posição e uma descrição obrigatória. Pode
 * ainda incluir uma banda, um título, uma ligação externa, um tipo de
 * incorporação e um ano.
 *
 * As secções suportam comentários, avaliações e registos de audição através
 * de relações polimórficas.
 *
 * A coluna gerada `ordem_ativa` garante que não existem duas secções ativas
 * com a mesma posição dentro da mesma MetalThursday.
 *
 * @property int $id
 * @property int $metal_thursday_id
 * @property int $tipo_seccao_id
 * @property int $ordem
 * @property int|null $ordem_ativa
 * @property string|null $titulo
 * @property string $descricao
 * @property int|null $banda_id
 * @property string|null $ligacao
 * @property TipoIncorporacao|null $tipo_incorporacao
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
 */
class SeccaoMetalThursday extends Model
{
    /** @use HasFactory<SeccaoMetalThursdayFactory> */
    use HasFactory;

    use RegistaAutoria;
    use SoftDeletes;
    use TemAudicoes;
    use TemAvaliacoes;
    use TemComentarios;

    /**
     * Ordem mínima permitida.
     *
     * @since 2.0.0
     */
    public const ORDEM_MINIMA = 1;

    /**
     * Ordem máxima permitida pela coluna unsigned small integer.
     *
     * @since 2.0.0
     */
    public const ORDEM_MAXIMA = 65_535;

    /**
     * Comprimento máximo permitido para o título.
     *
     * @since 2.0.0
     */
    public const COMPRIMENTO_MAXIMO_TITULO = 255;

    /**
     * Comprimento máximo permitido para a descrição.
     *
     * Este é um limite funcional da aplicação, deliberadamente inferior à
     * capacidade da coluna SQL `MEDIUMTEXT`.
     *
     * @since 2.0.0
     */
    public const COMPRIMENTO_MAXIMO_DESCRICAO = 65_535;

    /**
     * Comprimento máximo permitido para a ligação.
     *
     * @since 2.0.0
     */
    public const COMPRIMENTO_MAXIMO_LIGACAO = 2048;

    /**
     * Primeiro ano permitido pelo domínio musical da aplicação.
     *
     * @since 2.0.0
     */
    public const ANO_MINIMO = 1900;

    /**
     * Último ano permitido pelo domínio e pela restrição da base de dados.
     *
     * @since 2.0.0
     */
    public const ANO_MAXIMO = 2155;

    /**
     * Nome físico da tabela associada ao modelo.
     *
     * @var string
     *
     * @since 1.0.0
     */
    protected $table = 'seccoes_metal_thursday';

    /**
     * Atributos permitidos em operações de atribuição em massa.
     *
     * A MetalThursday, o tipo de secção e a banda devem ser associados
     * explicitamente pelo serviço responsável pela persistência.
     *
     * Os identificadores de auditoria são preenchidos automaticamente pelo
     * trait {@see RegistaAutoria}. A coluna `ordem_ativa` é gerada pela base
     * de dados e não pode ser atribuída pela aplicação.
     *
     * @var list<string>
     *
     * @since 1.0.0
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
     * Atributos internos omitidos das representações serializadas.
     *
     * @var list<string>
     *
     * @since 2.0.0
     */
    protected $hidden = [
        'ordem_ativa',
    ];

    /**
     * Regista as validações de coerência executadas antes da persistência.
     *
     * @since 2.0.0
     */
    protected static function booted(): void
    {
        static::saving(
            static function (
                self $seccao,
            ): void {
                $seccao->validarIncorporacao();
            },
        );
    }

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
            'metal_thursday_id' => 'integer',

            'tipo_seccao_id' => 'integer',

            'ordem_ativa' => 'integer',

            'banda_id' => 'integer',

            'tipo_incorporacao' => TipoIncorporacao::class,

            'criado_por_id' => 'integer',

            'atualizado_por_id' => 'integer',
        ];
    }

    /**
     * Cria a factory associada ao modelo.
     *
     * @return SeccaoMetalThursdayFactory Factory das secções.
     *
     * @since 2.0.0
     */
    protected static function newFactory(): SeccaoMetalThursdayFactory
    {
        return SeccaoMetalThursdayFactory::new();
    }

    /**
     * Normaliza e valida a ordem da secção.
     *
     * Apenas números inteiros pertencentes ao intervalo da coluna são
     * aceites. A unicidade da posição entre secções ativas da mesma
     * MetalThursday é garantida pela coluna gerada e pelo índice único da
     * base de dados.
     *
     * @return Attribute<int, int> Atributo da ordem.
     *
     * @throws InvalidArgumentException Quando a ordem não é válida.
     *
     * @since 2.0.0
     */
    protected function ordem(): Attribute
    {
        return Attribute::make(
            get: static fn (
                mixed $valor,
            ): int => (int) $valor,

            set: static function (
                mixed $valor,
            ): int {
                if (
                    ! is_int($valor)
                    || $valor < self::ORDEM_MINIMA
                    || $valor > self::ORDEM_MAXIMA
                ) {
                    throw new InvalidArgumentException(
                        sprintf(
                            'A ordem da secção deve estar entre %d e %d.',
                            self::ORDEM_MINIMA,
                            self::ORDEM_MAXIMA,
                        ),
                    );
                }

                return $valor;
            },
        );
    }

    /**
     * Normaliza e valida o título opcional da secção.
     *
     * Um valor nulo ou vazio remove o título. Espaços exteriores e
     * consecutivos são normalizados. Caracteres de controlo não são aceites.
     *
     * @return Attribute<string|null, string|null> Atributo do título.
     *
     * @throws InvalidArgumentException Quando o título não é válido.
     *
     * @since 2.0.0
     */
    protected function titulo(): Attribute
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
                        'O título da secção deve ser uma sequência de caracteres.',
                    );
                }

                self::validarTextoUtf8(
                    $valor,
                    'O título da secção contém texto inválido.',
                );

                if (
                    preg_match(
                        '/[\x00-\x1F\x7F]/',
                        $valor,
                    ) === 1
                ) {
                    throw new InvalidArgumentException(
                        'O título da secção contém caracteres inválidos.',
                    );
                }

                $tituloNormalizado = preg_replace(
                    '/\s+/u',
                    ' ',
                    $valor,
                );

                if (! is_string($tituloNormalizado)) {
                    throw new InvalidArgumentException(
                        'Não foi possível normalizar o título da secção.',
                    );
                }

                $tituloNormalizado = trim(
                    $tituloNormalizado,
                );

                if ($tituloNormalizado === '') {
                    return null;
                }

                if (
                    mb_strlen(
                        $tituloNormalizado,
                    ) > self::COMPRIMENTO_MAXIMO_TITULO
                ) {
                    throw new InvalidArgumentException(
                        sprintf(
                            'O título da secção não pode ter mais de %d caracteres.',
                            self::COMPRIMENTO_MAXIMO_TITULO,
                        ),
                    );
                }

                return $tituloNormalizado;
            },
        );
    }

    /**
     * Normaliza e valida a descrição obrigatória da secção.
     *
     * As quebras de linha são preservadas num formato uniforme. Espaços
     * ASCII, tabulações e quebras de linha exteriores são removidos, mas os
     * parágrafos e restantes espaços interiores são preservados.
     *
     * @return Attribute<string, string> Atributo da descrição.
     *
     * @throws InvalidArgumentException Quando a descrição não é válida.
     *
     * @since 2.0.0
     */
    protected function descricao(): Attribute
    {
        return Attribute::make(
            set: static function (
                mixed $valor,
            ): string {
                if (! is_string($valor)) {
                    throw new InvalidArgumentException(
                        'A descrição da secção deve ser uma sequência de caracteres.',
                    );
                }

                self::validarTextoUtf8(
                    $valor,
                    'A descrição da secção contém texto inválido.',
                );

                if (
                    preg_match(
                        '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/',
                        $valor,
                    ) === 1
                ) {
                    throw new InvalidArgumentException(
                        'A descrição da secção contém caracteres inválidos.',
                    );
                }

                $descricaoNormalizada = str_replace(
                    [
                        "\r\n",
                        "\r",
                    ],
                    "\n",
                    $valor,
                );

                $descricaoNormalizada = trim(
                    $descricaoNormalizada,
                    " \t\n",
                );

                if ($descricaoNormalizada === '') {
                    throw new InvalidArgumentException(
                        'A descrição da secção é obrigatória.',
                    );
                }

                if (
                    mb_strlen(
                        $descricaoNormalizada,
                    ) > self::COMPRIMENTO_MAXIMO_DESCRICAO
                ) {
                    throw new InvalidArgumentException(
                        sprintf(
                            'A descrição da secção não pode ter mais de %d caracteres.',
                            self::COMPRIMENTO_MAXIMO_DESCRICAO,
                        ),
                    );
                }

                return $descricaoNormalizada;
            },
        );
    }

    /**
     * Normaliza e valida a ligação opcional da secção.
     *
     * Um valor nulo ou vazio remove a ligação. Apenas endereços absolutos
     * HTTP ou HTTPS, sem credenciais incorporadas, são aceites.
     *
     * Apenas espaços ASCII exteriores são removidos antes da validação.
     * Caracteres de controlo permanecem intactos para serem rejeitados.
     *
     * @return Attribute<string|null, string|null> Atributo da ligação.
     *
     * @throws InvalidArgumentException Quando a ligação não é válida.
     *
     * @since 2.0.0
     */
    protected function ligacao(): Attribute
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
                        'A ligação da secção deve ser uma sequência de caracteres.',
                    );
                }

                self::validarTextoUtf8(
                    $valor,
                    'A ligação da secção contém texto inválido.',
                );

                $ligacaoNormalizada = trim(
                    $valor,
                    ' ',
                );

                if ($ligacaoNormalizada === '') {
                    return null;
                }

                if (
                    mb_strlen(
                        $ligacaoNormalizada,
                    ) > self::COMPRIMENTO_MAXIMO_LIGACAO
                    || str_contains(
                        $ligacaoNormalizada,
                        '\\',
                    )
                    || preg_match(
                        '/[\x00-\x20\x7F]/',
                        $ligacaoNormalizada,
                    ) === 1
                    || filter_var(
                        $ligacaoNormalizada,
                        FILTER_VALIDATE_URL,
                    ) === false
                ) {
                    throw new InvalidArgumentException(
                        'A ligação da secção não é válida.',
                    );
                }

                $componentes = parse_url(
                    $ligacaoNormalizada,
                );

                if (
                    ! is_array($componentes)
                    || ! isset(
                        $componentes['scheme'],
                        $componentes['host'],
                    )
                    || isset(
                        $componentes['user'],
                    )
                    || isset(
                        $componentes['pass'],
                    )
                    || ! in_array(
                        mb_strtolower(
                            (string) $componentes['scheme'],
                        ),
                        [
                            'http',
                            'https',
                        ],
                        true,
                    )
                    || trim(
                        (string) $componentes['host'],
                    ) === ''
                ) {
                    throw new InvalidArgumentException(
                        'A ligação da secção deve utilizar HTTP ou HTTPS e não pode incluir credenciais.',
                    );
                }

                return $ligacaoNormalizada;
            },
        );
    }

    /**
     * Normaliza e valida o ano opcional da secção.
     *
     * Apenas valores inteiros pertencentes ao intervalo definido pelo domínio
     * e pela restrição da base de dados são aceites.
     *
     * @return Attribute<int|null, int|null> Atributo do ano.
     *
     * @throws InvalidArgumentException Quando o ano não é válido.
     *
     * @since 2.0.0
     */
    protected function ano(): Attribute
    {
        return Attribute::make(
            get: static fn (
                mixed $valor,
            ): ?int => $valor === null
                ? null
                : (int) $valor,

            set: static function (
                mixed $valor,
            ): ?int {
                if ($valor === null) {
                    return null;
                }

                if (
                    ! is_int($valor)
                    || $valor < self::ANO_MINIMO
                    || $valor > self::ANO_MAXIMO
                ) {
                    throw new InvalidArgumentException(
                        sprintf(
                            'O ano da secção deve estar compreendido entre %d e %d.',
                            self::ANO_MINIMO,
                            self::ANO_MAXIMO,
                        ),
                    );
                }

                return $valor;
            },
        );
    }

    /**
     * Limita a consulta às secções ordenadas pela posição definida.
     *
     * O identificador é utilizado como segundo critério para garantir uma
     * ordenação determinística.
     *
     * @param  Builder<SeccaoMetalThursday>  $construtor  Consulta das secções.
     * @return Builder<SeccaoMetalThursday> Consulta ordenada.
     *
     * @since 2.0.0
     */
    public function scopeOrdenadas(
        Builder $construtor,
    ): Builder {
        return $construtor
            ->orderBy(
                'ordem',
            )
            ->orderBy(
                'id',
            );
    }

    /**
     * Obtém a MetalThursday à qual pertence a secção.
     *
     * A MetalThursday continua acessível quando foi eliminada logicamente,
     * preservando o contexto histórico da secção.
     *
     * @return BelongsTo<MetalThursday, $this> Relação com a MetalThursday.
     *
     * @since 1.0.0
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
     * A banda continua acessível quando foi eliminada logicamente,
     * preservando o conteúdo histórico da secção.
     *
     * A relação pode ser nula quando a secção não possui uma banda ou quando
     * a banda foi eliminada fisicamente.
     *
     * @return BelongsTo<Banda, $this> Relação com a banda.
     *
     * @since 1.0.0
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

    /**
     * Valida a coerência entre a ligação e o tipo de incorporação.
     *
     * A ligação e o tipo de incorporação devem existir em conjunto. A mesma
     * regra é garantida pela restrição `CHECK` da base de dados.
     *
     * @throws InvalidArgumentException Quando apenas um dos dois atributos
     *                                  está preenchido.
     *
     * @since 2.0.0
     */
    private function validarIncorporacao(): void
    {
        $temLigacao =
            $this->ligacao !== null;

        $temTipoIncorporacao =
            $this->tipo_incorporacao !== null;

        if ($temLigacao === $temTipoIncorporacao) {
            return;
        }

        throw new InvalidArgumentException(
            'A ligação e o tipo de incorporação devem ser indicados em conjunto.',
        );
    }

    /**
     * Valida que um texto utiliza uma codificação UTF-8 válida.
     *
     * @param  string  $valor  Texto recebido.
     * @param  string  $mensagem  Mensagem utilizada em caso de erro.
     *
     * @throws InvalidArgumentException Quando o texto não é UTF-8 válido.
     *
     * @since 2.0.0
     */
    private static function validarTextoUtf8(
        string $valor,
        string $mensagem,
    ): void {
        if (
            preg_match(
                '//u',
                $valor,
            ) === 1
        ) {
            return;
        }

        throw new InvalidArgumentException(
            $mensagem,
        );
    }
}
