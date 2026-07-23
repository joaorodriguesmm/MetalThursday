<?php

declare(strict_types=1);

namespace App\Traits\Interacoes;

use App\Models\Interacoes\Audicao;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Facades\Auth;

/**
 * Adiciona suporte a audições polimórficas a um modelo Eloquent.
 *
 * @mixin Model
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
trait TemAudicoes
{
    /**
     * Obtém os registos de audição associados ao modelo.
     *
     * @return MorphMany<Audicao, $this> Relação com as audições.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function audicoes(): MorphMany
    {
        return $this->morphMany(
            Audicao::class,
            'audivel',
            'tipo_audivel',
            'audivel_id',
        );
    }

    /**
     * Obtém o registo de audição do utilizador autenticado.
     *
     * A restrição única da tabela `audicoes` garante que existe, no máximo,
     * um registo do mesmo utilizador para a mesma entidade.
     *
     * @return MorphOne<Audicao, $this> Relação com a audição.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function audicaoUtilizadorAutenticado(): MorphOne
    {
        return $this
            ->morphOne(
                Audicao::class,
                'audivel',
                'tipo_audivel',
                'audivel_id',
            )
            ->where(
                'utilizador_id',
                Auth::id(),
            );
    }

    /**
     * Determina se o utilizador autenticado ouviu a entidade.
     *
     * @return Attribute<bool, never> Estado da audição do utilizador.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    protected function ouvidoPeloUtilizadorAutenticado(): Attribute
    {
        return Attribute::get(
            function (): bool {
                if (! Auth::check()) {
                    return false;
                }

                if (
                    $this->relationLoaded(
                        'audicaoUtilizadorAutenticado',
                    )
                ) {
                    return $this->getRelation(
                        'audicaoUtilizadorAutenticado',
                    ) !== null;
                }

                return $this
                    ->audicaoUtilizadorAutenticado()
                    ->exists();
            },
        );
    }
}
