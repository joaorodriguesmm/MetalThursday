<?php

declare(strict_types=1);

namespace Tests\Feature\Notifications;

use App\Models\Autenticacao\Utilizador;
use App\Notifications\NotificacaoSessoesEncerradasUtilizador;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa a comunicação do encerramento administrativo das sessões.
 *
 * Esta comunicação pertence à segurança da conta e é independente das
 * preferências opcionais de notificações da MetalThursday.
 *
 * @since 2.0.0
 */
final class NotificacaoSessoesEncerradasUtilizadorTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Confirma o conteúdo e o canal da comunicação.
     *
     * @since 2.0.0
     */
    #[Test]
    public function encerramento_de_sessoes_e_comunicado_por_email(): void
    {
        $utilizador =
            Utilizador::factory()
                ->create([
                    'nome' => 'João Teste',
                ]);

        $notificacao =
            new NotificacaoSessoesEncerradasUtilizador;

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
            'MetalThursday — Sessões encerradas',
            $mensagem->subject,
        );

        self::assertSame(
            'Olá João!',
            $mensagem->greeting,
        );

        self::assertContains(
            'As sessões da tua conta MetalThursday foram encerradas administrativamente.',
            $mensagem->introLines,
        );

        self::assertContains(
            'As autenticações persistentes anteriores também foram invalidadas.',
            $mensagem->introLines,
        );

        self::assertContains(
            'Se pretenderes continuar a utilizar a MetalThursday, inicia novamente sessão.',
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
     * Confirma que um utilizador suspenso não recebe uma ação de início de sessão.
     *
     * @since 2.0.0
     */
    #[Test]
    public function utilizador_suspenso_nao_recebe_acao_de_inicio_de_sessao(): void
    {
        $responsavel =
            Utilizador::factory()
                ->create();

        $utilizador =
            Utilizador::factory()
                ->suspensoPor(
                    $responsavel,
                    'Suspensão administrativa.',
                )
                ->create([
                    'nome' => 'Maria Teste',
                ]);

        $notificacao =
            new NotificacaoSessoesEncerradasUtilizador;

        $mensagem =
            $notificacao->toMail(
                $utilizador,
            );

        self::assertContains(
            'As sessões da tua conta MetalThursday foram encerradas administrativamente.',
            $mensagem->introLines,
        );

        self::assertContains(
            'As autenticações persistentes anteriores também foram invalidadas.',
            $mensagem->introLines,
        );

        self::assertNotContains(
            'Se pretenderes continuar a utilizar a MetalThursday, inicia novamente sessão.',
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
     * Confirma que a comunicação aguarda a confirmação das transações abertas.
     *
     * @since 2.0.0
     */
    #[Test]
    public function notificacao_aguarda_commit_da_transacao(): void
    {
        $notificacao =
            new NotificacaoSessoesEncerradasUtilizador;

        self::assertTrue(
            $notificacao->afterCommit,
        );
    }
}
