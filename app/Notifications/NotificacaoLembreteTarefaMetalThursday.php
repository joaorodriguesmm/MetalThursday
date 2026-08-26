<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enumeracoes\IdentificadorPermissaoEmail;
use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\ReservaMetalThursday;
use Carbon\CarbonInterface;
use InvalidArgumentException;

/**
 * Recorda o responsável de uma reserva pendente cuja MetalThursday deve ser
 * preparada e publicada no próprio dia.
 *
 * A notificação guarda apenas um retrato de valores escalares obtidos no
 * momento da criação. O processamento posterior da fila não depende da
 * existência atual da reserva nem executa consultas para reconstruir a
 * mensagem.
 *
 * @since 2.0.0
 */
final class NotificacaoLembreteTarefaMetalThursday extends NotificacaoAplicacao
{
    /**
     * Identificador da reserva pendente.
     *
     * @since 2.0.0
     */
    private readonly int $identificadorReserva;

    /**
     * Data limite apresentada ao utilizador.
     *
     * @since 2.0.0
     */
    private readonly string $prazo;

    /**
     * Cria a notificação e captura os dados necessários para a fila.
     *
     * @param  ReservaMetalThursday  $reserva  Reserva pendente.
     *
     * @throws InvalidArgumentException Quando a reserva não representa uma
     *                                  tarefa pendente válida.
     *
     * @since 2.0.0
     */
    public function __construct(
        ReservaMetalThursday $reserva,
    ) {
        $this->validarReserva(
            $reserva,
        );

        $this->identificadorReserva =
            (int) $reserva->getKey();

        $data =
            $reserva->data;

        if (! $data instanceof CarbonInterface) {
            throw new InvalidArgumentException(
                'A reserva do lembrete deve possuir uma data válida.',
            );
        }

        $this->prazo =
            $data->format(
                'd/m/Y',
            );

        $this->afterCommit();
    }

    /**
     * Determina se o lembrete deve ser enviado por e-mail.
     *
     * @param  Utilizador  $utilizador  Utilizador destinatário.
     * @return bool Verdadeiro quando o envio está autorizado.
     *
     * @since 2.0.0
     */
    protected function deveEnviarPorEmail(
        Utilizador $utilizador,
    ): bool {
        return $utilizador->temPermissaoEmail(
            IdentificadorPermissaoEmail::TodasNotificacoes->value,
        )
            || $utilizador->temPermissaoEmail(
                IdentificadorPermissaoEmail::LembreteDiarioTarefas->value,
            );
    }

    /**
     * Converte a notificação para o formato guardado na base de dados.
     *
     * @param  Utilizador  $utilizador  Utilizador destinatário.
     * @return array{
     *     tipo: string,
     *     identificador_reserva: int,
     *     titulo: string,
     *     mensagem: string,
     *     ligacao: string,
     *     icone: string,
     *     cor: string
     * } Dados persistidos.
     *
     * @since 2.0.0
     */
    public function toArray(
        Utilizador $utilizador,
    ): array {
        return [
            'tipo' => 'lembrete_tarefa_metal_thursday',

            'identificador_reserva' => $this->identificadorReserva,

            'titulo' => 'MetalThursday para publicar hoje',

            'mensagem' => $this->obterMensagem(),

            'ligacao' => $this->obterUrlAcao(
                $utilizador,
            ),

            'icone' => 'bi-alarm-fill',

            'cor' => 'text-warning',
        ];
    }

    /**
     * Obtém o assunto da mensagem de e-mail.
     *
     * @param  Utilizador  $utilizador  Utilizador destinatário.
     * @return string Assunto da mensagem.
     *
     * @since 2.0.0
     */
    protected function obterAssunto(
        Utilizador $utilizador,
    ): string {
        return 'Lembrete: tens uma MetalThursday para publicar hoje';
    }

    /**
     * Obtém a linha principal da mensagem.
     *
     * @param  Utilizador  $utilizador  Utilizador destinatário.
     * @return string Conteúdo principal.
     *
     * @since 2.0.0
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
     * @since 2.0.0
     */
    protected function obterTextoAcao(
        Utilizador $utilizador,
    ): ?string {
        return 'Preparar MetalThursday';
    }

    /**
     * Obtém o endereço do formulário associado à reserva.
     *
     * @param  Utilizador  $utilizador  Utilizador destinatário.
     * @return string Endereço do formulário.
     *
     * @since 2.0.0
     */
    protected function obterUrlAcao(
        Utilizador $utilizador,
    ): ?string {
        return route(
            'metal-thursday.reservas.preparar',
            [
                'reservaMetalThursday' => $this->identificadorReserva,
            ],
        );
    }

    /**
     * Obtém a mensagem principal do lembrete.
     *
     * @return string Mensagem construída.
     *
     * @since 2.0.0
     */
    private function obterMensagem(): string
    {
        return sprintf(
            'Hoje, dia %s, tens uma MetalThursday por preparar e publicar.',
            $this->prazo,
        );
    }

    /**
     * Valida a reserva utilizada pelo lembrete.
     *
     * @param  ReservaMetalThursday  $reserva  Reserva recebida.
     *
     * @throws InvalidArgumentException Quando a reserva não está persistida,
     *                                  já foi cumprida ou não possui um
     *                                  responsável válido.
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
                'A reserva do lembrete deve estar persistida.',
            );
        }

        if (! $reserva->estaPendente()) {
            throw new InvalidArgumentException(
                'A reserva do lembrete deve estar pendente.',
            );
        }

        if (
            ! is_numeric(
                $reserva->responsavel_id,
            )
            || (int) $reserva->responsavel_id < 1
        ) {
            throw new InvalidArgumentException(
                'A reserva do lembrete deve possuir um responsável válido.',
            );
        }
    }
}
