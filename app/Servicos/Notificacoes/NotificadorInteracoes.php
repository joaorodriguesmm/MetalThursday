<?php

declare(strict_types=1);

namespace App\Servicos\Notificacoes;

use App\Models\Autenticacao\Utilizador;
use App\Notifications\NotificacaoInteracaoUtilizador;
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
 * Quando não existe um utilizador autenticado válido, nenhuma notificação é
 * enviada.
 *
 * @since 2.0.0
 *
 * @version 1.1.0
 */
final class NotificadorInteracoes
{
    /**
     * Quantidade máxima de destinatários processados em cada lote.
     *
     * @var int
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const TAMANHO_LOTE = 200;

    /**
     * Comprimento máximo da descrição textual da interação.
     *
     * @var int
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const COMPRIMENTO_MAXIMO_DESCRICAO = 255;

    /**
     * Cria o serviço de notificações de interações.
     *
     * @param  FabricaAutenticacao  $autenticacao  Serviço de autenticação.
     * @param  DespachanteNotificacoes  $notificacoes  Serviço de notificações.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function __construct(
        private readonly FabricaAutenticacao $autenticacao,
        private readonly DespachanteNotificacoes $notificacoes,
    ) {}

    /**
     * Notifica os restantes utilizadores sobre uma interação.
     *
     * O utilizador responsável pela interação é excluído dos destinatários.
     * Quando não existe um utilizador autenticado válido, nenhuma notificação
     * é enviada.
     *
     * @param  Model  $entidade  Entidade que recebeu a interação.
     * @param  string  $descricaoAcao  Descrição textual da ação realizada.
     *
     * @throws InvalidArgumentException Quando a entidade ou a descrição não
     *                                  são válidas.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    public function notificarOutrosUtilizadores(
        Model $entidade,
        string $descricaoAcao,
    ): void {
        $utilizadorAutenticado =
            $this->obterUtilizadorAutenticado();

        if ($utilizadorAutenticado === null) {
            return;
        }

        $identificadorUtilizador =
            $this->obterIdentificadorUtilizador(
                $utilizadorAutenticado,
            );

        $this->validarEntidadePersistida(
            $entidade,
        );

        $descricaoNormalizada =
            $this->normalizarDescricaoAcao(
                $descricaoAcao,
            );

        Utilizador::query()
            ->selecionaveis()
            ->whereKeyNot(
                $identificadorUtilizador,
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
                        new NotificacaoInteracaoUtilizador(
                            $entidade,
                            $utilizadorAutenticado,
                            $descricaoNormalizada,
                        ),
                    );
                },
                'id',
                'id',
            );
    }

    /**
     * Obtém o utilizador autenticado.
     *
     * @return Utilizador|null Utilizador autenticado ou nulo.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function obterUtilizadorAutenticado(): ?Utilizador
    {
        $utilizador =
            $this
                ->autenticacao
                ->guard()
                ->user();

        return $utilizador instanceof Utilizador
            ? $utilizador
            : null;
    }

    /**
     * Obtém o identificador de um utilizador persistido.
     *
     * @param  Utilizador  $utilizador  Utilizador recebido.
     * @return int Identificador do utilizador.
     *
     * @throws InvalidArgumentException Quando o utilizador não está
     *                                  persistido.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function obterIdentificadorUtilizador(
        Utilizador $utilizador,
    ): int {
        $identificador =
            $utilizador->getKey();

        if (
            ! $utilizador->exists
            || ! is_numeric($identificador)
            || (int) $identificador < 1
        ) {
            throw new InvalidArgumentException(
                'O utilizador responsável pela interação deve estar persistido.',
            );
        }

        return (int) $identificador;
    }

    /**
     * Confirma que a entidade da interação está persistida.
     *
     * @param  Model  $entidade  Entidade recebida.
     *
     * @throws InvalidArgumentException Quando a entidade não está persistida.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function validarEntidadePersistida(
        Model $entidade,
    ): void {
        if (
            $entidade->exists
            && $entidade->getKey() !== null
        ) {
            return;
        }

        throw new InvalidArgumentException(
            'A entidade da interação deve estar persistida antes da notificação.',
        );
    }

    /**
     * Normaliza e valida a descrição textual da ação.
     *
     * Espaços consecutivos, tabulações e quebras de linha são convertidos num
     * único espaço.
     *
     * @param  string  $descricao  Descrição recebida.
     * @return string Descrição normalizada.
     *
     * @throws InvalidArgumentException Quando a descrição não é válida.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function normalizarDescricaoAcao(
        string $descricao,
    ): string {
        if (
            preg_match(
                '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/',
                $descricao,
            ) === 1
        ) {
            throw new InvalidArgumentException(
                'A descrição da interação contém caracteres inválidos.',
            );
        }

        $descricaoNormalizada =
            preg_replace(
                '/\s+/u',
                ' ',
                trim(
                    $descricao,
                ),
            );

        if (
            ! is_string($descricaoNormalizada)
            || $descricaoNormalizada === ''
        ) {
            throw new InvalidArgumentException(
                'A descrição da ação da interação não pode estar vazia.',
            );
        }

        if (
            mb_strlen(
                $descricaoNormalizada,
            ) > self::COMPRIMENTO_MAXIMO_DESCRICAO
        ) {
            throw new InvalidArgumentException(
                sprintf(
                    'A descrição da interação não pode exceder %d caracteres.',
                    self::COMPRIMENTO_MAXIMO_DESCRICAO,
                ),
            );
        }

        return $descricaoNormalizada;
    }
}
