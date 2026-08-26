<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enumeracoes\AcaoAcessoUtilizador;
use App\Models\Autenticacao\Utilizador;
use InvalidArgumentException;

/**
 * Comunica uma alteração administrativa do acesso de um utilizador.
 *
 * A suspensão e a reativação são acontecimentos transacionais da conta.
 * Por esse motivo, o envio por e-mail não depende das preferências opcionais
 * de comunicação da MetalThursday e não cria uma notificação na aplicação.
 *
 * @since 2.0.0
 */
final class NotificacaoEstadoAcessoUtilizador extends NotificacaoAplicacao
{
    /**
     * Ação administrativa comunicada.
     *
     * @since 2.0.0
     */
    private readonly AcaoAcessoUtilizador $acao;

    /**
     * Motivo normalizado da suspensão.
     *
     * @since 2.0.0
     */
    private readonly ?string $motivo;

    /**
     * Cria a notificação.
     *
     * @param  AcaoAcessoUtilizador  $acao  Alteração do acesso.
     * @param  string|null  $motivo  Motivo da suspensão.
     *
     * @throws InvalidArgumentException Quando uma suspensão não possui um
     *                                  motivo válido.
     *
     * @since 2.0.0
     */
    public function __construct(
        AcaoAcessoUtilizador $acao,
        ?string $motivo = null,
    ) {
        $this->acao =
            $acao;

        $this->motivo =
            $this->normalizarMotivo(
                $motivo,
            );

        if (
            $this->acao->eSuspensao()
            && $this->motivo === null
        ) {
            throw new InvalidArgumentException(
                'A notificação de suspensão exige um motivo.',
            );
        }

        $this->afterCommit();
    }

    /**
     * Impede a persistência da comunicação na base de dados.
     *
     * A suspensão impossibilita imediatamente o acesso à própria aplicação.
     * A comunicação utiliza, por isso, exclusivamente o endereço de e-mail.
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
     * Autoriza sempre o canal de e-mail.
     *
     * Esta comunicação pertence ao estado e à segurança da conta, não às
     * preferências opcionais de notificações da MetalThursday.
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
        return match ($this->acao) {
            AcaoAcessoUtilizador::Suspensao => 'MetalThursday — Acesso suspenso',

            AcaoAcessoUtilizador::Reativacao => 'MetalThursday — Acesso reativado',
        };
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
        return match ($this->acao) {
            AcaoAcessoUtilizador::Suspensao => 'O acesso à tua conta MetalThursday foi suspenso.',

            AcaoAcessoUtilizador::Reativacao => 'O acesso à tua conta MetalThursday foi reativado.',
        };
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
        if ($this->acao->eReativacao()) {
            return [
                'Podes voltar a iniciar sessão normalmente.',
            ];
        }

        return [
            sprintf(
                'Motivo: %s',
                $this->motivo,
            ),

            'Todas as sessões da tua conta foram encerradas.',
        ];
    }

    /**
     * Obtém o texto da ação.
     *
     * Uma conta suspensa não recebe uma ligação para uma área à qual deixou de
     * ter acesso.
     *
     * @param  Utilizador  $utilizador  Utilizador destinatário.
     * @return string|null Texto da ação.
     *
     * @since 2.0.0
     */
    protected function obterTextoAcao(
        Utilizador $utilizador,
    ): ?string {
        return $this->acao->eReativacao()
            ? 'Iniciar sessão'
            : null;
    }

    /**
     * Obtém o endereço da ação.
     *
     * @param  Utilizador  $utilizador  Utilizador destinatário.
     * @return string|null Endereço da ação.
     *
     * @since 2.0.0
     */
    protected function obterUrlAcao(
        Utilizador $utilizador,
    ): ?string {
        return $this->acao->eReativacao()
            ? route(
                'login',
            )
            : null;
    }

    /**
     * Normaliza o motivo opcional.
     *
     * @param  string|null  $motivo  Motivo recebido.
     * @return string|null Motivo normalizado.
     *
     * @since 2.0.0
     */
    private function normalizarMotivo(
        ?string $motivo,
    ): ?string {
        if ($motivo === null) {
            return null;
        }

        $motivoNormalizado =
            preg_replace(
                '/\s+/u',
                ' ',
                trim(
                    $motivo,
                ),
            );

        if (
            ! is_string(
                $motivoNormalizado,
            )
            || $motivoNormalizado === ''
        ) {
            return null;
        }

        return $motivoNormalizado;
    }
}
