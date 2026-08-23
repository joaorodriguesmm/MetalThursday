<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\MetalThursday;
use App\Models\MetalThursday\ReservaMetalThursday;
use Carbon\CarbonInterface;
use InvalidArgumentException;

/**
 * Notifica um utilizador quando recebe uma reserva para preparar uma
 * MetalThursday.
 *
 * A notificação utiliza a reserva efetivamente criada como fonte da nomeação.
 * Quando existe uma MetalThursday de origem, a mensagem identifica o respetivo
 * autor. Quando a reserva resulta do fallback semanal, a mensagem identifica a
 * nomeação como automática.
 *
 * A notificação guarda apenas um retrato de valores escalares obtidos no
 * momento da criação. O processamento posterior da fila não depende da
 * existência atual dos modelos nem executa consultas para reconstruir a
 * mensagem.
 *
 * @since 1.0.0
 */
final class NotificacaoUtilizadorNomeado extends NotificacaoAplicacao
{
    /**
     * Identificador da permissão que autoriza todas as comunicações.
     *
     * @var string
     *
     * @since 2.0.0
     */
    private const PERMISSAO_TODAS = 'todas';

    /**
     * Identificador da permissão relativa aos alertas de nomeação.
     *
     * @var string
     *
     * @since 2.0.0
     */
    private const PERMISSAO_ALERTAS_NOMEACAO =
        'alertas_nomeacao';

    /**
     * Identificador da MetalThursday onde ocorreu a nomeação manual.
     *
     * É nulo quando a reserva foi criada automaticamente pelo fallback.
     *
     * @since 2.0.0
     */
    private readonly ?int $identificadorMetalThursday;

    /**
     * Nome do utilizador responsável pela nomeação manual.
     *
     * É nulo quando a reserva foi criada automaticamente pelo fallback.
     *
     * @since 2.0.0
     */
    private readonly ?string $nomeAutor;

    /**
     * Prazo apresentado ao utilizador nomeado.
     *
     * @since 2.0.0
     */
    private readonly string $prazo;

    /**
     * Cria a notificação e captura os dados necessários para a fila.
     *
     * @param  ReservaMetalThursday  $reserva  Reserva efetivamente atribuída ao
     *                                         utilizador.
     * @param  MetalThursday|null  $metalThursdayOrigem  MetalThursday que
     *                                                   originou uma nomeação
     *                                                   manual ou nulo numa
     *                                                   nomeação automática.
     *
     * @throws InvalidArgumentException Quando a reserva ou a MetalThursday de
     *                                  origem não estão persistidas ou possuem
     *                                  dados inválidos.
     *
     * @since 1.0.0
     */
    public function __construct(
        ReservaMetalThursday $reserva,
        ?MetalThursday $metalThursdayOrigem = null,
    ) {
        $this->validarReserva(
            $reserva,
        );

        $this->prazo =
            $this->obterPrazo(
                $reserva->data,
            );

        if ($metalThursdayOrigem instanceof MetalThursday) {
            $this->identificadorMetalThursday =
                $this->obterIdentificadorMetalThursday(
                    $metalThursdayOrigem,
                );

            $metalThursdayOrigem->loadMissing([
                'autor:id,nome',
            ]);

            $this->nomeAutor =
                $this->obterNomeAutor(
                    $metalThursdayOrigem,
                );
        } else {
            $this->identificadorMetalThursday = null;
            $this->nomeAutor = null;
        }

        $this->afterCommit();
    }

    /**
     * Determina se a notificação deve ser enviada por e-mail.
     *
     * @param  Utilizador  $utilizador  Utilizador destinatário.
     * @return bool Verdadeiro quando o envio está autorizado.
     *
     * @since 1.0.0
     */
    protected function deveEnviarPorEmail(
        Utilizador $utilizador,
    ): bool {
        return $utilizador->temPermissaoEmail(
            self::PERMISSAO_TODAS,
        )
            || $utilizador->temPermissaoEmail(
                self::PERMISSAO_ALERTAS_NOMEACAO,
            );
    }

    /**
     * Converte a notificação para o formato guardado na base de dados.
     *
     * @param  Utilizador  $utilizador  Utilizador destinatário.
     * @return array{
     *     tipo: string,
     *     identificador_metal_thursday: int|null,
     *     titulo: string,
     *     mensagem: string,
     *     ligacao: string,
     *     icone: string,
     *     cor: string
     * } Dados persistidos.
     *
     * @since 1.0.0
     */
    public function toArray(
        Utilizador $utilizador,
    ): array {
        return [
            'tipo' => 'utilizador_nomeado',

            'identificador_metal_thursday' => $this->identificadorMetalThursday,

            'titulo' => 'Foste nomeado para a próxima MetalThursday',

            'mensagem' => $this->obterMensagem(),

            'ligacao' => $this->obterUrlAcao(
                $utilizador,
            ),

            'icone' => 'bi-trophy-fill',

            'cor' => 'text-warning',
        ];
    }

    /**
     * Obtém o assunto da mensagem de e-mail.
     *
     * @param  Utilizador  $utilizador  Utilizador destinatário.
     * @return string Assunto da mensagem.
     *
     * @since 1.0.0
     */
    protected function obterAssunto(
        Utilizador $utilizador,
    ): string {
        return 'Foste nomeado para a próxima MetalThursday!';
    }

    /**
     * Obtém a linha principal da mensagem.
     *
     * @param  Utilizador  $utilizador  Utilizador destinatário.
     * @return string Conteúdo principal.
     *
     * @since 1.0.0
     */
    protected function obterLinhaMensagem(
        Utilizador $utilizador,
    ): string {
        return $this->obterMensagem();
    }

    /**
     * Obtém o texto apresentado no botão da mensagem.
     *
     * @param  Utilizador  $utilizador  Utilizador destinatário.
     * @return string Texto do botão.
     *
     * @since 1.0.0
     */
    protected function obterTextoAcao(
        Utilizador $utilizador,
    ): ?string {
        return $this->identificadorMetalThursday === null
            ? 'Preparar MetalThursday'
            : 'Ver MetalThursday';
    }

    /**
     * Obtém o endereço associado à nomeação.
     *
     * Nas nomeações manuais é apresentada a MetalThursday onde ocorreu a
     * escolha. Nas nomeações automáticas é apresentado diretamente o formulário
     * de criação correspondente à reserva pendente.
     *
     * @param  Utilizador  $utilizador  Utilizador destinatário.
     * @return string Endereço da ação.
     *
     * @since 1.0.0
     */
    protected function obterUrlAcao(
        Utilizador $utilizador,
    ): ?string {
        if ($this->identificadorMetalThursday === null) {
            return route(
                'metal-thursday.criar',
            );
        }

        return route(
            'metal-thursday.detalhes',
            [
                'metalThursday' => $this->identificadorMetalThursday,
            ],
        );
    }

    /**
     * Obtém a mensagem principal da notificação.
     *
     * @return string Mensagem construída.
     *
     * @since 2.0.0
     */
    private function obterMensagem(): string
    {
        if ($this->nomeAutor === null) {
            return sprintf(
                'Foste nomeado automaticamente! Prepara e publica a tua MetalThursday até quinta-feira, dia %s.',
                $this->prazo,
            );
        }

        return sprintf(
            'Foste nomeado por %s! Prepara e publica a tua MetalThursday até quinta-feira, dia %s.',
            $this->nomeAutor,
            $this->prazo,
        );
    }

    /**
     * Obtém o nome do utilizador que realizou a nomeação manual.
     *
     * @param  MetalThursday  $metalThursday  MetalThursday consultada.
     * @return string Nome do autor ou valor alternativo.
     *
     * @since 2.0.0
     */
    private function obterNomeAutor(
        MetalThursday $metalThursday,
    ): string {
        $autor =
            $metalThursday->autor;

        if (
            $autor instanceof Utilizador
            && is_string($autor->nome)
            && trim($autor->nome) !== ''
        ) {
            return trim(
                $autor->nome,
            );
        }

        return 'um utilizador';
    }

    /**
     * Obtém a data limite da reserva atribuída.
     *
     * @param  mixed  $data  Data da reserva.
     * @return string Data formatada.
     *
     * @throws InvalidArgumentException Quando a reserva não possui uma data
     *                                  válida.
     *
     * @since 2.0.0
     */
    private function obterPrazo(
        mixed $data,
    ): string {
        if (! $data instanceof CarbonInterface) {
            throw new InvalidArgumentException(
                'A reserva da notificação deve possuir uma data válida.',
            );
        }

        return $data->format(
            'd/m/Y',
        );
    }

    /**
     * Valida a reserva que representa a nomeação efetiva.
     *
     * @param  ReservaMetalThursday  $reserva  Reserva recebida.
     *
     * @throws InvalidArgumentException Quando a reserva não está persistida,
     *                                  não possui identificador ou não possui
     *                                  responsável.
     *
     * @since 2.0.0
     */
    private function validarReserva(
        ReservaMetalThursday $reserva,
    ): void {
        if (
            ! $reserva->exists
            || $reserva->getKey() === null
        ) {
            throw new InvalidArgumentException(
                'A reserva da notificação deve estar persistida.',
            );
        }

        if (
            ! is_numeric(
                $reserva->responsavel_id,
            )
            || (int) $reserva->responsavel_id < 1
        ) {
            throw new InvalidArgumentException(
                'A reserva da notificação deve possuir um responsável válido.',
            );
        }
    }

    /**
     * Obtém o identificador persistido da MetalThursday de origem.
     *
     * Representações textuais do identificador podem conter apenas espaços
     * ASCII exteriores. Restantes caracteres, incluindo caracteres de
     * controlo, não são removidos antes da validação.
     *
     * @param  MetalThursday  $metalThursday  MetalThursday recebida.
     * @return int Identificador persistido.
     *
     * @throws InvalidArgumentException Quando o modelo não está persistido ou
     *                                  não possui um identificador válido.
     *
     * @since 2.0.0
     */
    private function obterIdentificadorMetalThursday(
        MetalThursday $metalThursday,
    ): int {
        if (! $metalThursday->exists) {
            throw new InvalidArgumentException(
                'A MetalThursday da notificação deve estar persistida.',
            );
        }

        $identificador =
            $metalThursday->getKey();

        if (
            is_int($identificador)
            && $identificador > 0
        ) {
            return $identificador;
        }

        if (is_string($identificador)) {
            $identificadorNormalizado = trim(
                $identificador,
                ' ',
            );

            if (
                $identificadorNormalizado !== ''
                && ctype_digit(
                    $identificadorNormalizado,
                )
                && (int) $identificadorNormalizado > 0
            ) {
                return (int) $identificadorNormalizado;
            }
        }

        throw new InvalidArgumentException(
            'A MetalThursday da notificação deve possuir um identificador válido.',
        );
    }
}
