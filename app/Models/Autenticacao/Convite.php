<?php

declare(strict_types=1);

namespace App\Models\Autenticacao;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Database\Factories\Autenticacao\ConviteFactory;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;
use SensitiveParameter;

/**
 * Representa um convite para registo na aplicação.
 *
 * O convite existe independentemente de um utilizador. Quando é aceite,
 * o utilizador criado fica associado através da coluna
 * `utilizado_por_id`.
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
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read Utilizador|null $criador
 * @property-read Utilizador|null $utilizador
 *
 * @since 2.0.0
 *
 * @version 2.2.0
 */
class Convite extends Model
{
    /** @use HasFactory<ConviteFactory> */
    use HasFactory;

    /**
     * Comprimento mínimo permitido para um código de convite.
     *
     * @var int
     *
     * @since 2.2.0
     *
     * @version 1.0.0
     */
    public const COMPRIMENTO_MINIMO_CODIGO = 10;

    /**
     * Comprimento máximo permitido para um código de convite.
     *
     * @var int
     *
     * @since 2.2.0
     *
     * @version 1.0.0
     */
    public const COMPRIMENTO_MAXIMO_CODIGO = 128;

    /**
     * Padrão utilizado nas rotas para validar códigos de convite.
     *
     * A constante não inclui delimitadores de expressão regular para poder
     * ser utilizada diretamente pelo método `where` das rotas.
     *
     * @var string
     *
     * @since 2.2.0
     *
     * @version 1.0.0
     */
    public const PADRAO_CODIGO =
        '[A-Za-z0-9_-]{10,128}';

    /**
     * Algoritmo utilizado para calcular o hash dos códigos.
     *
     * Os códigos devem ser gerados com entropia suficiente para que um hash
     * rápido seja adequado. Não se trata de uma palavra-passe escolhida pelo
     * utilizador.
     *
     * @var string
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const ALGORITMO_HASH = 'sha256';

    /**
     * Nome físico da tabela associada ao modelo.
     *
     * @var string
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    protected $table = 'convites';

    /**
     * Atributos permitidos em operações de atribuição em massa.
     *
     * Os campos de segurança e controlo do estado não são atribuíveis em
     * massa. Devem ser alterados através dos métodos próprios do modelo.
     *
     * @var array<int, string>
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    protected $fillable = [
        'nome_convidado',
        'email_destino',
        'expira_em',
    ];

    /**
     * Atributos omitidos das representações serializadas.
     *
     * Embora o hash não permita recuperar diretamente o código original,
     * não existe motivo para o expor através de respostas JSON.
     *
     * @var array<int, string>
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    protected $hidden = [
        'codigo_hash',
    ];

    /**
     * Define as conversões aplicadas aos atributos do modelo.
     *
     * As datas são convertidas em objetos imutáveis para impedir alterações
     * acidentais ao estado temporal do convite.
     *
     * @return array<string, string> Conversões dos atributos.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    protected function casts(): array
    {
        return [
            'criado_por_id' => 'integer',

            'utilizado_por_id' => 'integer',

            'expira_em' => 'immutable_datetime',

            'utilizado_em' => 'immutable_datetime',

            'revogado_em' => 'immutable_datetime',
        ];
    }

    /**
     * Cria a factory associada ao modelo.
     *
     * @return ConviteFactory Factory dos convites.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    protected static function newFactory(): ConviteFactory
    {
        return ConviteFactory::new();
    }

    /**
     * Obtém o utilizador responsável pela criação do convite.
     *
     * @return BelongsTo<Utilizador, $this> Relação com o criador.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    public function criador(): BelongsTo
    {
        return $this->belongsTo(
            Utilizador::class,
            'criado_por_id',
        );
    }

    /**
     * Obtém o utilizador criado ou associado através do convite.
     *
     * @return BelongsTo<Utilizador, $this> Relação com o utilizador que
     *                                      aceitou o convite.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    public function utilizador(): BelongsTo
    {
        return $this->belongsTo(
            Utilizador::class,
            'utilizado_por_id',
        );
    }

    /**
     * Limita a consulta aos convites ainda não utilizados nem revogados.
     *
     * Este escopo não verifica a expiração. Para obter convites que possam
     * ser usados neste momento deve utilizar-se o escopo `disponiveis`.
     *
     * @param  Builder<Convite>  $consulta  Consulta dos convites.
     * @return Builder<Convite> Consulta dos convites pendentes.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    public function scopePendentes(
        Builder $consulta,
    ): Builder {
        return $consulta
            ->whereNull(
                'utilizado_por_id',
            )
            ->whereNull(
                'utilizado_em',
            )
            ->whereNull(
                'revogado_em',
            );
    }

    /**
     * Limita a consulta aos convites atualmente disponíveis.
     *
     * Um convite está disponível quando não foi utilizado, não foi revogado
     * e não possui uma data de expiração já ultrapassada.
     *
     * @param  Builder<Convite>  $consulta  Consulta dos convites.
     * @return Builder<Convite> Consulta dos convites disponíveis.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    public function scopeDisponiveis(
        Builder $consulta,
    ): Builder {
        $momentoAtual =
            CarbonImmutable::now();

        return $consulta
            ->whereNull(
                'utilizado_por_id',
            )
            ->whereNull(
                'utilizado_em',
            )
            ->whereNull(
                'revogado_em',
            )
            ->where(
                static function (
                    Builder $consultaExpiracao,
                ) use (
                    $momentoAtual,
                ): void {
                    $consultaExpiracao
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
     * Limita a consulta ao convite correspondente ao código recebido.
     *
     * @param  Builder<Convite>  $consulta  Consulta dos convites.
     * @param  string  $codigo  Código original recebido.
     * @return Builder<Convite> Consulta limitada ao hash do código.
     *
     * @throws InvalidArgumentException Quando o código não é válido.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    public function scopeComCodigo(
        Builder $consulta,
        #[SensitiveParameter]
        string $codigo,
    ): Builder {
        return $consulta->where(
            'codigo_hash',
            self::calcularHashCodigo(
                $codigo,
            ),
        );
    }

    /**
     * Define o código do convite.
     *
     * Apenas o hash é colocado no modelo. O código original deve ser entregue
     * ao criador do convite pelo serviço responsável e descartado depois
     * dessa operação.
     *
     * @param  string  $codigo  Código original do convite.
     *
     * @throws InvalidArgumentException Quando o código não é válido.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    public function definirCodigo(
        #[SensitiveParameter]
        string $codigo,
    ): void {
        $this->codigo_hash =
            self::calcularHashCodigo(
                $codigo,
            );
    }

    /**
     * Determina se o código recebido corresponde ao convite.
     *
     * A comparação utiliza `hash_equals` para evitar diferenças temporais
     * dependentes da posição do primeiro caráter divergente.
     *
     * @param  string  $codigo  Código original recebido.
     * @return bool Verdadeiro quando o código corresponde ao convite.
     *
     * @throws InvalidArgumentException Quando o código não é válido.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    public function correspondeAoCodigo(
        #[SensitiveParameter]
        string $codigo,
    ): bool {
        $hashPersistido =
            $this->codigo_hash;

        if (
            ! is_string($hashPersistido)
            || $hashPersistido === ''
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
     * Um convite é considerado utilizado quando possui o utilizador ou o
     * momento de utilização preenchido. Desta forma, um estado parcialmente
     * persistido não volta a ser considerado disponível.
     *
     * @return bool Verdadeiro quando o convite já foi utilizado.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
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
     *
     * @version 1.0.0
     */
    public function foiRevogado(): bool
    {
        return $this->revogado_em !== null;
    }

    /**
     * Determina se o convite expirou.
     *
     * @param  CarbonInterface|null  $momento  Momento usado na comparação.
     * @return bool Verdadeiro quando o prazo já terminou.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    public function estaExpirado(
        ?CarbonInterface $momento = null,
    ): bool {
        if ($this->expira_em === null) {
            return false;
        }

        $momentoComparacao =
            $momento !== null
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
     *
     * @version 1.0.0
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
     * O método apenas altera o estado do modelo. A persistência deve ser
     * realizada pelo serviço dentro de uma transação e com bloqueio do
     * registo para impedir a utilização simultânea do mesmo convite.
     *
     * @param  Utilizador  $utilizador  Utilizador criado através do convite.
     * @param  CarbonInterface|null  $momento  Momento da utilização.
     *
     * @throws DomainException Quando o convite não está disponível ou quando
     *                         o utilizador ainda não foi persistido.
     *
     * @since 2.0.0
     *
     * @version 2.2.0
     */
    public function utilizar(
        Utilizador $utilizador,
        ?CarbonInterface $momento = null,
    ): void {
        $momentoUtilizacao =
            $momento !== null
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
            $utilizador->getKey();

        if (
            ! $utilizador->exists
            || ! is_numeric($identificadorUtilizador)
            || (int) $identificadorUtilizador < 1
        ) {
            throw new DomainException(
                'O utilizador associado ao convite ainda não foi persistido.',
            );
        }

        $this->utilizado_por_id =
            (int) $identificadorUtilizador;

        $this->utilizado_em =
            $momentoUtilizacao;

        $this->setRelation(
            'utilizador',
            $utilizador,
        );
    }

    /**
     * Revoga o convite.
     *
     * Um convite utilizado já produziu efeitos e não pode ser posteriormente
     * revogado. Revogar novamente um convite já revogado não altera o estado.
     *
     * @param  CarbonInterface|null  $momento  Momento da revogação.
     *
     * @throws DomainException Quando o convite já foi utilizado.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    public function revogar(
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

        $this->revogado_em =
            $momento !== null
            ? CarbonImmutable::instance(
                $momento,
            )
            : CarbonImmutable::now();
    }

    /**
     * Normaliza e valida um código de convite.
     *
     * Os espaços exteriores são removidos, mas a capitalização é preservada.
     * Os códigos permanecem sensíveis a maiúsculas e minúsculas.
     *
     * @param  string  $codigo  Código original do convite.
     * @return string Código normalizado.
     *
     * @throws InvalidArgumentException Quando o código não é válido.
     *
     * @since 2.2.0
     *
     * @version 1.0.0
     */
    public static function normalizarCodigo(
        #[SensitiveParameter]
        string $codigo,
    ): string {
        $codigoNormalizado =
            trim(
                $codigo,
            );

        $comprimento =
            strlen(
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
     * @param  string  $codigo  Código original do convite.
     * @return string Hash hexadecimal do código.
     *
     * @throws InvalidArgumentException Quando o código não é válido.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
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
}
