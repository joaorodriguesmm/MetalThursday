<?php

declare(strict_types=1);

namespace App\Models\MetalThursday;

use App\Traits\Auditoria\RegistaAutoria;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Database\Factories\MetalThursday\EdicaoFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Representa uma edição do MetalThursday.
 *
 * Cada edição define um período temporal, pode possuir uma ligação para uma
 * compilação e agrupa várias MetalThursdays e escolhas de músicas favoritas.
 *
 * A coluna gerada `nome_ativo` garante a unicidade do nome entre edições não
 * eliminadas logicamente e não constitui um atributo editável da aplicação.
 *
 * @property int $id
 * @property string $nome
 * @property string|null $nome_ativo
 * @property CarbonImmutable $data_inicio
 * @property CarbonImmutable|null $data_fim
 * @property string|null $ligacao_compilacao
 * @property int|null $criado_por_id
 * @property int|null $atualizado_por_id
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property CarbonInterface|null $deleted_at
 * @property-read Collection<int, MetalThursday> $metalThursdays
 * @property-read Collection<int, MusicaFavoritaEdicao> $musicasFavoritas
 *
 * @since 1.0.0
 *
 * @version 3.1.0
 */
class Edicao extends Model
{
    /** @use HasFactory<EdicaoFactory> */
    use HasFactory;

    use RegistaAutoria;
    use SoftDeletes;

    /**
     * Comprimento máximo permitido para o nome.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public const COMPRIMENTO_MAXIMO_NOME = 255;

    /**
     * Comprimento máximo permitido para a ligação da compilação.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public const COMPRIMENTO_MAXIMO_LIGACAO_COMPILACAO = 2048;

    /**
     * Nome físico da tabela associada ao modelo.
     *
     * @var string
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    protected $table = 'edicoes';

    /**
     * Atributos permitidos em operações de atribuição em massa.
     *
     * Os campos de auditoria são preenchidos automaticamente pelo trait
     * {@see RegistaAutoria}. A coluna `nome_ativo` é gerada pela base de dados
     * e não pode ser atribuída pela aplicação.
     *
     * @var list<string>
     *
     * @since 1.0.0
     *
     * @version 3.0.0
     */
    protected $fillable = [
        'nome',
        'data_inicio',
        'data_fim',
        'ligacao_compilacao',
    ];

    /**
     * Atributos internos omitidos das representações serializadas.
     *
     * @var list<string>
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    protected $hidden = [
        'nome_ativo',
    ];

    /**
     * Regista as validações executadas antes da persistência.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    protected static function booted(): void
    {
        static::saving(
            static function (
                self $edicao,
            ): void {
                $edicao->validarPeriodo();
            },
        );
    }

    /**
     * Define as conversões automáticas dos atributos.
     *
     * @return array<string, string> Conversões dos atributos.
     *
     * @since 1.0.0
     *
     * @version 3.0.0
     */
    protected function casts(): array
    {
        return [
            'data_inicio' => 'immutable_date',

            'data_fim' => 'immutable_date',

            'criado_por_id' => 'integer',

            'atualizado_por_id' => 'integer',
        ];
    }

    /**
     * Cria a factory associada ao modelo.
     *
     * @return EdicaoFactory Factory das edições.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    protected static function newFactory(): EdicaoFactory
    {
        return EdicaoFactory::new();
    }

    /**
     * Normaliza e valida o nome da edição.
     *
     * Os espaços exteriores e consecutivos são normalizados. Quebras de
     * linha, tabulações e restantes caracteres de controlo não são aceites.
     *
     * @return Attribute<string, string> Atributo do nome.
     *
     * @throws InvalidArgumentException Quando o nome não é válido.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    protected function nome(): Attribute
    {
        return Attribute::make(
            set: static function (
                mixed $valor,
            ): string {
                if (! is_string($valor)) {
                    throw new InvalidArgumentException(
                        'O nome da edição deve ser uma sequência de caracteres.',
                    );
                }

                if (
                    preg_match(
                        '//u',
                        $valor,
                    ) !== 1
                ) {
                    throw new InvalidArgumentException(
                        'O nome da edição contém texto inválido.',
                    );
                }

                if (
                    preg_match(
                        '/[\x00-\x1F\x7F]/',
                        $valor,
                    ) === 1
                ) {
                    throw new InvalidArgumentException(
                        'O nome da edição contém caracteres inválidos.',
                    );
                }

                $nomeNormalizado = Str::squish(
                    $valor,
                );

                if ($nomeNormalizado === '') {
                    throw new InvalidArgumentException(
                        'O nome da edição é obrigatório.',
                    );
                }

                if (
                    mb_strlen(
                        $nomeNormalizado,
                    ) > self::COMPRIMENTO_MAXIMO_NOME
                ) {
                    throw new InvalidArgumentException(
                        sprintf(
                            'O nome da edição não pode ter mais de %d caracteres.',
                            self::COMPRIMENTO_MAXIMO_NOME,
                        ),
                    );
                }

                return $nomeNormalizado;
            },
        );
    }

    /**
     * Normaliza e valida a ligação da compilação.
     *
     * Um valor nulo ou vazio remove a ligação. Apenas endereços absolutos
     * HTTP ou HTTPS, sem credenciais, espaços interiores, caracteres de
     * controlo ou barras invertidas, são aceites.
     *
     * @return Attribute<string|null, string|null> Atributo da ligação.
     *
     * @throws InvalidArgumentException Quando a ligação não é válida.
     *
     * @since 2.0.0
     *
     * @version 2.1.0
     */
    protected function ligacaoCompilacao(): Attribute
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
                        'A ligação da compilação deve ser uma sequência de caracteres.',
                    );
                }

                $ligacaoNormalizada = trim(
                    $valor,
                );

                if ($ligacaoNormalizada === '') {
                    return null;
                }

                if (
                    preg_match(
                        '//u',
                        $ligacaoNormalizada,
                    ) !== 1
                    || mb_strlen(
                        $ligacaoNormalizada,
                    ) > self::COMPRIMENTO_MAXIMO_LIGACAO_COMPILACAO
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
                        'A ligação da compilação não é válida.',
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
                    || trim(
                        (string) $componentes['host'],
                    ) === ''
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
                ) {
                    throw new InvalidArgumentException(
                        'A ligação da compilação deve utilizar HTTP ou HTTPS, possuir um anfitrião válido e não pode incluir credenciais.',
                    );
                }

                return $ligacaoNormalizada;
            },
        );
    }

    /**
     * Obtém as MetalThursdays pertencentes à edição.
     *
     * Os registos são devolvidos cronologicamente, com o identificador como
     * critério de desempate.
     *
     * @return HasMany<MetalThursday, $this> Relação com as MetalThursdays.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function metalThursdays(): HasMany
    {
        return $this
            ->hasMany(
                MetalThursday::class,
                'edicao_id',
            )
            ->orderBy(
                'data',
            )
            ->orderBy(
                'id',
            );
    }

    /**
     * Obtém as músicas favoritas registadas para a edição.
     *
     * Os registos são ordenados por utilizador, posição e identificador para
     * manter uma apresentação determinística.
     *
     * @return HasMany<MusicaFavoritaEdicao, $this> Relação com as músicas.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function musicasFavoritas(): HasMany
    {
        return $this
            ->hasMany(
                MusicaFavoritaEdicao::class,
                'edicao_id',
            )
            ->orderBy(
                'utilizador_id',
            )
            ->orderBy(
                'posicao',
            )
            ->orderBy(
                'id',
            );
    }

    /**
     * Valida a coerência do período da edição.
     *
     * A data final pode ser nula para representar uma edição ainda em curso.
     * Quando está preenchida, não pode ser anterior à data inicial.
     *
     * A mesma regra é também garantida pela restrição `CHECK` da base de
     * dados.
     *
     * @throws InvalidArgumentException Quando o período não é válido.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function validarPeriodo(): void
    {
        $dataInicio = $this->data_inicio;

        if (! $dataInicio instanceof CarbonInterface) {
            throw new InvalidArgumentException(
                'A data de início da edição é obrigatória.',
            );
        }

        $dataFim = $this->data_fim;

        if ($dataFim === null) {
            return;
        }

        if (! $dataFim instanceof CarbonInterface) {
            throw new InvalidArgumentException(
                'A data de fim da edição não é válida.',
            );
        }

        if (
            $dataFim->lessThan(
                $dataInicio,
            )
        ) {
            throw new InvalidArgumentException(
                'A data de fim da edição não pode ser anterior à data de início.',
            );
        }
    }
}
