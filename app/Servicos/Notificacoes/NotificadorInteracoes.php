<?php

declare(strict_types=1);

namespace App\Servicos\Notificacoes;

use App\Models\Autenticacao\Utilizador;
use App\Models\Interacoes\Comentario;
use App\Models\MetalThursday\MetalThursday;
use App\Models\MetalThursday\SeccaoMetalThursday;
use App\Notifications\NotificacaoInteracaoUtilizador;
use Illuminate\Contracts\Notifications\Dispatcher as DespachanteNotificacoes;
use Illuminate\Database\Eloquent\Collection as ColecaoEloquent;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Envia notificações relacionadas com interações dos utilizadores.
 *
 * O serviço exclui o utilizador responsável pela interação e processa os
 * restantes destinatários por lotes, evitando carregar todos os utilizadores
 * simultaneamente em memória.
 *
 * O sujeito e o utilizador responsável são recebidos explicitamente,
 * mantendo o serviço independente do contexto HTTP e do estado global da
 * autenticação.
 *
 * @since 2.0.0
 *
 * @version 2.0.0
 */
final class NotificadorInteracoes
{
    /**
     * Quantidade máxima de destinatários processados em cada lote.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const TAMANHO_LOTE = 200;

    /**
     * Cria o serviço de notificações de interações.
     *
     * @param  DespachanteNotificacoes  $notificacoes  Serviço responsável
     *                                                 pelo envio das
     *                                                 notificações.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    public function __construct(
        private readonly DespachanteNotificacoes $notificacoes,
    ) {}

    /**
     * Notifica os restantes utilizadores sobre uma interação.
     *
     * O utilizador responsável pela interação é excluído dos destinatários.
     * Apenas utilizadores pertencentes ao âmbito `selecionaveis` recebem a
     * notificação.
     *
     * A notificação é construída antes da consulta dos destinatários, fazendo
     * com que eventuais dados inválidos sejam rejeitados mesmo quando não
     * existem outros utilizadores a notificar.
     *
     * @param  MetalThursday|SeccaoMetalThursday|Comentario  $sujeito  Entidade
     *                                                                 que
     *                                                                 recebeu
     *                                                                 a
     *                                                                 interação.
     * @param  Utilizador  $causador  Utilizador responsável pela interação.
     * @param  string  $acao  Ação realizada.
     *
     * @throws InvalidArgumentException Quando o sujeito, o utilizador ou a
     *                                  ação não são válidos.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    public function notificarOutrosUtilizadores(
        MetalThursday|SeccaoMetalThursday|Comentario $sujeito,
        Utilizador $causador,
        string $acao,
    ): void {
        $this->obterIdentificadorPersistido(
            $sujeito,
            'O sujeito da interação',
        );

        $identificadorCausador =
            $this->obterIdentificadorPersistido(
                $causador,
                'O utilizador responsável pela interação',
            );

        $acaoNormalizada =
            $this->normalizarAcao(
                $acao,
            );

        $notificacao =
            new NotificacaoInteracaoUtilizador(
                $sujeito,
                $causador,
                $acaoNormalizada,
            );

        Utilizador::query()
            ->selecionaveis()
            ->whereKeyNot(
                $identificadorCausador,
            )
            ->reorder(
                'utilizadores.id',
            )
            ->chunkById(
                self::TAMANHO_LOTE,
                function (
                    ColecaoEloquent $destinatarios,
                ) use (
                    $notificacao,
                ): void {
                    if ($destinatarios->isEmpty()) {
                        return;
                    }

                    $this->notificacoes->send(
                        $destinatarios,
                        $notificacao,
                    );
                },
                'utilizadores.id',
                'id',
            );
    }

    /**
     * Obtém o identificador de um modelo persistido.
     *
     * Apenas são aceites identificadores inteiros positivos ou
     * representações textuais compostas exclusivamente por algarismos.
     *
     * @param  Model  $modelo  Modelo recebido.
     * @param  string  $designacao  Designação utilizada na mensagem.
     * @return int Identificador persistido.
     *
     * @throws InvalidArgumentException Quando o modelo não está persistido ou
     *                                  não possui um identificador válido.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    private function obterIdentificadorPersistido(
        Model $modelo,
        string $designacao,
    ): int {
        if (! $modelo->exists) {
            throw new InvalidArgumentException(
                sprintf(
                    '%s deve estar persistido.',
                    $designacao,
                ),
            );
        }

        $identificador =
            $modelo->getKey();

        if (
            is_int($identificador)
            && $identificador > 0
        ) {
            return $identificador;
        }

        if (! is_string($identificador)) {
            throw new InvalidArgumentException(
                sprintf(
                    '%s deve possuir um identificador válido.',
                    $designacao,
                ),
            );
        }

        $identificadorNormalizado =
            trim(
                $identificador,
            );

        if (
            $identificadorNormalizado === ''
            || ! ctype_digit(
                $identificadorNormalizado,
            )
            || (int) $identificadorNormalizado < 1
        ) {
            throw new InvalidArgumentException(
                sprintf(
                    '%s deve possuir um identificador válido.',
                    $designacao,
                ),
            );
        }

        return (int) $identificadorNormalizado;
    }

    /**
     * Normaliza a ação realizada.
     *
     * Espaços consecutivos, tabulações e quebras de linha são convertidos num
     * único espaço. A ação é convertida para minúsculas para corresponder ao
     * contrato utilizado pelas notificações.
     *
     * O limite máximo definitivo continua a ser validado pela própria
     * notificação, evitando duplicar esse contrato neste serviço.
     *
     * @param  string  $acao  Ação recebida.
     * @return string Ação normalizada.
     *
     * @throws InvalidArgumentException Quando a ação não contém texto UTF-8
     *                                  válido, possui caracteres de controlo
     *                                  ou fica vazia depois da normalização.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    private function normalizarAcao(
        string $acao,
    ): string {
        if (
            preg_match(
                '//u',
                $acao,
            ) !== 1
        ) {
            throw new InvalidArgumentException(
                'A ação da interação contém texto inválido.',
            );
        }

        if (
            preg_match(
                '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/',
                $acao,
            ) === 1
        ) {
            throw new InvalidArgumentException(
                'A ação da interação contém caracteres inválidos.',
            );
        }

        $acaoNormalizada =
            preg_replace(
                '/\s+/u',
                ' ',
                mb_strtolower(
                    trim(
                        $acao,
                    ),
                ),
            );

        if (
            ! is_string($acaoNormalizada)
            || $acaoNormalizada === ''
        ) {
            throw new InvalidArgumentException(
                'A ação da interação não pode estar vazia.',
            );
        }

        return $acaoNormalizada;
    }
}
