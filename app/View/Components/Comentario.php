<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Models\Autenticacao\Utilizador;
use App\Models\Interacoes\Comentario as ModeloComentario;
use Carbon\CarbonInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Component;
use LogicException;

/**
 * Prepara a apresentação de um comentário e das respetivas respostas.
 *
 * O componente exige que as relações `utilizador` e `respostas` tenham**
 * Prepara a apresentação de um comentário e das respetivas respostas.
 *
 * O componente exige que as relações sido
 * previamente carregadas, impedindo consultas implícitas durante a
 * renderização recursiva da árvore de comentários.
 *
 * @since 1.0.0
 *
 * @version 4.0.0
 */
final class Comentario extends Component
{
    /**
     * Comentário apresentado.
     *
     *
     * @since 3.0.0
     *
     * @version 2.0.0
     */
    public readonly ModeloComentario $comentario;

    /**
     * Identificador do comentário apresentado.
     *
     *
     * @since 3.0.0
     *
     * @version 2.0.0
     */
    public readonly int $identificadorComentario;

    /**
     * Identificador do comentário principal da árvore.
     *
     *
     * @since 3.0.0
     *
     * @version 2.0.0
     */
    public readonly int $identificadorPrincipal;

    /**
     * Utilizador responsável pelo comentário.
     *
     * Pode ser nulo quando o utilizador tiver sido removido.
     *
     *
     * @since 3.0.0
     *
     * @version 2.0.0
     */
    public readonly ?Utilizador $utilizador;

    /**
     * Utilizador autenticado que pode publicar uma resposta.
     *
     *
     * @since 3.0.0
     *
     * @version 2.0.0
     */
    public readonly ?Utilizador $utilizadorAutenticado;

    /**
     * Nome apresentado como autor do comentário.
     *
     *
     * @since 3.0.0
     *
     * @version 2.0.0
     */
    public readonly string $nomeUtilizador;

    /**
     * Quantidade de gostos atribuídos ao comentário.
     *
     *
     * @since 3.0.0
     *
     * @version 2.0.0
     */
    public readonly int $quantidadeGostos;

    /**
     * Indica se o utilizador autenticado atribuiu gosto ao comentário.
     *
     *
     * @since 3.0.0
     *
     * @version 2.0.0
     */
    public readonly bool $temGosto;

    /**
     * Descrição acessível da ação de gosto.
     *
     *
     * @since 4.0.0
     *
     * @version 1.0.0
     */
    public readonly string $descricaoAcaoGosto;

    /**
     * Momento de criação do comentário.
     *
     *
     * @since 3.0.0
     *
     * @version 2.0.0
     */
    public readonly ?CarbonInterface $momentoCriacao;

    /**
     * Respostas diretas ao comentário.
     *
     * @var Collection<int, ModeloComentario>
     *
     * @since 3.0.0
     *
     * @version 2.0.0
     */
    public readonly Collection $respostas;

    /**
     * Cria uma nova instância do componente.
     *
     * @param  ModeloComentario  $comentario  Comentário apresentado.
     * @param  int|string|null  $identificadorComentarioPrincipal  Identificador
     *                                                             da raiz.
     *
     * @throws LogicException Quando o comentário não está persistido, um
     *                        identificador é inválido, uma relação necessária
     *                        não está carregada ou possui um tipo inesperado.
     *
     * @since 1.0.0
     *
     * @version 4.0.0
     */
    public function __construct(
        ModeloComentario $comentario,
        int|string|null $identificadorComentarioPrincipal = null,
    ) {
        $this->comentario = $comentario;

        $this->identificadorComentario =
            $this->obterIdentificadorComentario(
                $comentario,
            );

        $this->identificadorPrincipal =
            $this->normalizarIdentificadorPrincipal(
                $identificadorComentarioPrincipal,
                $this->identificadorComentario,
            );

        $this->utilizador =
            $this->obterUtilizadorComentario(
                $comentario,
            );

        $nomeUtilizador = trim(
            (string) (
                $this->utilizador?->nome
                ?? ''
            ),
        );

        $this->nomeUtilizador =
            $nomeUtilizador !== ''
            ? $nomeUtilizador
            : 'Utilizador removido';

        $this->quantidadeGostos = max(
            0,
            (int) (
                $comentario->quantidade_gostos
                ?? 0
            ),
        );

        $this->temGosto = (bool) (
            $comentario->gostado_pelo_utilizador_autenticado
            ?? false
        );

        $descricaoQuantidadeGostos =
            $this->quantidadeGostos === 1
            ? '1 gosto'
            : "{$this->quantidadeGostos} gostos";

        $this->descricaoAcaoGosto = sprintf(
            '%s. %s.',
            $this->temGosto
                ? 'Remover gosto'
                : 'Adicionar gosto',
            $descricaoQuantidadeGostos,
        );

        $this->momentoCriacao =
            $this->obterMomentoCriacao(
                $comentario,
            );

        $this->respostas =
            $this->obterRespostas(
                $comentario,
            );

        $this->utilizadorAutenticado =
            $this->obterUtilizadorAutenticado();
    }

    /**
     * Obtém a vista do componente.
     *
     * @return View Vista responsável pela apresentação.
     *
     * @since 1.0.0
     *
     * @version 3.0.0
     */
    public function render(): View
    {
        return view(
            'components.comentario',
        );
    }

    /**
     * Obtém o identificador persistido do comentário.
     *
     * @param  ModeloComentario  $comentario  Comentário recebido.
     * @return int Identificador do comentário.
     *
     * @throws LogicException Quando o comentário não está persistido ou o
     *                        identificador não é um inteiro positivo.
     *
     * @since 3.0.0
     *
     * @version 2.0.0
     */
    private function obterIdentificadorComentario(
        ModeloComentario $comentario,
    ): int {
        if (! $comentario->exists) {
            throw new LogicException(
                'O comentário deve estar persistido antes de ser apresentado.',
            );
        }

        $identificador = $comentario->getKey();

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
            'O comentário possui um identificador persistido inválido.',
        );
    }

    /**
     * Normaliza o identificador do comentário principal.
     *
     * Quando o identificador não é fornecido, é utilizado o identificador do
     * comentário atual. Qualquer valor fornecido deve representar um inteiro
     * positivo.
     *
     * @param  int|string|null  $identificador  Identificador recebido.
     * @param  int  $identificadorPredefinido  Identificador alternativo.
     * @return int Identificador normalizado.
     *
     * @throws LogicException Quando o identificador fornecido é inválido.
     *
     * @since 3.0.0
     *
     * @version 2.0.0
     */
    private function normalizarIdentificadorPrincipal(
        int|string|null $identificador,
        int $identificadorPredefinido,
    ): int {
        if ($identificador === null) {
            return $identificadorPredefinido;
        }

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
            'O identificador do comentário principal deve ser um inteiro positivo.',
        );
    }

    /**
     * Obtém o utilizador associado ao comentário.
     *
     * @param  ModeloComentario  $comentario  Comentário apresentado.
     * @return Utilizador|null Utilizador relacionado.
     *
     * @throws LogicException Quando a relação não está carregada ou possui
     *                        um tipo inesperado.
     *
     * @since 3.0.0
     *
     * @version 2.0.0
     */
    private function obterUtilizadorComentario(
        ModeloComentario $comentario,
    ): ?Utilizador {
        if (! $comentario->relationLoaded('utilizador')) {
            throw new LogicException(
                'A relação "utilizador" deve ser carregada antes de apresentar o comentário.',
            );
        }

        $utilizador = $comentario->getRelation(
            'utilizador',
        );

        if (
            $utilizador !== null
            && ! $utilizador instanceof Utilizador
        ) {
            throw new LogicException(
                'A relação "utilizador" do comentário possui um tipo inesperado.',
            );
        }

        return $utilizador;
    }

    /**
     * Obtém as respostas previamente carregadas.
     *
     * @param  ModeloComentario  $comentario  Comentário apresentado.
     * @return Collection<int, ModeloComentario> Respostas diretas.
     *
     * @throws LogicException Quando a relação não está carregada ou possui
     *                        um tipo inesperado.
     *
     * @since 3.0.0
     *
     * @version 2.0.0
     */
    private function obterRespostas(
        ModeloComentario $comentario,
    ): Collection {
        if (! $comentario->relationLoaded('respostas')) {
            throw new LogicException(
                'A relação "respostas" deve ser carregada antes de apresentar o comentário.',
            );
        }

        $respostas = $comentario->getRelation(
            'respostas',
        );

        if (! $respostas instanceof Collection) {
            throw new LogicException(
                'A relação "respostas" do comentário possui um tipo inesperado.',
            );
        }

        foreach ($respostas as $resposta) {
            if (! $resposta instanceof ModeloComentario) {
                throw new LogicException(
                    'A relação "respostas" contém um modelo inesperado.',
                );
            }
        }

        /** @var Collection<int, ModeloComentario> $respostas */
        return $respostas;
    }

    /**
     * Obtém o momento de criação do comentário.
     *
     * @param  ModeloComentario  $comentario  Comentário apresentado.
     * @return CarbonInterface|null Momento de criação.
     *
     * @throws LogicException Quando o valor possui um tipo inesperado.
     *
     * @since 3.0.0
     *
     * @version 2.0.0
     */
    private function obterMomentoCriacao(
        ModeloComentario $comentario,
    ): ?CarbonInterface {
        $momentoCriacao = $comentario->created_at;

        if (
            $momentoCriacao !== null
            && ! $momentoCriacao instanceof CarbonInterface
        ) {
            throw new LogicException(
                'O momento de criação do comentário possui um tipo inesperado.',
            );
        }

        return $momentoCriacao;
    }

    /**
     * Obtém o utilizador autenticado através do guard da aplicação.
     *
     * @return Utilizador|null Utilizador autenticado ou nulo.
     *
     * @throws LogicException Quando o guard devolve um tipo inesperado.
     *
     * @since 3.0.0
     *
     * @version 2.0.0
     */
    private function obterUtilizadorAutenticado(): ?Utilizador
    {
        $utilizador = Auth::guard(
            'sessao',
        )->user();

        if (
            $utilizador !== null
            && ! $utilizador instanceof Utilizador
        ) {
            throw new LogicException(
                'O guard sessao devolveu um utilizador de tipo inesperado.',
            );
        }

        return $utilizador;
    }
}
