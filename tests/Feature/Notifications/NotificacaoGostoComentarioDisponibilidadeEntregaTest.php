<?php

declare(strict_types=1);

namespace Tests\Feature\Notifications;

use App\Models\Autenticacao\Utilizador;
use App\Models\Interacoes\Comentario;
use App\Models\Interacoes\Gosto;
use App\Models\MetalThursday\Edicao;
use App\Models\MetalThursday\MetalThursday;
use App\Models\MetalThursday\SeccaoMetalThursday;
use App\Notifications\NotificacaoInteracaoUtilizador;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Revalida a disponibilidade do comentário que recebeu um gosto.
 *
 * @since 2.0.0
 */
final class NotificacaoGostoComentarioDisponibilidadeEntregaTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function comentario_eliminado_bloqueia_gosto_que_permanece_persistido(): void
    {
        [$publicacao, $causador, $destinatario] = $this->criarCenario();
        $comentario = $this->criarComentario($publicacao, $destinatario);
        $gosto = $comentario->gostos()->create([
            'utilizador_id' => $causador->getKey(),
        ]);
        $notificacao = new NotificacaoInteracaoUtilizador($comentario, $causador, 'gostou');

        self::assertTrue($notificacao->shouldSend($destinatario, 'database'));
        $comentario->deleteOrFail();
        self::assertTrue(Gosto::query()->whereKey($gosto->getKey())->exists());
        self::assertFalse($notificacao->shouldSend($destinatario, 'database'));
        self::assertFalse($notificacao->shouldSend($destinatario, 'mail'));
    }

    #[Test]
    public function comentario_com_conteudo_removido_bloqueia_gosto_persistido(): void
    {
        [$publicacao, $causador, $destinatario] = $this->criarCenario();
        $comentario = $this->criarComentario($publicacao, $destinatario);
        $resposta = $this->criarComentario($publicacao, $causador, $comentario);
        $gosto = $comentario->gostos()->create([
            'utilizador_id' => $causador->getKey(),
        ]);
        $notificacao = new NotificacaoInteracaoUtilizador($comentario, $causador, 'gostou');

        self::assertTrue($notificacao->shouldSend($destinatario, 'database'));
        $comentario->conteudo_eliminado_em = now();
        $comentario->saveOrFail();
        self::assertNotNull($resposta->fresh());
        self::assertTrue(Gosto::query()->whereKey($gosto->getKey())->exists());
        self::assertFalse($notificacao->shouldSend($destinatario, 'database'));
    }

    #[Test]
    public function comentario_valido_mantem_entrega_do_gosto(): void
    {
        [$publicacao, $causador, $destinatario] = $this->criarCenario();
        $comentario = $this->criarComentario($publicacao, $destinatario);
        $comentario->gostos()->create([
            'utilizador_id' => $causador->getKey(),
        ]);
        $notificacao = new NotificacaoInteracaoUtilizador($comentario, $causador, 'gostou');

        self::assertTrue($notificacao->shouldSend($destinatario, 'database'));
    }

    #[Test]
    public function resposta_valida_mantem_entrega_do_gosto(): void
    {
        [$publicacao, $causador, $destinatario] = $this->criarCenario();
        $pai = $this->criarComentario($publicacao, $destinatario);
        $resposta = $this->criarComentario($publicacao, $destinatario, $pai);
        $resposta->gostos()->create([
            'utilizador_id' => $causador->getKey(),
        ]);
        $notificacao = new NotificacaoInteracaoUtilizador($resposta, $causador, 'gostou');

        self::assertTrue($notificacao->shouldSend($destinatario, 'database'));
    }

    #[Test]
    public function comentario_de_seccao_valida_mantem_entrega_do_gosto(): void
    {
        [$publicacao, $causador, $destinatario] = $this->criarCenario();
        $seccao = SeccaoMetalThursday::factory()->paraMetalThursday($publicacao)->create();
        $comentario = $seccao->comentarios()->create([
            'utilizador_id' => $destinatario->getKey(),
            'conteudo' => 'Comentário de secção válido.',
            'comentario_pai_id' => null,
        ]);
        $comentario->gostos()->create([
            'utilizador_id' => $causador->getKey(),
        ]);
        $notificacao = new NotificacaoInteracaoUtilizador($comentario, $causador, 'gostou');

        self::assertTrue($notificacao->shouldSend($destinatario, 'database'));
    }

    /**
     * @return array{MetalThursday, Utilizador, Utilizador}
     */
    private function criarCenario(): array
    {
        $hoje = CarbonImmutable::now((string) config('app.timezone'))->startOfDay();
        $edicao = Edicao::factory()
            ->comPeriodo($hoje->subMonths(2)->startOfMonth(), $hoje->addMonths(2)->endOfMonth())
            ->create();
        $causador = Utilizador::factory()->create();
        $destinatario = Utilizador::factory()->create();
        $publicacao = MetalThursday::factory()
            ->comData($hoje->subDays(3))
            ->comEdicao($edicao)
            ->comAutor($causador)
            ->comProximoNomeado($causador)
            ->create();

        return [$publicacao, $causador, $destinatario];
    }

    private function criarComentario(
        MetalThursday $publicacao,
        Utilizador $autor,
        ?Comentario $pai = null,
    ): Comentario {
        return $publicacao->comentarios()->create([
            'utilizador_id' => $autor->getKey(),
            'conteudo' => 'Comentário para testar a entrega.',
            'comentario_pai_id' => $pai?->getKey(),
        ]);
    }
}
