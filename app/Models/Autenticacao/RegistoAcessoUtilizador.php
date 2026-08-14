<?php

declare(strict_types=1);

namespace App\Models\Autenticacao;

use App\Enumeracoes\AcaoAcessoUtilizador;
use App\ObjetosValor\Utilizadores\MotivoSuspensaoUtilizador;
use Carbon\CarbonImmutable;
use Database\Factories\Autenticacao\RegistoAcessoUtilizadorFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;
use LogicException;

/**
 * Representa uma alteração imutável do acesso de um utilizador.
 *
 * Cada registo identifica o utilizador afetado, a ação executada, o
 * superadministrador responsável, o momento da alteração e, nas suspensões,
 * o respetivo motivo.
 *
 * @property int $id
 * @property int $utilizador_id
 * @property AcaoAcessoUtilizador $acao
 * @property string|null $motivo
 * @property int $responsavel_id
 * @property CarbonImmutable $registado_em
 * @property-read Utilizador $utilizador
 * @property-read Utilizador $responsavel
 *
 * @since 2.0.0
 */
class RegistoAcessoUtilizador extends Model
{
    /** @use HasFactory<RegistoAcessoUtilizadorFactory> */
    use HasFactory;

    /**
     * Nome físico da tabela associada ao modelo.
     *
     * @var string
     *
     * @since 2.0.0
     */
    protected $table =
        'registos_acesso_utilizadores';

    /**
     * Desativa as datas automáticas do Eloquent.
     *
     * O histórico possui apenas `registado_em`, porque nunca é atualizado.
     *
     * @var bool
     *
     * @since 2.0.0
     */
    public $timestamps = false;

    /**
     * Impede a atribuição em massa dos campos de auditoria.
     *
     * Os registos devem ser construídos explicitamente pelo serviço de
     * acesso dos utilizadores.
     *
     * @var list<string>
     *
     * @since 2.0.0
     */
    protected $fillable = [];

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
            'utilizador_id' => 'integer',

            'acao' => AcaoAcessoUtilizador::class,

            'responsavel_id' => 'integer',

            'registado_em' => 'immutable_datetime',
        ];
    }

    /**
     * Cria a factory associada ao modelo.
     *
     * @return RegistoAcessoUtilizadorFactory Factory dos registos.
     *
     * @since 2.0.0
     */
    protected static function newFactory(): RegistoAcessoUtilizadorFactory
    {
        return RegistoAcessoUtilizadorFactory::new();
    }

    /**
     * Regista as proteções de imutabilidade do histórico.
     *
     * O nome permanece em inglês por corresponder ao ponto de extensão do
     * ciclo de vida dos modelos Eloquent.
     *
     * @since 2.0.0
     */
    protected static function booted(): void
    {
        static::updating(
            static function (): never {
                throw new LogicException(
                    'Os registos de acesso dos utilizadores não podem ser alterados.',
                );
            },
        );

        static::deleting(
            static function (): never {
                throw new LogicException(
                    'Os registos de acesso dos utilizadores não podem ser eliminados.',
                );
            },
        );
    }

    /**
     * Normaliza e valida o motivo de uma suspensão.
     *
     * @return Attribute<string|null, string|null> Atributo do motivo.
     *
     * @throws InvalidArgumentException Quando o motivo não é textual ou não é
     *                                  válido.
     *
     * @since 2.0.0
     */
    protected function motivo(): Attribute
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
                        'O motivo da suspensão deve ser uma sequência de caracteres.',
                    );
                }

                return MotivoSuspensaoUtilizador::deTexto(
                    $valor,
                )->valor();
            },
        );
    }

    /**
     * Obtém o utilizador afetado pela alteração de acesso.
     *
     * @return BelongsTo<Utilizador, $this> Relação com o utilizador afetado.
     *
     * @since 2.0.0
     */
    public function utilizador(): BelongsTo
    {
        return $this->belongsTo(
            Utilizador::class,
            'utilizador_id',
        );
    }

    /**
     * Obtém o utilizador responsável pela alteração de acesso.
     *
     * @return BelongsTo<Utilizador, $this> Relação com o responsável.
     *
     * @since 2.0.0
     */
    public function responsavel(): BelongsTo
    {
        return $this->belongsTo(
            Utilizador::class,
            'responsavel_id',
        );
    }

    /**
     * Determina se o registo representa uma suspensão.
     *
     * @return bool Verdadeiro apenas para uma suspensão.
     *
     * @since 2.0.0
     */
    public function eSuspensao(): bool
    {
        return $this
            ->acao
            ->eSuspensao();
    }

    /**
     * Determina se o registo representa uma reativação.
     *
     * @return bool Verdadeiro apenas para uma reativação.
     *
     * @since 2.0.0
     */
    public function eReativacao(): bool
    {
        return $this
            ->acao
            ->eReativacao();
    }
}
