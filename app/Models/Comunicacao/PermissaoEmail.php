<?php

declare(strict_types=1);

namespace App\Models\Comunicacao;

use App\Models\Autenticacao\Utilizador;
use Carbon\CarbonInterface;
use Database\Factories\Comunicacao\PermissaoEmailFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Representa uma permissão de comunicação por correio eletrónico.
 *
 * Cada permissão possui um identificador técnico estável, um nome, uma
 * descrição obrigatória e uma ordem de apresentação.
 *
 * @property int $id
 * @property string $identificador
 * @property string $nome
 * @property string $descricao
 * @property int $ordem
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read Collection<int, Utilizador> $utilizadores
 *
 * @since 1.0.0
 *
 * @version 3.1.0
 */
class PermissaoEmail extends Model
{
    /** @use HasFactory<PermissaoEmailFactory> */
    use HasFactory;

    /**
     * Comprimento máximo do identificador.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public const COMPRIMENTO_MAXIMO_IDENTIFICADOR = 64;

    /**
     * Comprimento máximo do nome.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public const COMPRIMENTO_MAXIMO_NOME = 100;

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
     * Nome da tabela intermédia entre permissões e utilizadores.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const TABELA_PERMISSAO_UTILIZADOR =
        'permissao_email_utilizador';

    /**
     * Nome físico da tabela associada ao modelo.
     *
     * @var string
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    protected $table = 'permissoes_email';

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
        'ordem',
    ];

    /**
     * Cria a factory associada ao modelo.
     *
     * @return PermissaoEmailFactory Factory das permissões.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    protected static function newFactory(): PermissaoEmailFactory
    {
        return PermissaoEmailFactory::new();
    }

    /**
     * Normaliza e valida o identificador da permissão.
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
     * @version 2.1.0
     */
    protected function identificador(): Attribute
    {
        return Attribute::make(
            set: static function (
                mixed $valor,
            ): string {
                if (! is_string($valor)) {
                    throw new InvalidArgumentException(
                        'O identificador da permissão deve ser uma sequência de caracteres.',
                    );
                }

                self::validarTextoUtf8(
                    $valor,
                    'O identificador da permissão contém texto inválido.',
                );

                self::validarAusenciaCaracteresControlo(
                    $valor,
                    'O identificador da permissão contém caracteres inválidos.',
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
                        'O identificador da permissão deve conter apenas letras minúsculas, números e sublinhados interiores.',
                    );
                }

                return $identificadorNormalizado;
            },
        );
    }

    /**
     * Normaliza e valida o nome da permissão.
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
                        'O nome da permissão de e-mail deve ser uma sequência de caracteres.',
                    );
                }

                self::validarTextoUtf8(
                    $valor,
                    'O nome da permissão de e-mail contém texto inválido.',
                );

                self::validarAusenciaCaracteresControlo(
                    $valor,
                    'O nome da permissão de e-mail contém caracteres inválidos.',
                );

                $nomeNormalizado = Str::squish(
                    $valor,
                );

                if ($nomeNormalizado === '') {
                    throw new InvalidArgumentException(
                        'O nome da permissão de e-mail não pode estar vazio.',
                    );
                }

                if (
                    mb_strlen(
                        $nomeNormalizado,
                    ) > self::COMPRIMENTO_MAXIMO_NOME
                ) {
                    throw new InvalidArgumentException(
                        sprintf(
                            'O nome da permissão de e-mail não pode exceder %d caracteres.',
                            self::COMPRIMENTO_MAXIMO_NOME,
                        ),
                    );
                }

                return $nomeNormalizado;
            },
        );
    }

    /**
     * Normaliza e valida a descrição da permissão.
     *
     * A descrição é persistida como texto de uma única linha. Os espaços
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
                        'A descrição da permissão de e-mail deve ser uma sequência de caracteres.',
                    );
                }

                self::validarTextoUtf8(
                    $valor,
                    'A descrição da permissão de e-mail contém texto inválido.',
                );

                self::validarAusenciaCaracteresControlo(
                    $valor,
                    'A descrição da permissão de e-mail contém caracteres inválidos.',
                );

                $descricaoNormalizada = Str::squish(
                    $valor,
                );

                if ($descricaoNormalizada === '') {
                    throw new InvalidArgumentException(
                        'A descrição da permissão de e-mail não pode estar vazia.',
                    );
                }

                if (
                    mb_strlen(
                        $descricaoNormalizada,
                    ) > self::COMPRIMENTO_MAXIMO_DESCRICAO
                ) {
                    throw new InvalidArgumentException(
                        sprintf(
                            'A descrição da permissão de e-mail não pode exceder %d caracteres.',
                            self::COMPRIMENTO_MAXIMO_DESCRICAO,
                        ),
                    );
                }

                return $descricaoNormalizada;
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
                    ! is_int($valor)
                    || $valor < self::ORDEM_MINIMA
                    || $valor > self::ORDEM_MAXIMA
                ) {
                    throw new InvalidArgumentException(
                        sprintf(
                            'A ordem da permissão deve estar entre %d e %d.',
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
     * Obtém os utilizadores associados à permissão.
     *
     * @return BelongsToMany<Utilizador, $this> Relação com os utilizadores.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function utilizadores(): BelongsToMany
    {
        return $this
            ->belongsToMany(
                Utilizador::class,
                self::TABELA_PERMISSAO_UTILIZADOR,
                'permissao_email_id',
                'utilizador_id',
            )
            ->orderBy(
                'utilizadores.nome',
            )
            ->orderBy(
                'utilizadores.id',
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
