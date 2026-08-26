<?php

declare(strict_types=1);

namespace Tests\Feature\Notifications;

use App\Enumeracoes\PapelUtilizador;
use App\Models\Autenticacao\Utilizador;
use App\Notifications\NotificacaoPapelUtilizadorAlterado;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa a comunicação transacional de alterações do papel de um utilizador.
 *
 * Esta comunicação pertence à segurança e autorização da conta e é
 * independente das preferências opcionais da MetalThursday.
 *
 * @since 2.0.0
 */
final class NotificacaoPapelUtilizadorAlteradoTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Confirma a comunicação da alteração para um utilizador com acesso ativo.
     *
     * @since 2.0.0
     */
    #[Test]
    public function alteracao_de_papel_e_comunicada_por_email(): void
    {
        $utilizador =
            Utilizador::factory()
                ->create([
                    'nome' => 'João Teste',
                ]);

        $notificacao =
            new NotificacaoPapelUtilizadorAlterado(
                PapelUtilizador::Utilizador,
                PapelUtilizador::Administrador,
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
            'MetalThursday — Papel da conta alterado',
            $mensagem->subject,
        );

        self::assertSame(
            'Olá João!',
            $mensagem->greeting,
        );

        self::assertContains(
            'O papel da tua conta MetalThursday foi alterado.',
            $mensagem->introLines,
        );

        self::assertContains(
            'Papel anterior: Utilizador.',
            $mensagem->introLines,
        );

        self::assertContains(
            'Novo papel: Administrador.',
            $mensagem->introLines,
        );

        self::assertContains(
            'Por motivos de segurança, todas as sessões da tua conta foram encerradas.',
            $mensagem->introLines,
        );

        self::assertContains(
            'Inicia novamente sessão para aplicar as novas permissões.',
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
     * Confirma que uma conta suspensa não recebe uma ação de autenticação.
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
            new NotificacaoPapelUtilizadorAlterado(
                PapelUtilizador::Administrador,
                PapelUtilizador::Utilizador,
            );

        $mensagem =
            $notificacao->toMail(
                $utilizador,
            );

        self::assertContains(
            'Papel anterior: Administrador.',
            $mensagem->introLines,
        );

        self::assertContains(
            'Novo papel: Utilizador.',
            $mensagem->introLines,
        );

        self::assertNotContains(
            'Inicia novamente sessão para aplicar as novas permissões.',
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
            new NotificacaoPapelUtilizadorAlterado(
                PapelUtilizador::Utilizador,
                PapelUtilizador::Administrador,
            );

        self::assertTrue(
            $notificacao->afterCommit,
        );
    }
}
