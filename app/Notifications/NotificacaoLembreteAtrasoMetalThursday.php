<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enumeracoes\IdentificadorPermissaoEmail;
use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\ReservaMetalThursday;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use InvalidArgumentException;

/**
 * Recorda por e-mail o responsável de uma reserva de MetalThursday que
 * continua pendente depois da respetiva data.
 *
 * A notificação é exclusivamente uma comunicação periódica por e-mail. Não é
 * guardada na base de dados para evitar criar uma nova notificação interna em
 * cada dia enquanto o mesmo atraso persistir.
 *
 * Apenas valores escalares são conservados para que o processamento posterior
 * da fila não dependa da existência atual da reserva.
 *
 * @since 2.0.0
 */
final class NotificacaoLembreteAtrasoMetalThursday extends NotificacaoAplicacao
{
    /**
     * Identificador da reserva em atraso.
     *
     * @since 2.0.0
     */
    private readonly int $identificadorReserva;

    /**
     * Data originalmente reservada.
     *
     * @since 2.0.0
     */
    private readonly string $prazo;

    /**
     * Cria o lembrete de uma reserva em atraso.
     *
     * @param  ReservaMetalThursday  $reserva  Reserva pendente.
     * @param  CarbonInterface  $referencia  Data utilizada para determinar o
     *                                       atraso.
     *
     * @throws InvalidArgumentException Quando a reserva não representa uma
     *                                  tarefa válida em atraso.
     *
     * @since 2.0.0
     */
    public function __construct(
        ReservaMetalThursday $reserva,
        CarbonInterface $referencia,
    ) {
        $dataReferencia =
            $this->normalizarDataReferencia(
                $referencia,
            );

        $this->validarReserva(
            $reserva,
            $dataReferencia,
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
     * Impede a criação diária de notificações internas repetidas.
     *
     * @return bool Falso porque o lembrete é exclusivamente enviado por
     *              e-mail.
     *
     * @since 2.0.0
     */
    protected function deveGuardarNaBaseDados(): bool
    {
        return false;
    }

    /**
     * Determina se o utilizador autorizou o lembrete diário de atrasos.
     *
     * @param  Utilizador  $utilizador  Utilizador destinatário.
     * @return bool Verdadeiro quando o envio por e-mail está autorizado.
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
                IdentificadorPermissaoEmail::LembreteDiarioAtrasos->value,
            );
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
        return 'Lembrete: tens uma MetalThursday em atraso';
    }

    /**
     * Obtém a mensagem principal do lembrete.
     *
     * @param  Utilizador  $utilizador  Utilizador destinatário.
     * @return string Conteúdo principal.
     *
     * @since 2.0.0
     */
    protected function obterLinhaMensagem(
        Utilizador $utilizador,
    ): string {
        return sprintf(
            'A MetalThursday prevista para %s continua por preparar e publicar.',
            $this->prazo,
        );
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
     * Normaliza a referência para a data civil da aplicação.
     *
     * @param  CarbonInterface  $referencia  Momento recebido.
     * @return CarbonImmutable Data normalizada.
     *
     * @since 2.0.0
     */
    private function normalizarDataReferencia(
        CarbonInterface $referencia,
    ): CarbonImmutable {
        return CarbonImmutable::instance(
            $referencia,
        )
            ->setTimezone(
                (string) config(
                    'app.timezone',
                ),
            )
            ->startOfDay();
    }

    /**
     * Valida a reserva utilizada pelo lembrete.
     *
     * @param  ReservaMetalThursday  $reserva  Reserva recebida.
     * @param  CarbonImmutable  $dataReferencia  Dia utilizado para determinar
     *                                           o atraso.
     *
     * @throws InvalidArgumentException Quando a reserva não está persistida,
     *                                  já foi cumprida, não possui responsável
     *                                  ou ainda não está em atraso.
     *
     * @since 2.0.0
     */
    private function validarReserva(
        ReservaMetalThursday $reserva,
        CarbonImmutable $dataReferencia,
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

        $data =
            $reserva->data;

        if (! $data instanceof CarbonInterface) {
            throw new InvalidArgumentException(
                'A reserva do lembrete deve possuir uma data válida.',
            );
        }

        if (
            ! CarbonImmutable::instance(
                $data,
            )
                ->startOfDay()
                ->lessThan(
                    $dataReferencia,
                )
        ) {
            throw new InvalidArgumentException(
                'A reserva do lembrete deve estar em atraso.',
            );
        }
    }
}
