<?php

declare(strict_types=1);

namespace Tests\Feature\Notifications;

use App\Enumeracoes\PapelUtilizador;
use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\MetalThursday;
use App\Notifications\NotificacaoInteracaoUtilizador;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa a revalidação de destinatários no momento da entrega das interações.
 *
 * @since 2.0.0
 */
final class NotificacaoInteracaoUtilizadorEntregaTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Um destinatário ativo continua a poder receber a notificação.
     *
     * @since 2.0.0
     */
    #[Test]
    public function permite_entrega_a_destinatario_ativo(): void
    {
        $causador = Utilizador::factory()->create();
        $destinatario = Utilizador::factory()->create();

        $metalThursday = MetalThursday::factory()
            ->comAutor($causador)
            ->comProximoNomeado($causador)
            ->create();

        $notificacao = new NotificacaoInteracaoUtilizador(
            $metalThursday,
            $causador,
            'comentou',
        );

        self::assertTrue(
            $notificacao->shouldSend($destinatario, 'database'),
        );

        self::assertTrue(
            $notificacao->shouldSend($destinatario, 'mail'),
        );
    }

    /**
     * Uma suspensão posterior ao enfileiramento bloqueia ambos os canais,
     * mesmo quando o modelo do destinatário ainda contém o estado antigo.
     *
     * @since 2.0.0
     */
    #[Test]
    public function impede_entrega_apos_suspensao_com_modelo_desatualizado(): void
    {
        $causador = Utilizador::factory()->create();
        $destinatario = Utilizador::factory()->create();

        $metalThursday = MetalThursday::factory()
            ->comAutor($causador)
            ->comProximoNomeado($causador)
            ->create();

        $notificacao = new NotificacaoInteracaoUtilizador(
            $metalThursday,
            $causador,
            'comentou',
        );

        $destinatarioDesatualizado = $destinatario->fresh();

        self::assertInstanceOf(
            Utilizador::class,
            $destinatarioDesatualizado,
        );

        $responsavel = Utilizador::factory()
            ->comPapel(PapelUtilizador::SuperAdministrador)
            ->create();

        $destinatario->forceFill([
            'suspenso_em' => now(),
            'motivo_suspensao' => 'Suspensão para teste de entrega em fila.',
            'suspenso_por_id' => $responsavel->getKey(),
        ])->saveOrFail();

        self::assertTrue(
            $destinatarioDesatualizado->temAcessoAtivo(),
        );

        self::assertFalse(
            $notificacao->shouldSend($destinatarioDesatualizado, 'database'),
        );

        self::assertFalse(
            $notificacao->shouldSend($destinatarioDesatualizado, 'mail'),
        );

        $destinatario->forceFill([
            'suspenso_em' => null,
            'motivo_suspensao' => null,
            'suspenso_por_id' => null,
        ])->saveOrFail();

        self::assertTrue(
            $notificacao->shouldSend($destinatarioDesatualizado, 'database'),
        );
    }

    /**
     * Uma conta entretanto eliminada nunca recebe a notificação.
     *
     * @since 2.0.0
     */
    #[Test]
    public function impede_entrega_a_destinatario_eliminado(): void
    {
        $causador = Utilizador::factory()->create();
        $destinatario = Utilizador::factory()->create();

        $metalThursday = MetalThursday::factory()
            ->comAutor($causador)
            ->comProximoNomeado($causador)
            ->create();

        $notificacao = new NotificacaoInteracaoUtilizador(
            $metalThursday,
            $causador,
            'comentou',
        );

        $destinatario->deleteOrFail();

        self::assertFalse(
            $notificacao->shouldSend($destinatario, 'database'),
        );
    }
}
