<?php

declare(strict_types=1);

namespace Tests\Feature\Notifications;

use App\Enumeracoes\AcaoAcessoUtilizador;
use App\Models\Autenticacao\Utilizador;
use App\Notifications\NotificacaoEstadoAcessoUtilizador;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa a comunicação transacional de alterações administrativas do acesso.
 *
 * Estas comunicações pertencem ao estado da conta e não às preferências
 * opcionais de notificações da MetalThursday.
 *
 * @since 2.0.0
 */
final class NotificacaoEstadoAcessoUtilizadorTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Confirma a comunicação de uma suspensão.
     *
     * @since 2.0.0
     */
    #[Test]
    public function suspensao_e_enviada_por_email_e_inclui_o_motivo(): void
    {
        $utilizador =
            Utilizador::factory()
                ->create([
                    'nome' => 'João Teste',
                ]);

        $notificacao =
            new NotificacaoEstadoAcessoUtilizador(
                AcaoAcessoUtilizador::Suspensao,
                'Incumprimento reiterado das regras.',
            );

        self::assertSame(
            [
                'mail',
            ],
            $notificacao->via(
                $utilizador,
            ),
        );

        $mensagem =
            $notificacao->toMail(
                $utilizador,
            );

        self::assertSame(
            'MetalThursday — Acesso suspenso',
            $mensagem->subject,
        );

        self::assertSame(
            'Olá João!',
            $mensagem->greeting,
        );

        self::assertContains(
            'O acesso à tua conta MetalThursday foi suspenso.',
            $mensagem->introLines,
        );

        self::assertContains(
            'Motivo: Incumprimento reiterado das regras.',
            $mensagem->introLines,
        );

        self::assertContains(
            'Todas as sessões da tua conta foram encerradas.',
            $mensagem->introLines,
        );

        self::assertNull(
            $mensagem->actionText,
        );

        self::assertNull(
            $mensagem->actionUrl,
        );
    }

    /**
     * Confirma a comunicação de uma reativação.
     *
     * @since 2.0.0
     */
    #[Test]
    public function reativacao_e_enviada_por_email_e_permite_iniciar_sessao(): void
    {
        $utilizador =
            Utilizador::factory()
                ->create([
                    'nome' => 'Maria Teste',
                ]);

        $notificacao =
            new NotificacaoEstadoAcessoUtilizador(
                AcaoAcessoUtilizador::Reativacao,
            );

        self::assertSame(
            [
                'mail',
            ],
            $notificacao->via(
                $utilizador,
            ),
        );

        $mensagem =
            $notificacao->toMail(
                $utilizador,
            );

        self::assertSame(
            'MetalThursday — Acesso reativado',
            $mensagem->subject,
        );

        self::assertSame(
            'Olá Maria!',
            $mensagem->greeting,
        );

        self::assertContains(
            'O acesso à tua conta MetalThursday foi reativado.',
            $mensagem->introLines,
        );

        self::assertContains(
            'Podes voltar a iniciar sessão normalmente.',
            $mensagem->introLines,
        );

        self::assertSame(
            'Iniciar sessão',
            $mensagem->actionText,
        );

        self::assertSame(
            route(
                'login',
            ),
            $mensagem->actionUrl,
        );
    }

    /**
     * Confirma que a comunicação aguarda a confirmação das transações abertas.
     *
     * @since 2.0.0
     */
    #[Test]
    public function notificacao_aguarda_commit_da_transacao(): void
    {
        $notificacao =
            new NotificacaoEstadoAcessoUtilizador(
                AcaoAcessoUtilizador::Reativacao,
            );

        self::assertTrue(
            $notificacao->afterCommit,
        );
    }
}
