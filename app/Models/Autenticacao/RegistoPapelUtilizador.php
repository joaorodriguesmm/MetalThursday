<?php

declare(strict_types=1);

namespace App\Models\Autenticacao;

use App\Enumeracoes\PapelUtilizador;
use Carbon\CarbonImmutable;
use Database\Factories\Autenticacao\RegistoPapelUtilizadorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Representa uma alteração imutável do papel de um utilizador.
 *
 * Cada registo conserva o utilizador afetado, o papel anterior, o novo papel,
 * o superadministrador responsável e o momento da alteração.
 *
 * @property int $id
 * @property int $utilizador_id
 * @property PapelUtilizador $papel_anterior
 * @property PapelUtilizador $papel_novo
 * @property int $responsavel_id
 * @property CarbonImmutable $registado_em
 * @property-read Utilizador $utilizador
 * @property-read Utilizador $responsavel
 *
 * @since 2.0.0
 */
class RegistoPapelUtilizador extends Model
{
    /** @use HasFactory<RegistoPapelUtilizadorFactory> */
    use HasFactory;

    /**
     * Nome físico da tabela associada ao modelo.
     *
     * @var string
     *
     * @since 2.0.0
     */
    protected $table =
        'registos_papel_utilizadores';

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
     * Os registos devem ser construídos explicitamente pelo serviço dos
     * papéis dos utilizadores.
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

            'papel_anterior' => PapelUtilizador::class,

            'papel_novo' => PapelUtilizador::class,

            'responsavel_id' => 'integer',

            'registado_em' => 'immutable_datetime',
        ];
    }

    /**
     * Cria a factory associada ao modelo.
     *
     * @return RegistoPapelUtilizadorFactory Factory dos registos.
     *
     * @since 2.0.0
     */
    protected static function newFactory(): RegistoPapelUtilizadorFactory
    {
        return RegistoPapelUtilizadorFactory::new();
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
                    'Os registos dos papéis dos utilizadores não podem ser alterados.',
                );
            },
        );

        static::deleting(
            static function (): never {
                throw new LogicException(
                    'Os registos dos papéis dos utilizadores não podem ser eliminados.',
                );
            },
        );
    }

    /**
     * Obtém o utilizador afetado pela alteração do papel.
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
     * Obtém o utilizador responsável pela alteração do papel.
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
}
