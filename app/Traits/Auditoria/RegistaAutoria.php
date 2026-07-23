<?php

declare(strict_types=1);

namespace App\Traits\Auditoria;

use App\Models\Autenticacao\Utilizador;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * Regista automaticamente a autoria da criação e atualização dos modelos.
 *
 * Quando existe um utilizador autenticado, o trait preenche os atributos
 * `criado_por_id` e `atualizado_por_id`. Em execuções sem autenticação,
 * como factories, seeders e comandos Artisan, conserva os valores definidos
 * explicitamente ou mantém os atributos nulos.
 *
 * @mixin Model
 *
 * @since 1.0.0
 *
 * @version 2.0.0
 */
trait RegistaAutoria
{
    /**
     * Regista um callback executado durante a criação do modelo.
     *
     * Este contrato é fornecido pelo modelo Eloquent através dos respetivos
     * mecanismos internos de eventos.
     *
     * @param  mixed  $callback  Callback do evento.
     * @return void
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    abstract public static function creating($callback);

    /**
     * Regista um callback executado durante a atualização do modelo.
     *
     * Este contrato é fornecido pelo modelo Eloquent através dos respetivos
     * mecanismos internos de eventos.
     *
     * @param  mixed  $callback  Callback do evento.
     * @return void
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    abstract public static function updating($callback);

    /**
     * Inicia os eventos responsáveis pelo registo da autoria.
     *
     * O nome deste método deve corresponder ao nome do trait para que o
     * Eloquent o execute automaticamente durante o arranque do modelo.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public static function bootRegistaAutoria(): void
    {
        static::creating(
            static function (
                Model $modelo,
            ): void {
                $utilizadorId = Auth::id();

                if ($utilizadorId === null) {
                    return;
                }

                if (
                    $modelo->getAttribute(
                        'criado_por_id',
                    ) === null
                ) {
                    $modelo->setAttribute(
                        'criado_por_id',
                        (int) $utilizadorId,
                    );
                }

                if (
                    $modelo->getAttribute(
                        'atualizado_por_id',
                    ) === null
                ) {
                    $modelo->setAttribute(
                        'atualizado_por_id',
                        (int) $utilizadorId,
                    );
                }
            },
        );

        static::updating(
            static function (
                Model $modelo,
            ): void {
                $utilizadorId = Auth::id();

                if ($utilizadorId === null) {
                    return;
                }

                $modelo->setAttribute(
                    'atualizado_por_id',
                    (int) $utilizadorId,
                );
            },
        );
    }

    /**
     * Obtém o utilizador responsável pela criação do registo.
     *
     * A relação pode ser nula quando o registo foi criado sem autenticação
     * ou quando o respetivo utilizador foi eliminado.
     *
     * @return BelongsTo<Utilizador, $this> Relação com o utilizador criador.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function criadoPor(): BelongsTo
    {
        return $this->belongsTo(
            Utilizador::class,
            'criado_por_id',
        );
    }

    /**
     * Obtém o utilizador responsável pela última atualização do registo.
     *
     * A relação pode ser nula quando o registo nunca foi atualizado por um
     * utilizador autenticado ou quando esse utilizador foi eliminado.
     *
     * @return BelongsTo<Utilizador, $this> Relação com o último utilizador
     *                                      responsável pela atualização.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function atualizadoPor(): BelongsTo
    {
        return $this->belongsTo(
            Utilizador::class,
            'atualizado_por_id',
        );
    }
}
