<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Models\Autenticacao\Utilizador;
use App\Models\Interacoes\Comentario;
use App\Models\MetalThursday\MetalThursday;
use App\Models\MetalThursday\SeccaoMetalThursday;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\Component;
use LogicException;

/**
 * Prepara a apresentação dos comentários de uma entidade.
 *
 * O componente exige que a relação `comentarios` tenha sido previamente
 * carregada pelo controlador, impedindo consultas à base de dados durante
 * a renderização da view.
 *
 * @since 1.0.0
 *
 * @version 3.0.0
 */
final class SeccaoComentarios extends Component
{
    /**
     * Entidade que recebe os comentários.
     *
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    public readonly MetalThursday|SeccaoMetalThursday $comentavel;

    /**
     * Identificador da entidade comentada.
     *
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    public readonly int $identificadorComentavel;

    /**
     * Tipo canónico utilizado pela rota dos comentários.
     *
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    public readonly string $tipoComentavel;

    /**
     * Comentários principais da entidade.
     *
     * @var Collection<int, Comentario>
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    public readonly Collection $comentarios;

    /**
     * Utilizador autenticado.
     *
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    public readonly ?Utilizador $utilizadorAutenticado;

    /**
     * Identificador HTML do formulário.
     *
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    public readonly string $identificadorFormulario;

    /**
     * Identificador HTML do campo de conteúdo.
     *
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    public readonly string $identificadorConteudo;

    /**
     * Identificador HTML do contentor de erro.
     *
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    public readonly string $identificadorErro;

    /**
     * Cria uma nova instância do componente.
     *
     * @param  MetalThursday|SeccaoMetalThursday  $comentavel  Entidade comentada.
     * @param  string  $tipoComentavel  Tipo recebido pela utilização do componente.
     *
     * @throws LogicException Quando a entidade não está persistida, o tipo não
     *                        corresponde ao modelo ou a relação não foi carregada.
     *
     * @since 1.0.0
     *
     * @version 3.0.0
     */
    public function __construct(
        MetalThursday|SeccaoMetalThursday $comentavel,
        string $tipoComentavel,
    ) {
        $this->comentavel = $comentavel;

        $this->identificadorComentavel =
            $this->obterIdentificadorComentavel(
                $comentavel,
            );

        $this->tipoComentavel =
            $this->normalizarTipoComentavel(
                $comentavel,
                $tipoComentavel,
            );

        $this->comentarios =
            $this->obterComentarios(
                $comentavel,
            );

        $this->utilizadorAutenticado =
            $this->obterUtilizadorAutenticado();

        $tipoIdentificador = Str::of(
            $this->tipoComentavel,
        )
            ->replace(
                '_',
                '-',
            )
            ->slug('-')
            ->toString();

        $sufixoIdentificador =
            "{$tipoIdentificador}-{$this->identificadorComentavel}";

        $this->identificadorFormulario =
            "formulario-comentario-{$sufixoIdentificador}";

        $this->identificadorConteudo =
            "conteudo-comentario-{$sufixoIdentificador}";

        $this->identificadorErro =
            "erro-comentario-{$sufixoIdentificador}";
    }

    /**
     * Obtém a view do componente.
     *
     * @return View View da secção de comentários.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
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
     * @param  MetalThursday|SeccaoMetalThursday  $comentavel  Entidade recebida.
     * @return int Identificador da entidade.
     *
     * @throws LogicException Quando a entidade não está persistida.
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    private function obterIdentificadorComentavel(
        MetalThursday|SeccaoMetalThursday $comentavel,
    ): int {
        $identificador = $comentavel->getKey();

        if (
            ! $comentavel->exists
            || ! is_numeric($identificador)
            || (int) $identificador < 1
        ) {
            throw new LogicException(
                'A entidade comentada deve estar persistida.',
            );
        }

        return (int) $identificador;
    }

    /**
     * Normaliza e valida o tipo da entidade comentada.
     *
     * @param  MetalThursday|SeccaoMetalThursday  $comentavel  Entidade recebida.
     * @param  string  $tipoRecebido  Tipo recebido pelo componente.
     * @return string Tipo canónico.
     *
     * @throws LogicException Quando o tipo não corresponde ao modelo.
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    private function normalizarTipoComentavel(
        MetalThursday|SeccaoMetalThursday $comentavel,
        string $tipoRecebido,
    ): string {
        $tipoEsperado = match (true) {
            $comentavel instanceof MetalThursday => 'metal_thursday',

            $comentavel instanceof SeccaoMetalThursday => 'seccao_metal_thursday',
        };

        $tipoNormalizado = mb_strtolower(
            trim(
                $tipoRecebido,
            ),
        );

        if ($tipoNormalizado !== $tipoEsperado) {
            throw new LogicException(
                'O tipo da entidade comentada não corresponde ao modelo recebido.',
            );
        }

        return $tipoEsperado;
    }

    /**
     * Obtém os comentários previamente carregados.
     *
     * @param  MetalThursday|SeccaoMetalThursday  $comentavel  Entidade recebida.
     * @return Collection<int, Comentario> Comentários principais.
     *
     * @throws LogicException Quando a relação não está carregada ou contém
     *                        modelos inesperados.
     *
     * @since 3.0.0
     *
     * @version 1.0.0
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
     * Obtém o utilizador autenticado.
     *
     * @return Utilizador|null Utilizador autenticado ou nulo.
     *
     * @throws LogicException Quando o guard devolve um tipo inesperado.
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    private function obterUtilizadorAutenticado(): ?Utilizador
    {
        $utilizador = Auth::guard(
            'web',
        )->user();

        if (
            $utilizador !== null
            && ! $utilizador instanceof Utilizador
        ) {
            throw new LogicException(
                'O guard web devolveu um utilizador de tipo inesperado.',
            );
        }

        return $utilizador;
    }
}
