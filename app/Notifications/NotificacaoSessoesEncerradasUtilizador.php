<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Autenticacao\Utilizador;

/**
 * Comunica o encerramento administrativo das sessões de um utilizador.
 *
 * Esta comunicação pertence à segurança da conta. Por esse motivo, o envio
 * por e-mail não depende das preferências opcionais de comunicação da
 * MetalThursday e não cria uma notificação na aplicação.
 *
 * @since 2.0.0
 */
final class NotificacaoSessoesEncerradasUtilizador extends NotificacaoAplicacao
{
    /**
     * Cria a notificação.
     *
     * @since 2.0.0
     */
    public function __construct()
    {
        $this->afterCommit();
    }

    /**
     * Impede a persistência da comunicação na base de dados.
     *
     * @return bool Falso.
     *
     * @since 2.0.0
     */
    protected function deveGuardarNaBaseDados(): bool
    {
        return false;
    }

    /**
     * Autoriza sempre o envio por e-mail.
     *
     * O encerramento administrativo das sessões é uma comunicação de
     * segurança da conta e não uma preferência opcional da MetalThursday.
     *
     * @param  Utilizador  $utilizador  Utilizador destinatário.
     * @return bool Verdadeiro.
     *
     * @since 2.0.0
     */
    protected function deveEnviarPorEmail(
        Utilizador $utilizador,
    ): bool {
        return true;
    }

    /**
     * Obtém o assunto da mensagem.
     *
     * @param  Utilizador  $utilizador  Utilizador destinatário.
     * @return string Assunto.
     *
     * @since 2.0.0
     */
    protected function obterAssunto(
        Utilizador $utilizador,
    ): string {
        return 'MetalThursday — Sessões encerradas';
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
        return 'As sessões da tua conta MetalThursday foram encerradas administrativamente.';
    }

    /**
     * Obtém as restantes linhas da mensagem.
     *
     * @param  Utilizador  $utilizador  Utilizador destinatário.
     * @return array<int, string> Linhas adicionais.
     *
     * @since 2.0.0
     */
    protected function obterLinhasAdicionais(
        Utilizador $utilizador,
    ): array {
        $linhas = [
            'As autenticações persistentes anteriores também foram invalidadas.',
        ];

        if ($utilizador->temAcessoAtivo()) {
            $linhas[] =
                'Se pretenderes continuar a utilizar a MetalThursday, inicia novamente sessão.';
        }

        return $linhas;
    }

    /**
     * Obtém o texto da ação.
     *
     * Utilizadores suspensos não recebem uma ação que conduza ao início de sessão.
     *
     * @param  Utilizador  $utilizador  Utilizador destinatário.
     * @return string|null Texto da ação.
     *
     * @since 2.0.0
     */
    protected function obterTextoAcao(
        Utilizador $utilizador,
    ): ?string {
        return $utilizador->temAcessoAtivo()
            ? 'Iniciar sessão'
            : null;
    }

    /**
     * Obtém o endereço da ação.
     *
     * Utilizadores suspensos não recebem uma ligação para autenticação.
     *
     * @param  Utilizador  $utilizador  Utilizador destinatário.
     * @return string|null Endereço da ação.
     *
     * @since 2.0.0
     */
    protected function obterUrlAcao(
        Utilizador $utilizador,
    ): ?string {
        if (! $utilizador->temAcessoAtivo()) {
            return null;
        }

        return route(
            'login',
        );
    }
}
