<?php

namespace App\Traits;

use App\Models\Autenticacao\Utilizador;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * Gere automaticamente os atributos blameable.
 *
 * @since 1.0
 *
 * @version 1.0
 */
trait Blameable
{
    /**
     * Inicia as funcionalidades de blameable num modelo.
     *
     *
     * @since 1.0
     *
     * @version 1.0
     */
    public static function bootBlameable(): void
    {
        static::creating(function ($model) {
            if (Auth::check()) {
                $userId = Auth::id();
                if (is_null($model->created_by)) {
                    $model->created_by = $userId;
                }

                if (is_null($model->updated_by)) {
                    $model->updated_by = $userId;
                }
            }
        });

        static::updating(function ($model) {
            if (Auth::check()) {
                $model->updated_by = Auth::id();
            }
        });
    }

    /**
     * Obtém o utilizador que criou o registo.
     *
     * @return BelongsTo - Relação com a tabela users.
     *
     * @since 1.0
     *
     * @version 1.0
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(Utilizador::class, 'created_by');
    }

    /**
     * Obtém o utilizador que atualizou o registo pela última vez.
     *
     * @return BelongsTo - Relação com a tabela users.
     *
     * @since 1.0
     *
     * @version 1.0
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(Utilizador::class, 'updated_by');
    }
}
