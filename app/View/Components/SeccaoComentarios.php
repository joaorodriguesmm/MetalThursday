<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Enumeracoes\Interacoes\TipoEntidadeInteracao;
use App\Models\Autenticacao\Utilizador;
use App\Models\Interacoes\Comentario;
use App\Models\MetalThursday\MetalThursday;
use App\Models\MetalThursday\SeccaoMetalThursday;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Component;
use LogicException;

/**
 * Prepara a apresentação dos comentários de uma entidade.
 *
 * O componente exige que a relação `comentarios` tenha sido previamente
 * carregada pelo controlador, impedindo consultas à base de dados durante
 * a renderização da vista.
 *
 * @since 1.0.0
 */
final class SeccaoComentarios extends Component
{
    /**
     * Identificador da entidade comentada.
     *
     * @since 2.0.0
     */
    public readonly int $identificadorComentavel;

    /**
     * Tipo canónico utilizado pela rota dos comentários.
     *
     * @since 2.0.0
     */
    public readonly string $tipoComentavel;

    /**
     * Comentários principais da entidade.
     *
     * @var Collection<int, Comentario>
     *
     * @since 2.0.0
     */
    public readonly Collection $comentarios;

    /**
     * Utilizador autenticado.
     *
     * @since 2.0.0
     */
    public readonly Utilizador $utilizadorAutenticado;

    /**
     * Endereço utilizado para publicar um comentário.
     *
     * @since 2.0.0
     */
    public readonly string $enderecoGuardarComentario;

    /**
     * Identificador HTML do formulário.
     *
     * @since 2.0.0
     */
    public readonly string $identificadorFormulario;

    /**
     * Identificador HTML do campo de conteúdo.
     *
     * @since 2.0.0
     */
    public readonly string $identificadorConteudo;

    /**
     * Identificador HTML do contentor de erro.
     *
     * @since 2.0.0
     */
    public readonly string $identificadorErro;

    /**
     * Cria uma nova instância do componente.
     *
     * @param  MetalThursday|SeccaoMetalThursday  $comentavel  Entidade
     *                                                         comentada.
     *
     * @throws AuthenticationException Quando não existe um utilizador
     *                                 autenticado e persistido válido.
     * @throws LogicException Quando a entidade não está persistida ou a
     *                        relação de comentários não foi carregada
     *                        corretamente.
     *
     * @since 1.0.0
     */
    public function __construct(
        MetalThursday|SeccaoMetalThursday $comentavel,
    ) {
        $this->identificadorComentavel =
            $this->obterIdentificadorComentavel(
                $comentavel,
            );

        $this->tipoComentavel =
            TipoEntidadeInteracao::deModelo(
                $comentavel,
            )->value;

        $this->comentarios =
            $this->obterComentarios(
                $comentavel,
            );

        $this->utilizadorAutenticado =
            $this->obterUtilizadorAutenticado();

        $this->enderecoGuardarComentario =
            route(
                'comentarios.guardar',
                [
                    'tipoComentavel' => $this->tipoComentavel,

                    'identificadorComentavel' => $this->identificadorComentavel,
                ],
            );

        $sufixoIdentificador =
            "{$this->tipoComentavel}-{$this->identificadorComentavel}";

        $this->identificadorFormulario =
            "formulario-comentario-{$sufixoIdentificador}";

        $this->identificadorConteudo =
            "conteudo-comentario-{$sufixoIdentificador}";

        $this->identificadorErro =
            "erro-comentario-{$sufixoIdentificador}";
    }

    /**
     * Obtém a vista do componente.
     *
     * @return View Vista da secção de comentários.
     *
     * @since 1.0.0
     */
    public function render(): View
    {
        return view(
            'components.seccao-comentarios',
        );
    }

    /**
     * Obtém o identificador persistido da entidade.
     *
     * @param  MetalThursday|SeccaoMetalThursday  $comentavel  Entidade
     *                                                         recebida.
     * @return int Identificador da entidade.
     *
     * @throws LogicException Quando a entidade não está persistida ou possui
     *                        um identificador inválido.
     *
     * @since 2.0.0
     */
    private function obterIdentificadorComentavel(
        MetalThursday|SeccaoMetalThursday $comentavel,
    ): int {
        if (! $comentavel->exists) {
            throw new LogicException(
                'A entidade comentada deve estar persistida.',
            );
        }

        $identificador = $comentavel->getKey();

        if (
            is_int($identificador)
            && $identificador > 0
        ) {
            return $identificador;
        }

        if (
            is_string($identificador)
            && ctype_digit($identificador)
            && (int) $identificador > 0
        ) {
            return (int) $identificador;
        }

        throw new LogicException(
            'A entidade comentada possui um identificador persistido inválido.',
        );
    }

    /**
     * Obtém os comentários previamente carregados.
     *
     * @param  MetalThursday|SeccaoMetalThursday  $comentavel  Entidade
     *                                                         recebida.
     * @return Collection<int, Comentario> Comentários principais.
     *
     * @throws LogicException Quando a relação não está carregada ou contém
     *                        modelos inesperados.
     *
     * @since 2.0.0
     */
    private function obterComentarios(
        MetalThursday|SeccaoMetalThursday $comentavel,
    ): Collection {
        if (! $comentavel->relationLoaded('comentarios')) {
            throw new LogicException(
                'A relação "comentarios" deve ser carregada antes de apresentar a secção.',
            );
        }

        $comentarios = $comentavel->getRelation(
            'comentarios',
        );

        if (! $comentarios instanceof Collection) {
            throw new LogicException(
                'A relação "comentarios" possui um tipo inesperado.',
            );
        }

        foreach ($comentarios as $comentario) {
            if (! $comentario instanceof Comentario) {
                throw new LogicException(
                    'A relação "comentarios" contém um modelo inesperado.',
                );
            }
        }

        /** @var Collection<int, Comentario> $comentarios */
        return $comentarios;
    }

    /**
     * Obtém o utilizador autenticado através da guarda da aplicação.
     *
     * @return Utilizador Utilizador autenticado.
     *
     * @throws AuthenticationException Quando não existe um utilizador
     *                                 autenticado e persistido válido.
     *
     * @since 2.0.0
     */
    private function obterUtilizadorAutenticado(): Utilizador
    {
        $utilizador = Auth::guard(
            'sessao',
        )->user();

        if (! $utilizador instanceof Utilizador) {
            throw new AuthenticationException(
                'É necessário iniciar sessão para apresentar os comentários.',
                [
                    'sessao',
                ],
            );
        }

        $identificador = $utilizador->getKey();

        if (
            ! $utilizador->exists
            || (
                ! is_int($identificador)
                && ! (
                    is_string($identificador)
                    && ctype_digit($identificador)
                )
            )
            || (int) $identificador < 1
        ) {
            throw new AuthenticationException(
                'Não foi possível identificar o utilizador autenticado.',
                [
                    'sessao',
                ],
            );
        }

        return $utilizador;
    }
}
