<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\MetalThursday;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Notifica um utilizador quando é nomeado para preparar a próxima
 * MetalThursday.
 *
 * A notificação é sempre guardada na base de dados e pode também ser enviada
 * por e-mail, conforme as permissões do destinatário.
 *
 * @since 1.0.0
 *
 * @version 3.0.0
 */
final class NotificacaoUtilizadorNomeado extends NotificacaoAplicacao
{
    /**
     * Identificador da permissão que autoriza todas as comunicações.
     *
     * @var string
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const PERMISSAO_TODAS = 'todas';

    /**
     * Identificador da permissão relativa aos alertas de nomeação.
     *
     * @var string
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const PERMISSAO_ALERTAS_NOMEACAO =
        'alertas_nomeacao';

    /**
     * Cria a notificação.
     *
     * @param  MetalThursday  $metalThursday  MetalThursday onde ocorreu a
     *                                        nomeação.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function __construct(
        private readonly MetalThursday $metalThursday,
    ) {
        $this->afterCommit();
    }

    /**
     * Determina se a notificação deve ser enviada por e-mail.
     *
     * @param  Utilizador  $utilizador  Utilizador destinatário.
     * @return bool Verdadeiro quando o envio está autorizado.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
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
     *
     * @version 3.0.0
     */
    public function toArray(
        Utilizador $utilizador,
    ): array {
        return [
            'tipo' => 'utilizador_nomeado',

            'identificador_metal_thursday' => (int) $this->metalThursday->getKey(),

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
     *
     * @version 2.0.0
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
     *
     * @version 2.0.0
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
     *
     * @version 2.0.0
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
     *
     * @version 3.0.0
     */
    protected function obterUrlAcao(
        Utilizador $utilizador,
    ): ?string {
        return route(
            'metal-thursday.detalhes',
            [
                'metalThursday' => $this->metalThursday,
            ],
        );
    }

    /**
     * Obtém a mensagem principal da notificação.
     *
     * @return string Mensagem construída.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function obterMensagem(): string
    {
        $nomeAutor =
            $this->obterNomeAutor();

        $prazo =
            $this->obterPrazo();

        if ($prazo === null) {
            return sprintf(
                'Foste nomeado por %s! Prepara a tua próxima MetalThursday.',
                $nomeAutor,
            );
        }

        return sprintf(
            'Foste nomeado por %s! Prepara e publica a tua MetalThursday até quinta-feira, dia %s.',
            $nomeAutor,
            $prazo,
        );
    }

    /**
     * Obtém o nome do utilizador que realizou a nomeação.
     *
     * @return string Nome do autor ou valor alternativo.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function obterNomeAutor(): string
    {
        $this->metalThursday->loadMissing([
            'autor',
        ]);

        $autor =
            $this->metalThursday->autor;

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
     * @return string|null Data formatada ou nulo quando a data original não
     *                     está disponível.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function obterPrazo(): ?string
    {
        $data =
            $this->metalThursday->data;

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
}
