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
 * @version 2.1.0
 */
class Convite extends Model
{
    /** @use HasFactory<ConviteFactory> */
    use HasFactory;

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
     * @version 1.0.0
     */
    public function scopePendentes(
        Builder $consulta,
    ): Builder {
        return $consulta
            ->whereNull('utilizado_em')
            ->whereNull('revogado_em');
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
     * @version 1.0.0
     */
    public function scopeDisponiveis(
        Builder $consulta,
    ): Builder {
        return $consulta
            ->whereNull('utilizado_em')
            ->whereNull('revogado_em')
            ->where(
                function (
                    Builder $consultaExpiracao,
                ): void {
                    $consultaExpiracao
                        ->whereNull('expira_em')
                        ->orWhere(
                            'expira_em',
                            '>',
                            now(),
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
     * @throws InvalidArgumentException Quando o código está vazio.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function scopeComCodigo(
        Builder $consulta,
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
     * @throws InvalidArgumentException Quando o código está vazio.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function definirCodigo(
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
     * @throws InvalidArgumentException Quando o código está vazio.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function correspondeAoCodigo(
        string $codigo,
    ): bool {
        if (
            ! isset($this->codigo_hash)
            || $this->codigo_hash === ''
        ) {
            return false;
        }

        return hash_equals(
            $this->codigo_hash,
            self::calcularHashCodigo(
                $codigo,
            ),
        );
    }

    /**
     * Determina se o convite já foi utilizado.
     *
     * @return bool Verdadeiro quando o convite já foi utilizado.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function foiUtilizado(): bool
    {
        return $this->utilizado_em !== null;
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
     * @version 1.0.0
     */
    public function estaExpirado(
        ?CarbonInterface $momento = null,
    ): bool {
        if ($this->expira_em === null) {
            return false;
        }

        return $this->expira_em->lessThanOrEqualTo(
            $momento ?? now(),
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
     * @version 2.1.0
     */
    public function utilizar(
        Utilizador $utilizador,
        ?CarbonInterface $momento = null,
    ): void {
        $momentoUtilizacao =
            $momento ?? now();

        if (
            ! $this->estaDisponivel(
                $momentoUtilizacao,
            )
        ) {
            throw new DomainException(
                'O convite não está disponível para utilização.',
            );
        }

        if (
            ! $utilizador->exists
            || $utilizador->getKey() === null
        ) {
            throw new DomainException(
                'O utilizador associado ao convite ainda não foi persistido.',
            );
        }

        $this->utilizado_por_id =
            (int) $utilizador->getKey();

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
     * @version 1.0.0
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
            $momento ?? now();
    }

    /**
     * Calcula o hash persistido para um código de convite.
     *
     * Os espaços no início e no fim são removidos, mas a capitalização é
     * preservada. Assim, os códigos permanecem sensíveis a maiúsculas e
     * minúsculas.
     *
     * @param  string  $codigo  Código original do convite.
     * @return string Hash hexadecimal do código.
     *
     * @throws InvalidArgumentException Quando o código está vazio.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public static function calcularHashCodigo(
        string $codigo,
    ): string {
        $codigoNormalizado =
            trim($codigo);

        if ($codigoNormalizado === '') {
            throw new InvalidArgumentException(
                'O código do convite não pode estar vazio.',
            );
        }

        return hash(
            self::ALGORITMO_HASH,
            $codigoNormalizado,
        );
    }
}
