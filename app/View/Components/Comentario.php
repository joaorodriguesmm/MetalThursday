<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Models\Autenticacao\Utilizador;
use App\Models\Interacoes\Comentario as ModeloComentario;
use Carbon\CarbonInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Component;
use LogicException;

/**
 * Prepara a apresentação de um comentário.
 *
 * O componente apresenta apenas o comentário recebido. As respostas são
 * carregadas assincronamente quando o utilizador expande o respetivo ramo.
 *
 * A consulta deve preparar previamente a relação `utilizador`, a quantidade
 * de gostos, a quantidade de respostas diretas e o estado de gosto do
 * utilizador autenticado.
 *
 * @since 1.0.0
 */
final class Comentario extends Component
{
    /**
     * Comentário apresentado.
     *
     * @since 2.0.0
     */
    public readonly ModeloComentario $comentario;

    /**
     * Identificador do comentário apresentado.
     *
     * @since 2.0.0
     */
    public readonly int $identificadorComentario;

    /**
     * Utilizador responsável pelo comentário.
     *
     * Pode ser nulo quando o utilizador tiver sido removido.
     *
     * @since 2.0.0
     */
    public readonly ?Utilizador $utilizador;

    /**
     * Utilizador autenticado que pode publicar uma resposta.
     *
     * @since 2.0.0
     */
    public readonly ?Utilizador $utilizadorAutenticado;

    /**
     * Nome apresentado como autor do comentário.
     *
     * @since 2.0.0
     */
    public readonly string $nomeUtilizador;

    /**
     * Quantidade de gostos atribuídos ao comentário.
     *
     * @since 2.0.0
     */
    public readonly int $quantidadeGostos;

    /**
     * Quantidade de respostas diretas ao comentário.
     *
     * @since 2.0.0
     */
    public readonly int $quantidadeRespostas;

    /**
     * Indica se o utilizador autenticado atribuiu gosto ao comentário.
     *
     * @since 2.0.0
     */
    public readonly bool $temGosto;

    /**
     * Descrição acessível da ação de gosto.
     *
     * @since 2.0.0
     */
    public readonly string $descricaoAcaoGosto;

    /**
     * Momento de criação do comentário.
     *
     * @since 2.0.0
     */
    public readonly ?CarbonInterface $momentoCriacao;

    /**
     * Cria uma nova instância do componente.
     *
     * @param  ModeloComentario  $comentario  Comentário apresentado.
     *
     * @throws LogicException Quando o comentário não está persistido, a
     *                        relação do utilizador não foi carregada ou contém
     *                        dados inesperados.
     *
     * @since 1.0.0
     */
    public function __construct(
        ModeloComentario $comentario,
    ) {
        $this->comentario =
            $comentario;

        $this->identificadorComentario =
            $this->obterIdentificadorComentario(
                $comentario,
            );

        $this->utilizador =
            $this->obterUtilizadorComentario(
                $comentario,
            );

        $nomeUtilizador =
            trim(
                (string) (
                    $this->utilizador?->nome
                    ?? ''
                ),
            );

        $this->nomeUtilizador =
            $nomeUtilizador !== ''
            ? $nomeUtilizador
            : 'Utilizador removido';

        $this->quantidadeGostos =
            max(
                0,
                (int) (
                    $comentario->quantidade_gostos
                    ?? 0
                ),
            );

        $this->quantidadeRespostas =
            max(
                0,
                (int) (
                    $comentario->quantidade_respostas
                    ?? 0
                ),
            );

        $this->temGosto =
            (bool) (
                $comentario->gostado_pelo_utilizador_autenticado
                ?? false
            );

        $descricaoQuantidadeGostos =
            $this->quantidadeGostos === 1
            ? '1 gosto'
            : "{$this->quantidadeGostos} gostos";

        $this->descricaoAcaoGosto =
            sprintf(
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

        $this->utilizadorAutenticado =
            $this->obterUtilizadorAutenticado();
    }

    /**
     * Obtém a vista do componente.
     *
     * @return View Vista responsável pela apresentação.
     *
     * @since 1.0.0
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
     * @since 2.0.0
     */
    private function obterIdentificadorComentario(
        ModeloComentario $comentario,
    ): int {
        if (! $comentario->exists) {
            throw new LogicException(
                'O comentário deve estar persistido antes de ser apresentado.',
            );
        }

        $identificador =
            $comentario->getKey();

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
     * Obtém o utilizador associado ao comentário.
     *
     * @param  ModeloComentario  $comentario  Comentário recebido.
     * @return Utilizador|null Utilizador relacionado.
     *
     * @throws LogicException Quando a relação não está carregada ou possui
     *                        um tipo inesperado.
     *
     * @since 2.0.0
     */
    private function obterUtilizadorComentario(
        ModeloComentario $comentario,
    ): ?Utilizador {
        if (! $comentario->relationLoaded('utilizador')) {
            throw new LogicException(
                'A relação "utilizador" deve ser carregada antes de apresentar o comentário.',
            );
        }

        $utilizador =
            $comentario->getRelation(
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
     * Obtém o momento de criação do comentário.
     *
     * @param  ModeloComentario  $comentario  Comentário recebido.
     * @return CarbonInterface|null Momento de criação.
     *
     * @throws LogicException Quando o valor possui um tipo inesperado.
     *
     * @since 2.0.0
     */
    private function obterMomentoCriacao(
        ModeloComentario $comentario,
    ): ?CarbonInterface {
        $momentoCriacao =
            $comentario->created_at;

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
     * Obtém o utilizador autenticado através da guarda da aplicação.
     *
     * @return Utilizador|null Utilizador autenticado ou nulo.
     *
     * @throws LogicException Quando a guarda devolve um tipo inesperado.
     *
     * @since 2.0.0
     */
    private function obterUtilizadorAutenticado(): ?Utilizador
    {
        $utilizador =
            Auth::guard(
                'sessao',
            )->user();

        if (
            $utilizador !== null
            && ! $utilizador instanceof Utilizador
        ) {
            throw new LogicException(
                'A guarda `sessao` devolveu um utilizador de tipo inesperado.',
            );
        }

        return $utilizador;
    }
}
