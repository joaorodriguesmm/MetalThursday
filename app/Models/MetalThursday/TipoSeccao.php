<?php

declare(strict_types=1);

namespace App\Models\MetalThursday;

use Carbon\CarbonInterface;
use Database\Factories\MetalThursday\TipoSeccaoFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Representa um tipo de secção de uma MetalThursday.
 *
 * Cada tipo possui um identificador técnico estável, um nome, uma descrição,
 * uma ordem e a indicação de que exige informação musical detalhada.
 *
 * @property int $id
 * @property string $identificador
 * @property string $nome
 * @property string $descricao
 * @property bool $exige_detalhes
 * @property int $ordem
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read Collection<int, SeccaoMetalThursday> $seccoes
 *
 * @since 1.0.0
 *
 * @version 3.1.0
 */
class TipoSeccao extends Model
{
    /** @use HasFactory<TipoSeccaoFactory> */
    use HasFactory;

    /**
     * Comprimento máximo do identificador.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public const COMPRIMENTO_MAXIMO_IDENTIFICADOR = 32;

    /**
     * Comprimento máximo do nome.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public const COMPRIMENTO_MAXIMO_NOME = 64;

    /**
     * Comprimento máximo da descrição.
     *
     * O valor corresponde à capacidade da coluna SQL `TEXT`.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public const COMPRIMENTO_MAXIMO_DESCRICAO = 65_535;

    /**
     * Ordem mínima permitida.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public const ORDEM_MINIMA = 1;

    /**
     * Ordem máxima permitida.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public const ORDEM_MAXIMA = 255;

    /**
     * Nome físico da tabela associada ao modelo.
     *
     * @var string
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    protected $table = 'tipos_seccao';

    /**
     * Atributos permitidos em operações de atribuição em massa.
     *
     * @var list<string>
     *
     * @since 1.0.0
     *
     * @version 3.0.0
     */
    protected $fillable = [
        'identificador',
        'nome',
        'descricao',
        'exige_detalhes',
        'ordem',
    ];

    /**
     * Cria a factory associada ao modelo.
     *
     * @return TipoSeccaoFactory Factory dos tipos de secção.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    protected static function newFactory(): TipoSeccaoFactory
    {
        return TipoSeccaoFactory::new();
    }

    /**
     * Normaliza e valida o identificador do tipo.
     *
     * Apenas letras ASCII minúsculas, números e sublinhados interiores são
     * aceites.
     *
     * @return Attribute<string, string> Atributo do identificador.
     *
     * @throws InvalidArgumentException Quando o identificador não é válido.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    protected function identificador(): Attribute
    {
        return Attribute::make(
            set: static function (
                mixed $valor,
            ): string {
                if (! is_string($valor)) {
                    throw new InvalidArgumentException(
                        'O identificador do tipo de secção deve ser uma sequência de caracteres.',
                    );
                }

                self::validarTextoUtf8(
                    $valor,
                    'O identificador do tipo de secção contém texto inválido.',
                );

                $identificadorNormalizado = strtolower(
                    trim(
                        $valor,
                    ),
                );

                if (
                    $identificadorNormalizado === ''
                    || strlen(
                        $identificadorNormalizado,
                    ) > self::COMPRIMENTO_MAXIMO_IDENTIFICADOR
                    || preg_match(
                        '/\A[a-z0-9]+(?:_[a-z0-9]+)*\z/',
                        $identificadorNormalizado,
                    ) !== 1
                ) {
                    throw new InvalidArgumentException(
                        'O identificador do tipo de secção deve conter apenas letras minúsculas, números e sublinhados interiores.',
                    );
                }

                return $identificadorNormalizado;
            },
        );
    }

    /**
     * Normaliza e valida o nome do tipo.
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
     * @version 2.1.0
     */
    protected function nome(): Attribute
    {
        return Attribute::make(
            set: static function (
                mixed $valor,
            ): string {
                if (! is_string($valor)) {
                    throw new InvalidArgumentException(
                        'O nome do tipo de secção deve ser uma sequência de caracteres.',
                    );
                }

                self::validarTextoUtf8(
                    $valor,
                    'O nome do tipo de secção contém texto inválido.',
                );

                self::validarAusenciaCaracteresControlo(
                    $valor,
                    'O nome do tipo de secção contém caracteres inválidos.',
                );

                $nomeNormalizado = Str::squish(
                    $valor,
                );

                if ($nomeNormalizado === '') {
                    throw new InvalidArgumentException(
                        'O nome do tipo de secção não pode estar vazio.',
                    );
                }

                if (
                    mb_strlen(
                        $nomeNormalizado,
                    ) > self::COMPRIMENTO_MAXIMO_NOME
                ) {
                    throw new InvalidArgumentException(
                        sprintf(
                            'O nome do tipo de secção não pode exceder %d caracteres.',
                            self::COMPRIMENTO_MAXIMO_NOME,
                        ),
                    );
                }

                return $nomeNormalizado;
            },
        );
    }

    /**
     * Normaliza e valida a descrição do tipo.
     *
     * A descrição é apresentada como texto de uma única linha. Espaços
     * exteriores e consecutivos são normalizados, mas caracteres de controlo
     * não são silenciosamente removidos.
     *
     * @return Attribute<string, string> Atributo da descrição.
     *
     * @throws InvalidArgumentException Quando a descrição não é válida.
     *
     * @since 2.0.0
     *
     * @version 2.1.0
     */
    protected function descricao(): Attribute
    {
        return Attribute::make(
            set: static function (
                mixed $valor,
            ): string {
                if (! is_string($valor)) {
                    throw new InvalidArgumentException(
                        'A descrição do tipo de secção deve ser uma sequência de caracteres.',
                    );
                }

                self::validarTextoUtf8(
                    $valor,
                    'A descrição do tipo de secção contém texto inválido.',
                );

                self::validarAusenciaCaracteresControlo(
                    $valor,
                    'A descrição do tipo de secção contém caracteres inválidos.',
                );

                $descricaoNormalizada = Str::squish(
                    $valor,
                );

                if ($descricaoNormalizada === '') {
                    throw new InvalidArgumentException(
                        'A descrição do tipo de secção não pode estar vazia.',
                    );
                }

                if (
                    mb_strlen(
                        $descricaoNormalizada,
                    ) > self::COMPRIMENTO_MAXIMO_DESCRICAO
                ) {
                    throw new InvalidArgumentException(
                        sprintf(
                            'A descrição do tipo de secção não pode exceder %d caracteres.',
                            self::COMPRIMENTO_MAXIMO_DESCRICAO,
                        ),
                    );
                }

                return $descricaoNormalizada;
            },
        );
    }

    /**
     * Normaliza e valida a indicação de detalhes obrigatórios.
     *
     * @return Attribute<bool, bool> Atributo da indicação.
     *
     * @throws InvalidArgumentException Quando o valor não é booleano.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    protected function exigeDetalhes(): Attribute
    {
        return Attribute::make(
            get: static fn (
                mixed $valor,
            ): bool => (bool) $valor,

            set: static function (
                mixed $valor,
            ): bool {
                if (! is_bool($valor)) {
                    throw new InvalidArgumentException(
                        'A indicação de detalhes obrigatórios deve ser booleana.',
                    );
                }

                return $valor;
            },
        );
    }

    /**
     * Normaliza e valida a ordem de apresentação.
     *
     * @return Attribute<int, int> Atributo da ordem.
     *
     * @throws InvalidArgumentException Quando a ordem não é válida.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
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
                    ! is_int(
                        $valor,
                    )
                    || $valor < self::ORDEM_MINIMA
                    || $valor > self::ORDEM_MAXIMA
                ) {
                    throw new InvalidArgumentException(
                        sprintf(
                            'A ordem do tipo de secção deve estar entre %d e %d.',
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
     * Obtém as secções que utilizam este tipo.
     *
     * As secções são devolvidas pela ordem definida.
     *
     * @return HasMany<SeccaoMetalThursday, $this> Relação com as secções.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function seccoes(): HasMany
    {
        return $this
            ->hasMany(
                SeccaoMetalThursday::class,
                'tipo_seccao_id',
            )
            ->orderBy(
                'ordem',
            )
            ->orderBy(
                'id',
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
     *
     * @version 1.0.0
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

    /**
     * Valida que um texto não contém caracteres de controlo.
     *
     * @param  string  $valor  Texto recebido.
     * @param  string  $mensagem  Mensagem utilizada em caso de erro.
     *
     * @throws InvalidArgumentException Quando o texto contém caracteres de
     *                                  controlo.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private static function validarAusenciaCaracteresControlo(
        string $valor,
        string $mensagem,
    ): void {
        if (
            preg_match(
                '/[\x00-\x1F\x7F]/',
                $valor,
            ) !== 1
        ) {
            return;
        }

        throw new InvalidArgumentException(
            $mensagem,
        );
    }
}
