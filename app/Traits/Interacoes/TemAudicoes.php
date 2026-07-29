<?php

declare(strict_types=1);

namespace App\Traits\Interacoes;

use App\Models\Autenticacao\Utilizador;
use App\Models\Interacoes\Audicao;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Facades\Auth;

/**
 * Adiciona suporte a audições polimórficas a um modelo Eloquent.
 *
 * Disponibiliza a relação com todas as audições e o estado correspondente ao
 * utilizador autenticado através da guarda `sessao`.
 *
 * @mixin Model
 *
 * @since 2.0.0
 *
 * @version 2.0.0
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
     * Quando não existe um utilizador autenticado e persistido, a relação
     * recebe uma condição impossível e não devolve qualquer registo.
     *
     * @return MorphOne<Audicao, $this> Relação com a audição do utilizador.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    public function audicaoUtilizadorAutenticado(): MorphOne
    {
        $relacaoAudicao = $this->morphOne(
            Audicao::class,
            'audivel',
            'tipo_audivel',
            'audivel_id',
        );

        $identificadorUtilizador =
            $this->obterIdentificadorUtilizadorParaAudicoes();

        if ($identificadorUtilizador === null) {
            return $relacaoAudicao->whereRaw(
                '1 = 0',
            );
        }

        return $relacaoAudicao->where(
            'utilizador_id',
            $identificadorUtilizador,
        );
    }

    /**
     * Determina se o utilizador autenticado ouviu a entidade.
     *
     * Quando a relação já está carregada, o resultado é obtido sem executar
     * uma nova consulta. Caso contrário, é verificada a existência do registo
     * diretamente na base de dados.
     *
     * @return Attribute<bool, never> Estado da audição do utilizador.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    protected function ouvidoPeloUtilizadorAutenticado(): Attribute
    {
        return Attribute::get(
            function (): bool {
                $identificadorUtilizador =
                    $this->obterIdentificadorUtilizadorParaAudicoes();

                if ($identificadorUtilizador === null) {
                    return false;
                }

                if (
                    $this->relationLoaded(
                        'audicaoUtilizadorAutenticado',
                    )
                ) {
                    $audicao = $this->getRelation(
                        'audicaoUtilizadorAutenticado',
                    );

                    return $audicao instanceof Audicao
                        && $audicao->utilizador_id
                        === $identificadorUtilizador;
                }

                return $this
                    ->audicaoUtilizadorAutenticado()
                    ->exists();
            },
        );
    }

    /**
     * Obtém o identificador do utilizador autenticado para as audições.
     *
     * O método confirma que o objeto autenticado através da guarda `sessao`
     * corresponde a um utilizador persistido e possui um identificador inteiro
     * positivo.
     *
     * O nome inclui a referência às audições para evitar colisões com métodos
     * privados declarados por outros traits de interações.
     *
     * @return int|null Identificador do utilizador ou nulo.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    private function obterIdentificadorUtilizadorParaAudicoes(): ?int
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
