<?php

declare(strict_types=1);

namespace App\Models\MetalThursday;

use App\Enumeracoes\PlataformaLigacao;
use App\Servicos\MetalThursday\ServicoLigacoesSecaoMetalThursday;
use Carbon\CarbonInterface;
use Database\Factories\MetalThursday\LigacaoSeccaoMetalThursdayFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;

/**
 * Representa uma ligação pública pertencente a uma secção MetalThursday.
 *
 * A plataforma é sempre calculada no servidor a partir do URL. A opção de
 * incorporação é independente por ligação e é automaticamente desactivada
 * quando a plataforma não a suporta.
 *
 * @property int $id
 * @property int $seccao_metal_thursday_id
 * @property PlataformaLigacao $plataforma
 * @property string|null $etiqueta
 * @property string $url
 * @property bool $incorporar
 * @property int $ordem
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read SeccaoMetalThursday $seccaoMetalThursday
 *
 * @since 2.0.0
 */
class LigacaoSeccaoMetalThursday extends Model
{
    /** @use HasFactory<LigacaoSeccaoMetalThursdayFactory> */
    use HasFactory;

    /**
     * Comprimento máximo permitido para uma etiqueta personalizada.
     *
     * @since 2.0.0
     */
    public const COMPRIMENTO_MAXIMO_ETIQUETA = 255;

    /**
     * Comprimento máximo permitido para o URL.
     *
     * @since 2.0.0
     */
    public const COMPRIMENTO_MAXIMO_URL = 2048;

    /**
     * Número máximo funcional de ligações permitido por secção.
     *
     * @since 2.0.0
     */
    public const NUMERO_MAXIMO_POR_SECCAO = 20;

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
     * Nome físico da tabela associada ao modelo.
     *
     * @var string
     *
     * @since 2.0.0
     */
    protected $table = 'ligacoes_seccao_metal_thursday';

    /**
     * Atributos permitidos em operações de atribuição em massa.
     *
     * A plataforma não é atribuível directamente: é detectada no evento de
     * persistência a partir do URL.
     *
     * @var list<string>
     *
     * @since 2.0.0
     */
    protected $fillable = [
        'seccao_metal_thursday_id',
        'etiqueta',
        'url',
        'incorporar',
        'ordem',
    ];

    /**
     * Regista as invariantes calculadas antes da persistência.
     *
     * @since 2.0.0
     */
    protected static function booted(): void
    {
        static::saving(
            static function (
                self $ligacao,
            ): void {
                $servico = app(
                    ServicoLigacoesSecaoMetalThursday::class,
                );

                $plataforma = $servico->detetarPlataforma(
                    $ligacao->url,
                );

                $ligacao->plataforma = $plataforma;

                if (! $plataforma->suportaIncorporacao()) {
                    $ligacao->incorporar = false;
                }
            },
        );
    }

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
            'seccao_metal_thursday_id' => 'integer',
            'plataforma' => PlataformaLigacao::class,
            'incorporar' => 'boolean',
            'ordem' => 'integer',
        ];
    }

    /**
     * Cria a factory associada ao modelo.
     *
     * @return LigacaoSeccaoMetalThursdayFactory Factory das ligações.
     *
     * @since 2.0.0
     */
    protected static function newFactory(): LigacaoSeccaoMetalThursdayFactory
    {
        return LigacaoSeccaoMetalThursdayFactory::new();
    }

    /**
     * Normaliza a etiqueta opcional da ligação.
     *
     * @return Attribute<string|null, string|null> Atributo da etiqueta.
     *
     * @throws InvalidArgumentException Quando a etiqueta não é válida.
     *
     * @since 2.0.0
     */
    protected function etiqueta(): Attribute
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
                        'A etiqueta da ligação deve ser uma sequência de caracteres.',
                    );
                }

                if (
                    preg_match(
                        '//u',
                        $valor,
                    ) !== 1
                    || preg_match(
                        '/[\x00-\x1F\x7F]/',
                        $valor,
                    ) === 1
                ) {
                    throw new InvalidArgumentException(
                        'A etiqueta da ligação contém texto inválido.',
                    );
                }

                $etiqueta = preg_replace(
                    '/\s+/u',
                    ' ',
                    $valor,
                );

                if (! is_string($etiqueta)) {
                    throw new InvalidArgumentException(
                        'Não foi possível normalizar a etiqueta da ligação.',
                    );
                }

                $etiqueta = trim(
                    $etiqueta,
                );

                if ($etiqueta === '') {
                    return null;
                }

                if (
                    mb_strlen(
                        $etiqueta,
                    ) > self::COMPRIMENTO_MAXIMO_ETIQUETA
                ) {
                    throw new InvalidArgumentException(
                        sprintf(
                            'A etiqueta da ligação não pode ter mais de %d caracteres.',
                            self::COMPRIMENTO_MAXIMO_ETIQUETA,
                        ),
                    );
                }

                return $etiqueta;
            },
        );
    }

    /**
     * Normaliza e valida o URL da ligação.
     *
     * Apenas URLs absolutos HTTP ou HTTPS, sem credenciais incorporadas, são
     * aceites.
     *
     * @return Attribute<string, string> Atributo do URL.
     *
     * @throws InvalidArgumentException Quando o URL não é válido.
     *
     * @since 2.0.0
     */
    protected function url(): Attribute
    {
        return Attribute::make(
            set: static function (
                mixed $valor,
            ): string {
                if (! is_string($valor)) {
                    throw new InvalidArgumentException(
                        'O URL da ligação deve ser uma sequência de caracteres.',
                    );
                }

                if (
                    preg_match(
                        '//u',
                        $valor,
                    ) !== 1
                ) {
                    throw new InvalidArgumentException(
                        'O URL da ligação contém texto inválido.',
                    );
                }

                $url = trim(
                    $valor,
                    ' ',
                );

                if (
                    $url === ''
                    || mb_strlen(
                        $url,
                    ) > self::COMPRIMENTO_MAXIMO_URL
                    || str_contains(
                        $url,
                        '\\',
                    )
                    || preg_match(
                        '/[\x00-\x20\x7F]/',
                        $url,
                    ) === 1
                    || filter_var(
                        $url,
                        FILTER_VALIDATE_URL,
                    ) === false
                ) {
                    throw new InvalidArgumentException(
                        'O URL da ligação não é válido.',
                    );
                }

                $componentes = parse_url(
                    $url,
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
                        'O URL da ligação deve utilizar HTTP ou HTTPS e não pode incluir credenciais.',
                    );
                }

                return $url;
            },
        );
    }

    /**
     * Normaliza e valida a ordem da ligação.
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
                            'A ordem da ligação deve estar entre %d e %d.',
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
     * Obtém a secção a que pertence a ligação.
     *
     * A secção continua acessível quando foi eliminada logicamente, permitindo
     * preservar as ligações durante uma eventual restauração.
     *
     * @return BelongsTo<SeccaoMetalThursday, $this> Relação com a secção.
     *
     * @since 2.0.0
     */
    public function seccaoMetalThursday(): BelongsTo
    {
        return $this
            ->belongsTo(
                SeccaoMetalThursday::class,
                'seccao_metal_thursday_id',
            )
            ->withTrashed();
    }
}
