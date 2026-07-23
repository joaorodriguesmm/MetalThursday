<?php

declare(strict_types=1);

namespace App\Traits\Interacoes;

use App\Models\Interacoes\Avaliacao;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Facades\Auth;

/**
 * Adiciona suporte a avaliações polimórficas a um modelo Eloquent.
 *
 * @mixin Model
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
trait TemAvaliacoes
{
    /**
     * Obtém as avaliações associadas ao modelo.
     *
     * @return MorphMany<Avaliacao, $this> Relação com as avaliações.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function avaliacoes(): MorphMany
    {
        return $this->morphMany(
            Avaliacao::class,
            'avaliavel',
            'tipo_avaliavel',
            'avaliavel_id',
        );
    }

    /**
     * Obtém a avaliação atribuída pelo utilizador autenticado.
     *
     * A restrição única da tabela `avaliacoes` garante que existe, no máximo,
     * uma avaliação do mesmo utilizador para a mesma entidade.
     *
     * @return MorphOne<Avaliacao, $this> Relação com a avaliação.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function avaliacaoUtilizadorAutenticado(): MorphOne
    {
        return $this
            ->morphOne(
                Avaliacao::class,
                'avaliavel',
                'tipo_avaliavel',
                'avaliavel_id',
            )
            ->where(
                'utilizador_id',
                Auth::id(),
            );
    }

    /**
     * Obtém a pontuação atribuída pelo utilizador autenticado.
     *
     * Quando não existe um utilizador autenticado ou uma avaliação, é
     * devolvida a pontuação zero.
     *
     * @return Attribute<float, never> Pontuação atribuída pelo utilizador.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    protected function pontuacaoUtilizadorAutenticado(): Attribute
    {
        return Attribute::get(
            function (): float {
                if (! Auth::check()) {
                    return 0.0;
                }

                if (
                    $this->relationLoaded(
                        'avaliacaoUtilizadorAutenticado',
                    )
                ) {
                    $avaliacao = $this->getRelation(
                        'avaliacaoUtilizadorAutenticado',
                    );
                } else {
                    $avaliacao = $this
                        ->avaliacaoUtilizadorAutenticado()
                        ->first();
                }

                return (float) (
                    $avaliacao?->pontuacao
                    ?? 0
                );
            },
        );
    }
}
