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
use LogicException;

/**
 * Adiciona suporte a avaliações polimórficas a um modelo Eloquent.
 *
 * Disponibiliza a relação com todas as avaliações e a pontuação atribuída pelo
 * utilizador autenticado através do guard `sessao`.
 *
 * @mixin Model
 *
 * @since 2.0.0
 *
 * @version 2.1.0
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
     * Quando não existe um utilizador autenticado e persistido, a relação
     * recebe uma condição impossível e não devolve qualquer registo.
     *
     * @return MorphOne<Avaliacao, $this> Relação com a avaliação.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    public function avaliacaoUtilizadorAutenticado(): MorphOne
    {
        $relacaoAvaliacao = $this->morphOne(
            Avaliacao::class,
            'avaliavel',
            'tipo_avaliavel',
            'avaliavel_id',
        );

        $identificadorUtilizador =
            $this->obterIdentificadorUtilizadorParaAvaliacoes();

        if ($identificadorUtilizador === null) {
            return $relacaoAvaliacao->whereRaw(
                '1 = 0',
            );
        }

        return $relacaoAvaliacao->where(
            'utilizador_id',
            $identificadorUtilizador,
        );
    }

    /**
     * Obtém a pontuação atribuída pelo utilizador autenticado.
     *
     * Quando não existe autenticação válida, é devolvida a pontuação zero.
     *
     * Para um utilizador autenticado, a relação
     * `avaliacaoUtilizadorAutenticado` deve ser carregada explicitamente pela
     * consulta responsável pela apresentação do modelo. O accessor nunca
     * executa consultas ocultas.
     *
     * @return Attribute<float, never> Pontuação atribuída pelo utilizador.
     *
     * @throws LogicException Quando existe autenticação válida, mas a relação
     *                        necessária não foi carregada.
     *
     * @since 2.0.0
     *
     * @version 2.1.0
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
                    ! $this->relationLoaded(
                        'avaliacaoUtilizadorAutenticado',
                    )
                ) {
                    throw new LogicException(
                        'A relação "avaliacaoUtilizadorAutenticado" deve estar carregada antes de obter a pontuação do utilizador autenticado.',
                    );
                }

                $avaliacao = $this->getRelation(
                    'avaliacaoUtilizadorAutenticado',
                );

                if (
                    ! $avaliacao instanceof Avaliacao
                    || $avaliacao->utilizador_id
                    !== $identificadorUtilizador
                ) {
                    return 0.0;
                }

                return $avaliacao->pontuacao;
            },
        );
    }

    /**
     * Obtém o identificador do utilizador autenticado para as avaliações.
     *
     * O método confirma que o objeto autenticado através do guard `sessao`
     * corresponde a um utilizador persistido e possui um identificador inteiro
     * positivo.
     *
     * O nome inclui a referência às avaliações para evitar colisões com
     * métodos privados declarados por outros traits de interações.
     *
     * @return int|null Identificador do utilizador ou nulo.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    private function obterIdentificadorUtilizadorParaAvaliacoes(): ?int
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

        $identificadorInteiro =
            (int) $identificadorNormalizado;

        return $identificadorInteiro > 0
            ? $identificadorInteiro
            : null;
    }
}
