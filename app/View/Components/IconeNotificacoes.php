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
 */
final class IconeNotificacoes extends Component
{
    /**
     * Limite máximo apresentado integralmente no indicador.
     *
     * @var int
     *
     * @since 2.0.0
     */
    private const LIMITE_QUANTIDADE_VISIVEL = 99;

    /**
     * Quantidade apresentada visualmente.
     *
     * @since 2.0.0
     */
    public readonly string $quantidadeVisivel;

    /**
     * Descrição acessível da ligação.
     *
     * @since 2.0.0
     */
    public readonly string $descricao;

    /**
     * Indica se a página de notificações está ativa.
     *
     * @since 2.0.0
     */
    public readonly bool $paginaAtiva;

    /**
     * Indica se existem notificações por ler.
     *
     * @since 2.0.0
     */
    public readonly bool $temNotificacoesPorLer;

    /**
     * Cria uma nova instância do componente.
     *
     * @param  Request  $pedido  Pedido HTTP atual.
     * @param  int|string|null  $quantidade  Quantidade recebida.
     *
     * @since 1.0.0
     */
    public function __construct(
        Request $pedido,
        int|string|null $quantidade = 0,
    ) {
        $quantidadeNormalizada =
            $this->normalizarQuantidade(
                $quantidade,
            );

        $this->quantidadeVisivel =
            $quantidadeNormalizada
            > self::LIMITE_QUANTIDADE_VISIVEL
            ? self::LIMITE_QUANTIDADE_VISIVEL.'+'
            : (string) $quantidadeNormalizada;

        $this->descricao =
            $this->criarDescricao(
                $quantidadeNormalizada,
            );

        $this->paginaAtiva =
            $pedido->routeIs(
                'notificacoes.*',
            );

        $this->temNotificacoesPorLer =
            $quantidadeNormalizada > 0;
    }

    /**
     * Obtém a vista do componente.
     *
     * @return View Vista do ícone de notificações.
     *
     * @since 1.0.0
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
     * Valores que não representem números inteiros ou que sejam negativos
     * são convertidos para zero.
     *
     * @param  int|string|null  $quantidade  Quantidade recebida.
     * @return int Quantidade normalizada.
     *
     * @since 2.0.0
     */
    private function normalizarQuantidade(
        int|string|null $quantidade,
    ): int {
        if (is_string($quantidade)) {
            $quantidade = trim(
                $quantidade,
            );
        }

        if (
            $quantidade === null
            || $quantidade === ''
        ) {
            return 0;
        }

        $quantidadeValidada = filter_var(
            $quantidade,
            FILTER_VALIDATE_INT,
        );

        if (
            $quantidadeValidada === false
            || $quantidadeValidada < 0
        ) {
            return 0;
        }

        return $quantidadeValidada;
    }

    /**
     * Cria a descrição acessível da ligação.
     *
     * @param  int  $quantidade  Quantidade de notificações por ler.
     * @return string Descrição acessível.
     *
     * @since 2.0.0
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
