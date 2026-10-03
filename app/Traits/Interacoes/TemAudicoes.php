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
use LogicException;

/**
 * Adiciona suporte a audições polimórficas a um modelo Eloquent.
 *
 * Disponibiliza a relação com todas as audições e o estado correspondente ao
 * utilizador autenticado através da guarda `sessao`.
 *
 * @mixin Model
 *
 * @since 2.0.0
 */
trait TemAudicoes
{
    /**
     * Alias do estado escalar de audição do utilizador autenticado.
     *
     * @since 2.0.0
     */
    public const COLUNA_OUVIDO_PELO_UTILIZADOR_AUTENTICADO =
        'ouvido_pelo_utilizador_autenticado';

    /**
     * Obtém os registos de audição associados ao modelo.
     *
     * @return MorphMany<Audicao, $this> Relação com as audições.
     *
     * @since 2.0.0
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
     * Quando não existe autenticação válida, é devolvido falso.
     *
     * Para um utilizador autenticado, a consulta pode carregar previamente o
     * estado escalar `ouvido_pelo_utilizador_autenticado`. Na ausência desse
     * atributo, a relação `audicaoUtilizadorAutenticado` deve estar
     * explicitamente carregada. O accessor nunca executa consultas ocultas.
     *
     * @return Attribute<bool, never> Estado da audição do utilizador.
     *
     * @throws LogicException Quando existe autenticação válida, mas a relação
     *                        necessária não foi carregada.
     *
     * @since 2.0.0
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

                $atributos =
                    $this->getAttributes();

                if (
                    array_key_exists(
                        self::COLUNA_OUVIDO_PELO_UTILIZADOR_AUTENTICADO,
                        $atributos,
                    )
                ) {
                    return (bool) $atributos[
                        self::COLUNA_OUVIDO_PELO_UTILIZADOR_AUTENTICADO
                    ];
                }

                if (
                    ! $this->relationLoaded(
                        'audicaoUtilizadorAutenticado',
                    )
                ) {
                    throw new LogicException(
                        'A relação "audicaoUtilizadorAutenticado" deve estar carregada antes de obter o estado de audição do utilizador autenticado.',
                    );
                }

                $audicao = $this->getRelation(
                    'audicaoUtilizadorAutenticado',
                );

                return $audicao instanceof Audicao
                    && $audicao->utilizador_id
                    === $identificadorUtilizador;
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
