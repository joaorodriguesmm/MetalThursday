<?php

declare(strict_types=1);

namespace App\Traits\Interacoes;

use App\Models\Autenticacao\Utilizador;
use App\Models\Interacoes\Avaliacao;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Facades\Auth;

/**
 * Adiciona suporte a avaliações polimórficas a um modelo Eloquent.
 *
 * Disponibiliza a relação com todas as avaliações e a pontuação atribuída pelo
 * utilizador atualmente autenticado.
 *
 * @mixin Model
 *
 * @since 2.0.0
 *
 * @version 1.1.0
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
     * Quando não existe um utilizador autenticado válido, a relação recebe uma
     * condição impossível e nunca devolve um registo.
     *
     * @return MorphOne<Avaliacao, $this> Relação com a avaliação.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    public function avaliacaoUtilizadorAutenticado(): MorphOne
    {
        $relacao =
            $this->morphOne(
                Avaliacao::class,
                'avaliavel',
                'tipo_avaliavel',
                'avaliavel_id',
            );

        $identificadorUtilizador =
            $this->obterIdentificadorUtilizadorParaAvaliacoes();

        if ($identificadorUtilizador === null) {
            return $relacao->whereRaw(
                '0 = 1',
            );
        }

        return $relacao->where(
            'utilizador_id',
            $identificadorUtilizador,
        );
    }

    /**
     * Obtém a pontuação atribuída pelo utilizador autenticado.
     *
     * Quando não existe um utilizador autenticado ou uma avaliação associada,
     * é devolvida a pontuação zero.
     *
     * Quando a relação já está carregada, o valor é obtido sem executar uma
     * nova consulta.
     *
     * @return Attribute<float, never> Pontuação atribuída pelo utilizador.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    protected function pontuacaoUtilizadorAutenticado(): Attribute
    {
        return Attribute::get(
            function (): float {
                $identificadorUtilizador =
                    $this->obterIdentificadorUtilizadorParaAvaliacoes();

                if ($identificadorUtilizador === null) {
                    return 0.0;
                }

                if (
                    $this->relationLoaded(
                        'avaliacaoUtilizadorAutenticado',
                    )
                ) {
                    $avaliacao =
                        $this->getRelation(
                            'avaliacaoUtilizadorAutenticado',
                        );
                } else {
                    $avaliacao =
                        $this
                            ->avaliacaoUtilizadorAutenticado()
                            ->first();
                }

                if (
                    ! $avaliacao instanceof Avaliacao
                    || (int) $avaliacao->utilizador_id
                    !== $identificadorUtilizador
                    || ! is_numeric(
                        $avaliacao->pontuacao,
                    )
                ) {
                    return 0.0;
                }

                return (float) $avaliacao->pontuacao;
            },
        );
    }

    /**
     * Obtém o identificador do utilizador autenticado para as avaliações.
     *
     * O método confirma que o objeto autenticado corresponde ao modelo
     * Utilizador, está persistido e possui um identificador inteiro positivo.
     *
     * O nome inclui a referência às avaliações para evitar colisões com
     * métodos privados declarados por outros traits de interações.
     *
     * @return int|null Identificador do utilizador ou nulo.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function obterIdentificadorUtilizadorParaAvaliacoes(): ?int
    {
        $utilizador =
            Auth::user();

        if (
            ! $utilizador instanceof Utilizador
            || ! $utilizador->exists
        ) {
            return null;
        }

        $identificador =
            $utilizador->getKey();

        if (
            is_int($identificador)
            && $identificador > 0
        ) {
            return $identificador;
        }

        if (is_string($identificador)) {
            $identificadorNormalizado =
                trim(
                    $identificador,
                );

            if (
                $identificadorNormalizado !== ''
                && ctype_digit(
                    $identificadorNormalizado,
                )
                && (int) $identificadorNormalizado > 0
            ) {
                return (int) $identificadorNormalizado;
            }
        }

        return null;
    }
}
