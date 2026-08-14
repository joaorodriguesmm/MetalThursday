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
 * Quando existe um utilizador autenticado e persistido na guarda `sessao`, o
 * trait preenche os atributos `criado_por_id` e `atualizado_por_id`.
 *
 * Em factories, seeders, comandos Artisan ou outros contextos sem
 * autenticação, os valores definidos explicitamente são preservados e os
 * restantes permanecem nulos.
 *
 * @mixin Model
 *
 * @since 1.0.0
 */
trait RegistaAutoria
{
    /**
     * Regista um callback executado durante a criação do modelo.
     *
     * Este método é fornecido pelo sistema de eventos do Eloquent. A
     * declaração abstrata permite que o Intelephense e outros analisadores
     * estáticos reconheçam o contrato utilizado pelo trait.
     *
     * @param  mixed  $callback  Callback do evento.
     *
     * @since 2.0.0
     */
    abstract public static function creating($callback);

    /**
     * Regista um callback executado durante a atualização do modelo.
     *
     * Este método é fornecido pelo sistema de eventos do Eloquent. A
     * declaração abstrata permite que o Intelephense e outros analisadores
     * estáticos reconheçam o contrato utilizado pelo trait.
     *
     * @param  mixed  $callback  Callback do evento.
     *
     * @since 2.0.0
     */
    abstract public static function updating($callback);

    /**
     * Regista os eventos responsáveis pelo preenchimento da autoria.
     *
     * O nome deste método deve corresponder ao nome do trait para que o
     * Eloquent o execute automaticamente durante o arranque do modelo.
     *
     * @since 1.0.0
     */
    public static function bootRegistaAutoria(): void
    {
        static::creating(
            static function (
                Model $modelo,
            ): void {
                $identificadorUtilizador =
                    self::obterIdentificadorUtilizadorAutenticado();

                if ($identificadorUtilizador === null) {
                    return;
                }

                if (
                    $modelo->getAttribute(
                        'criado_por_id',
                    ) === null
                ) {
                    $modelo->setAttribute(
                        'criado_por_id',
                        $identificadorUtilizador,
                    );
                }

                if (
                    $modelo->getAttribute(
                        'atualizado_por_id',
                    ) === null
                ) {
                    $modelo->setAttribute(
                        'atualizado_por_id',
                        $identificadorUtilizador,
                    );
                }
            },
        );

        static::updating(
            static function (
                Model $modelo,
            ): void {
                $identificadorUtilizador =
                    self::obterIdentificadorUtilizadorAutenticado();

                if ($identificadorUtilizador === null) {
                    return;
                }

                $modelo->setAttribute(
                    'atualizado_por_id',
                    $identificadorUtilizador,
                );
            },
        );
    }

    /**
     * Obtém o utilizador responsável pela criação do registo.
     *
     * A relação pode ser nula quando o registo foi criado sem autenticação ou
     * quando o utilizador responsável foi posteriormente eliminado.
     *
     * @return BelongsTo<Utilizador, $this> Relação com o utilizador criador.
     *
     * @since 1.0.0
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
     */
    public function atualizadoPor(): BelongsTo
    {
        return $this->belongsTo(
            Utilizador::class,
            'atualizado_por_id',
        );
    }

    /**
     * Obtém o identificador do utilizador autenticado e persistido.
     *
     * Um objeto autenticado de outro tipo, um utilizador não persistido ou um
     * identificador ausente, inválido ou não positivo são tratados como
     * inexistência de autenticação aplicável à auditoria.
     *
     * @return int|null Identificador do utilizador ou nulo.
     *
     * @since 2.0.0
     */
    private static function obterIdentificadorUtilizadorAutenticado(): ?int
    {
        $utilizador = Auth::guard(
            'sessao',
        )->user();

        if (
            ! $utilizador instanceof Utilizador
            || ! $utilizador->exists
        ) {
            return null;
        }

        $identificador = $utilizador->getKey();

        if (
            is_int($identificador)
            && $identificador > 0
        ) {
            return $identificador;
        }

        if (! is_string($identificador)) {
            return null;
        }

        $identificadorNormalizado = trim(
            $identificador,
        );

        if (
            $identificadorNormalizado === ''
            || ! ctype_digit(
                $identificadorNormalizado,
            )
        ) {
            return null;
        }

        $identificadorInteiro = (int) $identificadorNormalizado;

        return $identificadorInteiro > 0
            ? $identificadorInteiro
            : null;
    }
}
