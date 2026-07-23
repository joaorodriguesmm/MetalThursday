<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\Autenticacao\Utilizador;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * Gere automaticamente os dados de auditoria dos modelos.
 *
 * O trait preenche o utilizador responsável pela criação e pela última
 * atualização quando existe um utilizador autenticado. Em execuções sem
 * autenticação, como factories, seeders e comandos Artisan, os atributos
 * permanecem nulos ou conservam os valores definidos explicitamente.
 *
 * @since 1.0.0
 *
 * @version 2.0.0
 */
trait Blameable
{
    /**
     * Inicia os eventos responsáveis pela auditoria do modelo.
     *
     * Durante a criação, os dois identificadores são preenchidos quando ainda
     * não tiverem um valor. Durante a atualização, o utilizador autenticado
     * passa a ser o responsável pela última alteração.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public static function bootBlameable(): void
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
