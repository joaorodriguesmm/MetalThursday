<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enumeracoes\PapelUtilizador;
use App\Models\Autenticacao\Utilizador;
use InvalidArgumentException;

/**
 * Comunica uma alteração administrativa do papel de um utilizador.
 *
 * Esta comunicação pertence à segurança e autorização da conta. Por esse
 * motivo, o envio por e-mail não depende das preferências opcionais da
 * MetalThursday e não cria uma notificação na aplicação.
 *
 * @since 2.0.0
 */
final class NotificacaoPapelUtilizadorAlterado extends NotificacaoAplicacao
{
    /**
     * Cria a notificação.
     *
     * @param  PapelUtilizador  $papelAnterior  Papel anterior.
     * @param  PapelUtilizador  $papelNovo  Novo papel.
     *
     * @throws InvalidArgumentException Quando os papéis coincidem.
     *
     * @since 2.0.0
     */
    public function __construct(
        private readonly PapelUtilizador $papelAnterior,
        private readonly PapelUtilizador $papelNovo,
    ) {
        if ($this->papelAnterior === $this->papelNovo) {
            throw new InvalidArgumentException(
                'A notificação exige uma alteração efetiva do papel.',
            );
        }

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
     * A alteração administrativa do papel pertence à segurança e autorização
     * da conta, não às preferências opcionais de comunicação.
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
        return 'MetalThursday — Papel da conta alterado';
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
        return 'O papel da tua conta MetalThursday foi alterado.';
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
            sprintf(
                'Papel anterior: %s.',
                $this->papelAnterior->etiqueta(),
            ),

            sprintf(
                'Novo papel: %s.',
                $this->papelNovo->etiqueta(),
            ),

            'Por motivos de segurança, todas as sessões da tua conta foram encerradas.',
        ];

        if ($utilizador->temAcessoAtivo()) {
            $linhas[] =
                'Inicia novamente sessão para aplicar as novas permissões.';
        }

        return $linhas;
    }

    /**
     * Obtém o texto da ação.
     *
     * Utilizadores suspensos não recebem uma ação que conduza à autenticação.
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
