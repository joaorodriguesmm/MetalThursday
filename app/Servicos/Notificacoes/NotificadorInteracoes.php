<?php

declare(strict_types=1);

namespace App\Servicos\Notificacoes;

use App\Models\Autenticacao\Utilizador;
use App\Notifications\UserInteractionOccurred;
use Illuminate\Contracts\Auth\Factory as FabricaAutenticacao;
use Illuminate\Contracts\Notifications\Dispatcher as DespachanteNotificacoes;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Envia notificações relacionadas com interações dos utilizadores.
 *
 * O serviço exclui o utilizador responsável pela interação e processa os
 * restantes destinatários por lotes, evitando carregar todos os utilizadores
 * simultaneamente em memória.
 *
 * @since 2.0.0
 * @version 1.0.0
 */
final class NotificadorInteracoes
{
    /**
     * Quantidade máxima de destinatários processados em cada lote.
     *
     * @var int
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    private const TAMANHO_LOTE = 200;

    /**
     * Cria o serviço de notificações de interações.
     *
     * @param FabricaAutenticacao $autenticacao Serviço de autenticação.
     * @param DespachanteNotificacoes $notificacoes Serviço de notificações.
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    public function __construct(
        private readonly FabricaAutenticacao $autenticacao,
        private readonly DespachanteNotificacoes $notificacoes,
    ) {}

    /**
     * Notifica os utilizadores elegíveis sobre uma interação.
     *
     * O utilizador responsável pela interação é excluído dos destinatários.
     * Quando não existe um utilizador autenticado, nenhuma notificação é
     * enviada.
     *
     * @param Model $entidade Entidade que recebeu a interação.
     * @param string $descricaoAcao Descrição textual da ação realizada.
     *
     * @throws InvalidArgumentException Quando a descrição está vazia.
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    public function notificarOutrosUtilizadores(
        Model $entidade,
        string $descricaoAcao,
    ): void {
        $utilizadorAutenticado = $this
            ->autenticacao
            ->guard()
            ->user();

        if (! $utilizadorAutenticado instanceof Utilizador) {
            return;
        }

        $descricaoNormalizada = trim(
            $descricaoAcao,
        );

        if ($descricaoNormalizada === '') {
            throw new InvalidArgumentException(
                'A descrição da ação da interação não pode estar vazia.',
            );
        }

        Utilizador::query()
            ->selecionaveis()
            ->where(
                $utilizadorAutenticado->getKeyName(),
                '!=',
                $utilizadorAutenticado->getKey(),
            )
            ->chunkById(
                self::TAMANHO_LOTE,
                function (
                    Collection $destinatarios,
                ) use (
                    $entidade,
                    $utilizadorAutenticado,
                    $descricaoNormalizada,
                ): void {
                    if ($destinatarios->isEmpty()) {
                        return;
                    }

                    $this->notificacoes->send(
                        $destinatarios,
                        new UserInteractionOccurred(
                            $entidade,
                            $utilizadorAutenticado,
                            $descricaoNormalizada,
                        ),
                    );
                },
                $utilizadorAutenticado->getKeyName(),
            );
    }
}
