<?php

declare(strict_types=1);

namespace App\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\View\Component;

/**
 * Prepara a ligação para a página de notificações.
 *
 * Quando existem notificações por ler, o componente apresenta um indicador
 * visual com a respetiva quantidade. Valores superiores a 99 são abreviados
 * visualmente, mantendo a quantidade integral na descrição acessível.
 *
 * @since 1.0.0
 *
 * @version 3.0.0
 */
final class IconeNotificacoes extends Component
{
    /**
     * Limite máximo apresentado integralmente no indicador.
     *
     * @var int
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    private const LIMITE_QUANTIDADE_VISIVEL = 99;

    /**
     * Quantidade normalizada de notificações por ler.
     *
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    public readonly int $quantidade;

    /**
     * Quantidade apresentada visualmente.
     *
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    public readonly string $quantidadeVisivel;

    /**
     * Descrição acessível da ligação.
     *
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    public readonly string $descricao;

    /**
     * Indica se a página de notificações está ativa.
     *
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    public readonly bool $paginaAtiva;

    /**
     * Indica se existem notificações por ler.
     *
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    public readonly bool $temNotificacoesPorLer;

    /**
     * Cria uma nova instância do componente.
     *
     * @param  Request  $pedido  Pedido HTTP atual.
     * @param  mixed  $quantidade  Quantidade recebida.
     *
     * @since 1.0.0
     *
     * @version 3.0.0
     */
    public function __construct(
        Request $pedido,
        mixed $quantidade = 0,
    ) {
        $this->quantidade = $this->normalizarQuantidade(
            $quantidade,
        );

        $this->quantidadeVisivel =
            $this->quantidade > self::LIMITE_QUANTIDADE_VISIVEL
            ? self::LIMITE_QUANTIDADE_VISIVEL.'+'
            : (string) $this->quantidade;

        $this->descricao = $this->criarDescricao(
            $this->quantidade,
        );

        $this->paginaAtiva = $pedido->routeIs(
            'notificacoes.*',
        );

        $this->temNotificacoesPorLer =
            $this->quantidade > 0;
    }

    /**
     * Obtém a view do componente.
     *
     * @return View View do ícone de notificações.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function render(): View
    {
        return view(
            'components.icone-notificacoes',
        );
    }

    /**
     * Normaliza a quantidade recebida.
     *
     * Valores não numéricos ou negativos são convertidos para zero.
     *
     * @param  mixed  $quantidade  Quantidade recebida.
     * @return int Quantidade normalizada.
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    private function normalizarQuantidade(
        mixed $quantidade,
    ): int {
        if (! is_numeric($quantidade)) {
            return 0;
        }

        return max(
            0,
            (int) $quantidade,
        );
    }

    /**
     * Cria a descrição acessível da ligação.
     *
     * @param  int  $quantidade  Quantidade de notificações por ler.
     * @return string Descrição acessível.
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    private function criarDescricao(
        int $quantidade,
    ): string {
        return match ($quantidade) {
            0 => 'Notificações. Não existem notificações por ler.',

            1 => 'Notificações. Existe uma notificação por ler.',

            default => sprintf(
                'Notificações. Existem %d notificações por ler.',
                $quantidade,
            ),
        };
    }
}
