<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\MetalThursday;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use InvalidArgumentException;

/**
 * Notifica um utilizador quando é nomeado para preparar a próxima
 * MetalThursday.
 *
 * A notificação guarda apenas um retrato de valores escalares obtidos no
 * momento da nomeação. O processamento posterior da fila não depende da
 * existência atual do modelo nem executa consultas para reconstruir a
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
     * Identificador da MetalThursday onde ocorreu a nomeação.
     *
     * @since 2.0.0
     */
    private readonly int $identificadorMetalThursday;

    /**
     * Nome do utilizador responsável pela nomeação.
     *
     * @since 2.0.0
     */
    private readonly string $nomeAutor;

    /**
     * Prazo apresentado ao utilizador nomeado.
     *
     * @since 2.0.0
     */
    private readonly ?string $prazo;

    /**
     * Cria a notificação e captura os dados necessários para a fila.
     *
     * @param  MetalThursday  $metalThursday  MetalThursday onde ocorreu a
     *                                        nomeação.
     *
     * @throws InvalidArgumentException Quando a MetalThursday não está
     *                                  persistida ou não possui um
     *                                  identificador válido.
     *
     * @since 1.0.0
     */
    public function __construct(
        MetalThursday $metalThursday,
    ) {
        $this->identificadorMetalThursday =
            $this->obterIdentificadorMetalThursday(
                $metalThursday,
            );

        $metalThursday->loadMissing([
            'autor:id,nome',
        ]);

        $this->nomeAutor =
            $this->obterNomeAutor(
                $metalThursday,
            );

        $this->prazo =
            $this->obterPrazo(
                $metalThursday->data,
            );

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
     *     identificador_metal_thursday: int,
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
        return 'Ver MetalThursday';
    }

    /**
     * Obtém o endereço da MetalThursday onde ocorreu a nomeação.
     *
     * @param  Utilizador  $utilizador  Utilizador destinatário.
     * @return string Endereço da MetalThursday.
     *
     * @since 1.0.0
     */
    protected function obterUrlAcao(
        Utilizador $utilizador,
    ): ?string {
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
        if ($this->prazo === null) {
            return sprintf(
                'Foste nomeado por %s! Prepara a tua próxima MetalThursday.',
                $this->nomeAutor,
            );
        }

        return sprintf(
            'Foste nomeado por %s! Prepara e publica a tua MetalThursday até quinta-feira, dia %s.',
            $this->nomeAutor,
            $this->prazo,
        );
    }

    /**
     * Obtém o nome do utilizador que realizou a nomeação.
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
     * Obtém a data limite da próxima MetalThursday.
     *
     * A data corresponde à primeira quinta-feira posterior à data da
     * MetalThursday onde ocorreu a nomeação.
     *
     * @param  mixed  $data  Data original da MetalThursday.
     * @return string|null Data formatada ou nulo quando a data original não
     *                     está disponível.
     *
     * @since 2.0.0
     */
    private function obterPrazo(
        mixed $data,
    ): ?string {
        if (! $data instanceof CarbonInterface) {
            return null;
        }

        return CarbonImmutable::instance(
            $data,
        )
            ->next(
                CarbonImmutable::THURSDAY,
            )
            ->format(
                'd/m/Y',
            );
    }

    /**
     * Obtém o identificador persistido da MetalThursday.
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
