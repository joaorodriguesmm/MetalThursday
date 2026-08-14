<?php

declare(strict_types=1);

namespace App\Models\Autenticacao;

use App\ObjetosValor\Utilizadores\EnderecoEmail;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Database\Factories\Autenticacao\ConviteFactory;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;
use SensitiveParameter;

/**
 * Representa um convite para registo na aplicação.
 *
 * O convite existe antes do respetivo utilizador. Quando é aceite, o
 * utilizador criado fica associado através de `utilizado_por_id`.
 *
 * O código original nunca é persistido. A base de dados guarda apenas o
 * respetivo hash SHA-256.
 *
 * @property int $id
 * @property string $nome_convidado
 * @property string|null $email_destino
 * @property string $codigo_hash
 * @property int|null $criado_por_id
 * @property int|null $utilizado_por_id
 * @property CarbonImmutable|null $expira_em
 * @property CarbonImmutable|null $utilizado_em
 * @property CarbonImmutable|null $revogado_em
 * @property int|null $revogado_por_id
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read Utilizador|null $criador
 * @property-read Utilizador|null $utilizador
 * @property-read Utilizador|null $responsavelRevogacao
 *
 * @since 2.0.0
 */
class Convite extends Model
{
    /** @use HasFactory<ConviteFactory> */
    use HasFactory;

    /**
     * Comprimento máximo do nome do convidado.
     *
     * @since 2.0.0
     */
    public const COMPRIMENTO_MAXIMO_NOME_CONVIDADO = 255;

    /**
     * Comprimento mínimo permitido para um código de convite.
     *
     * @since 2.0.0
     */
    public const COMPRIMENTO_MINIMO_CODIGO = 10;

    /**
     * Comprimento máximo permitido para um código de convite.
     *
     * @since 2.0.0
     */
    public const COMPRIMENTO_MAXIMO_CODIGO = 128;

    /**
     * Padrão utilizado nas rotas e pedidos para validar códigos.
     *
     * A constante não inclui delimitadores de expressão regular.
     *
     * @since 2.0.0
     */
    public const PADRAO_CODIGO = '[A-Za-z0-9_-]{10,128}';

    /**
     * Comprimento do hash hexadecimal SHA-256.
     *
     * @since 2.0.0
     */
    private const COMPRIMENTO_HASH_CODIGO = 64;

    /**
     * Algoritmo utilizado para calcular o hash dos códigos.
     *
     * Os códigos são gerados com entropia criptográfica e não correspondem a
     * palavras-passe escolhidas pelos utilizadores.
     *
     * @since 2.0.0
     */
    private const ALGORITMO_HASH = 'sha256';

    /**
     * Nome físico da tabela associada ao modelo.
     *
     * @var string
     *
     * @since 2.0.0
     */
    protected $table = 'convites';

    /**
     * Atributos permitidos em operações de atribuição em massa.
     *
     * O código, o criador e os campos que controlam a utilização ou revogação
     * devem ser alterados através das relações ou métodos próprios.
     *
     * @var list<string>
     *
     * @since 2.0.0
     */
    protected $fillable = [
        'nome_convidado',
        'email_destino',
        'expira_em',
    ];

    /**
     * Atributos omitidos das representações serializadas.
     *
     * @var list<string>
     *
     * @since 2.0.0
     */
    protected $hidden = [
        'codigo_hash',
    ];

    /**
     * Define as conversões automáticas dos atributos.
     *
     * @return array<string, string> Conversões dos atributos.
     *
     * @since 2.0.0
     */
    protected function casts(): array
    {
        return [
            'criado_por_id' => 'integer',

            'utilizado_por_id' => 'integer',

            'expira_em' => 'immutable_datetime',

            'utilizado_em' => 'immutable_datetime',

            'revogado_em' => 'immutable_datetime',

            'revogado_por_id' => 'integer',
        ];
    }

    /**
     * Cria a factory associada ao modelo.
     *
     * @return ConviteFactory Factory dos convites.
     *
     * @since 2.0.0
     */
    protected static function newFactory(): ConviteFactory
    {
        return ConviteFactory::new();
    }

    /**
     * Normaliza e valida o nome do convidado.
     *
     * Tabulações, quebras de linha e sequências de espaços Unicode são
     * convertidas num único espaço.
     *
     * @return Attribute<string, string> Atributo do nome.
     *
     * @throws InvalidArgumentException Quando o nome não é válido.
     *
     * @since 2.0.0
     */
    protected function nomeConvidado(): Attribute
    {
        return Attribute::make(
            set: static function (
                mixed $valor,
            ): string {
                if (! is_string($valor)) {
                    throw new InvalidArgumentException(
                        'O nome do convidado deve ser uma sequência de caracteres.',
                    );
                }

                if (
                    preg_match(
                        '//u',
                        $valor,
                    ) !== 1
                ) {
                    throw new InvalidArgumentException(
                        'O nome do convidado contém texto inválido.',
                    );
                }

                if (
                    preg_match(
                        '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/',
                        $valor,
                    ) === 1
                ) {
                    throw new InvalidArgumentException(
                        'O nome do convidado contém caracteres inválidos.',
                    );
                }

                $nomeNormalizado = preg_replace(
                    '/\s+/u',
                    ' ',
                    $valor,
                );

                if (! is_string($nomeNormalizado)) {
                    throw new InvalidArgumentException(
                        'Não foi possível normalizar o nome do convidado.',
                    );
                }

                $nomeNormalizado = trim(
                    $nomeNormalizado,
                );

                if ($nomeNormalizado === '') {
                    throw new InvalidArgumentException(
                        'O nome do convidado é obrigatório.',
                    );
                }

                if (
                    mb_strlen(
                        $nomeNormalizado,
                    ) > self::COMPRIMENTO_MAXIMO_NOME_CONVIDADO
                ) {
                    throw new InvalidArgumentException(
                        sprintf(
                            'O nome do convidado não pode ter mais de %d caracteres.',
                            self::COMPRIMENTO_MAXIMO_NOME_CONVIDADO,
                        ),
                    );
                }

                return $nomeNormalizado;
            },
        );
    }

    /**
     * Normaliza e valida o endereço de e-mail de destino.
     *
     * Um valor nulo ou composto apenas por espaços ASCII representa um convite
     * não limitado a um endereço específico. Caracteres de controlo não são
     * removidos silenciosamente antes da validação.
     *
     * @return Attribute<string|null, string|null> Atributo do endereço.
     *
     * @throws InvalidArgumentException Quando o endereço não é válido.
     *
     * @since 2.0.0
     */
    protected function emailDestino(): Attribute
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
                        'O endereço de destino deve ser uma sequência de caracteres.',
                    );
                }

                if (
                    trim(
                        $valor,
                        ' ',
                    ) === ''
                ) {
                    return null;
                }

                return EnderecoEmail::deTexto(
                    $valor,
                )->valor();
            },
        );
    }

    /**
     * Normaliza e valida o hash persistido do código.
     *
     * Espaços ASCII exteriores são removidos e as letras hexadecimais são
     * convertidas para minúsculas. Caracteres de controlo permanecem
     * inalterados para serem rejeitados pela validação.
     *
     * @return Attribute<string, string> Atributo do hash.
     *
     * @throws InvalidArgumentException Quando o hash não é hexadecimal
     *                                  SHA-256.
     *
     * @since 2.0.0
     */
    protected function codigoHash(): Attribute
    {
        return Attribute::make(
            set: static function (
                mixed $valor,
            ): string {
                if (! is_string($valor)) {
                    throw new InvalidArgumentException(
                        'O hash do código do convite não é válido.',
                    );
                }

                $hashNormalizado = strtolower(
                    trim(
                        $valor,
                        ' ',
                    ),
                );

                if (
                    strlen(
                        $hashNormalizado,
                    ) !== self::COMPRIMENTO_HASH_CODIGO
                    || preg_match(
                        '/\A[a-f0-9]{64}\z/',
                        $hashNormalizado,
                    ) !== 1
                ) {
                    throw new InvalidArgumentException(
                        'O hash do código do convite não é válido.',
                    );
                }

                return $hashNormalizado;
            },
        );
    }

    /**
     * Obtém o utilizador responsável pela criação do convite.
     *
     * @return BelongsTo<Utilizador, $this> Relação com o criador.
     *
     * @since 2.0.0
     */
    public function criador(): BelongsTo
    {
        return $this->belongsTo(
            Utilizador::class,
            'criado_por_id',
        );
    }

    /**
     * Obtém o utilizador criado através do convite.
     *
     * @return BelongsTo<Utilizador, $this> Relação com o utilizador.
     *
     * @since 2.0.0
     */
    public function utilizador(): BelongsTo
    {
        return $this->belongsTo(
            Utilizador::class,
            'utilizado_por_id',
        );
    }

    /**
     * Obtém o superadministrador responsável pela revogação.
     *
     * @return BelongsTo<Utilizador, $this> Relação com o responsável.
     *
     * @since 2.0.0
     */
    public function responsavelRevogacao(): BelongsTo
    {
        return $this->belongsTo(
            Utilizador::class,
            'revogado_por_id',
        );
    }

    /**
     * Limita a consulta aos convites ainda não utilizados nem revogados.
     *
     * Este escopo não verifica a expiração.
     *
     * @param  Builder<Convite>  $construtor  Consulta dos convites.
     * @return Builder<Convite> Consulta dos convites pendentes.
     *
     * @since 2.0.0
     */
    public function scopePendentes(
        Builder $construtor,
    ): Builder {
        return $construtor
            ->whereNull(
                'utilizado_por_id',
            )
            ->whereNull(
                'utilizado_em',
            )
            ->whereNull(
                'revogado_em',
            )
            ->whereNull(
                'revogado_por_id',
            );
    }

    /**
     * Limita a consulta aos convites atualmente disponíveis.
     *
     * Um convite está disponível quando não foi utilizado, não foi revogado
     * e ainda não expirou.
     *
     * @param  Builder<Convite>  $construtor  Consulta dos convites.
     * @return Builder<Convite> Consulta dos convites disponíveis.
     *
     * @since 2.0.0
     */
    public function scopeDisponiveis(
        Builder $construtor,
    ): Builder {
        $momentoAtual = CarbonImmutable::now();

        return $construtor
            ->whereNull(
                'utilizado_por_id',
            )
            ->whereNull(
                'utilizado_em',
            )
            ->whereNull(
                'revogado_em',
            )
            ->whereNull(
                'revogado_por_id',
            )
            ->where(
                static function (
                    Builder $construtorExpiracao,
                ) use (
                    $momentoAtual,
                ): void {
                    $construtorExpiracao
                        ->whereNull(
                            'expira_em',
                        )
                        ->orWhere(
                            'expira_em',
                            '>',
                            $momentoAtual,
                        );
                },
            );
    }

    /**
     * Limita a consulta ao convite correspondente ao código original.
     *
     * @param  Builder<Convite>  $construtor  Consulta dos convites.
     * @param  string  $codigo  Código original recebido.
     * @return Builder<Convite> Consulta limitada ao hash.
     *
     * @throws InvalidArgumentException Quando o código não é válido.
     *
     * @since 2.0.0
     */
    public function scopeComCodigo(
        Builder $construtor,
        #[SensitiveParameter]
        string $codigo,
    ): Builder {
        return $construtor->where(
            'codigo_hash',
            self::calcularHashCodigo(
                $codigo,
            ),
        );
    }

    /**
     * Define o código original do convite.
     *
     * Apenas o respetivo hash é colocado no modelo.
     *
     * @param  string  $codigo  Código original.
     *
     * @throws InvalidArgumentException Quando o código não é válido.
     *
     * @since 2.0.0
     */
    public function definirCodigo(
        #[SensitiveParameter]
        string $codigo,
    ): void {
        $this->codigo_hash = self::calcularHashCodigo(
            $codigo,
        );
    }

    /**
     * Determina se o código recebido corresponde ao convite.
     *
     * @param  string  $codigo  Código original recebido.
     * @return bool Verdadeiro quando os códigos correspondem.
     *
     * @throws InvalidArgumentException Quando o código não é válido.
     *
     * @since 2.0.0
     */
    public function correspondeAoCodigo(
        #[SensitiveParameter]
        string $codigo,
    ): bool {
        $hashPersistido = $this->codigo_hash;

        if (
            ! is_string($hashPersistido)
            || preg_match(
                '/\A[a-f0-9]{64}\z/',
                $hashPersistido,
            ) !== 1
        ) {
            return false;
        }

        return hash_equals(
            $hashPersistido,
            self::calcularHashCodigo(
                $codigo,
            ),
        );
    }

    /**
     * Determina se o convite já foi utilizado.
     *
     * A data de utilização continua a indicar um convite consumido mesmo que
     * o utilizador associado seja posteriormente eliminado.
     *
     * @return bool Verdadeiro quando o convite foi utilizado.
     *
     * @since 2.0.0
     */
    public function foiUtilizado(): bool
    {
        return $this->utilizado_por_id !== null
            || $this->utilizado_em !== null;
    }

    /**
     * Determina se o convite foi revogado.
     *
     * @return bool Verdadeiro quando o convite foi revogado.
     *
     * @since 2.0.0
     */
    public function foiRevogado(): bool
    {
        return $this->revogado_em !== null
            || $this->revogado_por_id !== null;
    }

    /**
     * Determina se o convite expirou.
     *
     * @param  CarbonInterface|null  $momento  Momento usado na comparação.
     * @return bool Verdadeiro quando o prazo terminou.
     *
     * @since 2.0.0
     */
    public function estaExpirado(
        ?CarbonInterface $momento = null,
    ): bool {
        if ($this->expira_em === null) {
            return false;
        }

        $momentoComparacao = $momento !== null
            ? CarbonImmutable::instance(
                $momento,
            )
            : CarbonImmutable::now();

        return $this->expira_em->lessThanOrEqualTo(
            $momentoComparacao,
        );
    }

    /**
     * Determina se o convite pode ser utilizado.
     *
     * @param  CarbonInterface|null  $momento  Momento usado na validação.
     * @return bool Verdadeiro quando o convite está disponível.
     *
     * @since 2.0.0
     */
    public function estaDisponivel(
        ?CarbonInterface $momento = null,
    ): bool {
        return ! $this->foiUtilizado()
            && ! $this->foiRevogado()
            && ! $this->estaExpirado(
                $momento,
            );
    }

    /**
     * Associa o convite ao utilizador que o aceitou.
     *
     * A persistência deve ser realizada pelo serviço responsável dentro de
     * uma transação com bloqueio do registo.
     *
     * @param  Utilizador  $utilizador  Utilizador criado através do convite.
     * @param  CarbonInterface|null  $momento  Momento da utilização.
     *
     * @throws DomainException Quando o convite não está disponível ou o
     *                         utilizador não está persistido.
     *
     * @since 2.0.0
     */
    public function utilizar(
        Utilizador $utilizador,
        ?CarbonInterface $momento = null,
    ): void {
        $momentoUtilizacao = $momento !== null
            ? CarbonImmutable::instance(
                $momento,
            )
            : CarbonImmutable::now();

        if (! $this->estaDisponivel($momentoUtilizacao)) {
            throw new DomainException(
                'O convite não está disponível para utilização.',
            );
        }

        $identificadorUtilizador =
            self::obterIdentificadorUtilizadorPersistido(
                $utilizador,
                'O utilizador associado ao convite ainda não foi persistido.',
            );

        $this->utilizado_por_id =
            $identificadorUtilizador;

        $this->utilizado_em =
            $momentoUtilizacao;

        $this->revogado_em =
            null;

        $this->revogado_por_id =
            null;

        $this->unsetRelation(
            'responsavelRevogacao',
        );

        $this->setRelation(
            'utilizador',
            $utilizador,
        );
    }

    /**
     * Revoga o convite.
     *
     * Um convite utilizado não pode ser posteriormente revogado. A revogação
     * repetida é idempotente e preserva o primeiro momento e o primeiro
     * responsável.
     *
     * A autorização administrativa do responsável é validada pelo serviço.
     * O modelo confirma apenas que o utilizador está persistido.
     *
     * @param  Utilizador  $responsavel  Responsável pela revogação.
     * @param  CarbonInterface|null  $momento  Momento da revogação.
     *
     * @throws DomainException Quando o convite já foi utilizado ou o
     *                         responsável não está persistido.
     *
     * @since 2.0.0
     */
    public function revogar(
        Utilizador $responsavel,
        ?CarbonInterface $momento = null,
    ): void {
        if ($this->foiUtilizado()) {
            throw new DomainException(
                'Não é possível revogar um convite já utilizado.',
            );
        }

        if ($this->foiRevogado()) {
            return;
        }

        self::obterIdentificadorUtilizadorPersistido(
            $responsavel,
            'O responsável pela revogação deve estar persistido.',
        );

        $this->revogado_em = $momento !== null
            ? CarbonImmutable::instance(
                $momento,
            )
            : CarbonImmutable::now();

        $this
            ->responsavelRevogacao()
            ->associate(
                $responsavel,
            );
    }

    /**
     * Normaliza e valida um código de convite.
     *
     * Os espaços ASCII exteriores são removidos, mas a capitalização é
     * preservada. Os códigos são sensíveis a maiúsculas e minúsculas.
     * Caracteres de controlo permanecem inalterados e são rejeitados pelo
     * padrão permitido.
     *
     * @param  string  $codigo  Código original.
     * @return string Código normalizado.
     *
     * @throws InvalidArgumentException Quando o código não é válido.
     *
     * @since 2.0.0
     */
    public static function normalizarCodigo(
        #[SensitiveParameter]
        string $codigo,
    ): string {
        $codigoNormalizado = trim(
            $codigo,
            ' ',
        );

        $comprimento = strlen(
            $codigoNormalizado,
        );

        if (
            $comprimento < self::COMPRIMENTO_MINIMO_CODIGO
            || $comprimento > self::COMPRIMENTO_MAXIMO_CODIGO
            || preg_match(
                '/\A'.self::PADRAO_CODIGO.'\z/',
                $codigoNormalizado,
            ) !== 1
        ) {
            throw new InvalidArgumentException(
                'O código do convite não é válido.',
            );
        }

        return $codigoNormalizado;
    }

    /**
     * Calcula o hash persistido para um código de convite.
     *
     * @param  string  $codigo  Código original.
     * @return string Hash hexadecimal SHA-256.
     *
     * @throws InvalidArgumentException Quando o código não é válido.
     *
     * @since 2.0.0
     */
    public static function calcularHashCodigo(
        #[SensitiveParameter]
        string $codigo,
    ): string {
        return hash(
            self::ALGORITMO_HASH,
            self::normalizarCodigo(
                $codigo,
            ),
        );
    }

    /**
     * Obtém o identificador de um utilizador persistido.
     *
     * @param  Utilizador  $utilizador  Utilizador recebido.
     * @param  string  $mensagem  Mensagem utilizada em caso de erro.
     * @return int Identificador válido.
     *
     * @throws DomainException Quando o utilizador não está persistido ou não
     *                         possui um identificador válido.
     *
     * @since 2.0.0
     */
    private static function obterIdentificadorUtilizadorPersistido(
        Utilizador $utilizador,
        string $mensagem,
    ): int {
        if (! $utilizador->exists) {
            throw new DomainException(
                $mensagem,
            );
        }

        $identificador =
            $utilizador->getKey();

        if (
            is_int($identificador)
            && $identificador > 0
        ) {
            return $identificador;
        }

        if (is_string($identificador)) {
            $identificadorNormalizado = trim(
                $identificador,
            );

            if (
                $identificadorNormalizado !== ''
                && ctype_digit(
                    $identificadorNormalizado,
                )
                && (int) $identificadorNormalizado > 0
            ) {
                return (int) $identificadorNormalizado;
            }
        }

        throw new DomainException(
            $mensagem,
        );
    }
}
