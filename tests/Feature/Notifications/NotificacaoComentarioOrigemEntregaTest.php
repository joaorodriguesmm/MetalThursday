<?php

declare(strict_types=1);

namespace Tests\Feature\Notifications;

use App\Models\Autenticacao\Utilizador;
use App\Models\Interacoes\Comentario;
use App\Models\MetalThursday\Edicao;
use App\Models\MetalThursday\MetalThursday;
use App\Models\MetalThursday\SeccaoMetalThursday;
use App\Notifications\NotificacaoInteracaoUtilizador;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Garante que os avisos de comentários e respostas pertencem a ações válidas.
 *
 * @since 2.0.0
 */
final class NotificacaoComentarioOrigemEntregaTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function comentario_eliminado_antes_da_entrega_e_bloqueado(): void
    {
        [$publicacao, $causador, $destinatario] = $this->criarCenario();
        $comentario = $this->criarComentario($publicacao, $causador);
        $notificacao = new NotificacaoInteracaoUtilizador($publicacao, $causador, 'comentou', $comentario);

        self::assertTrue($notificacao->shouldSend($destinatario, 'database'));
        $comentario->deleteOrFail();
        self::assertFalse($notificacao->shouldSend($destinatario, 'database'));
        self::assertFalse($notificacao->shouldSend($destinatario, 'mail'));
    }

    #[Test]
    public function comentario_com_conteudo_eliminado_e_bloqueado(): void
    {
        [$publicacao, $causador, $destinatario] = $this->criarCenario();
        $comentario = $this->criarComentario($publicacao, $causador);
        $this->criarComentario($publicacao, $destinatario, $comentario);
        $notificacao = new NotificacaoInteracaoUtilizador($publicacao, $causador, 'comentou', $comentario);

        self::assertTrue($notificacao->shouldSend($destinatario, 'database'));
        $comentario->conteudo_eliminado_em = now();
        $comentario->saveOrFail();
        self::assertFalse($notificacao->shouldSend($destinatario, 'database'));
    }

    #[Test]
    public function resposta_eliminada_antes_da_entrega_e_bloqueada(): void
    {
        [$publicacao, $causador, $destinatario] = $this->criarCenario();
        $pai = $this->criarComentario($publicacao, $destinatario);
        $resposta = $this->criarComentario($publicacao, $causador, $pai);
        $notificacao = new NotificacaoInteracaoUtilizador($pai, $causador, 'respondeu', $resposta);

        self::assertTrue($notificacao->shouldSend($destinatario, 'database'));
        $resposta->deleteOrFail();
        self::assertFalse($notificacao->shouldSend($destinatario, 'database'));
    }

    #[Test]
    public function comentario_e_resposta_validos_sao_entregues(): void
    {
        [$publicacao, $causador, $destinatario] = $this->criarCenario();
        $comentario = $this->criarComentario($publicacao, $causador);
        $pai = $this->criarComentario($publicacao, $destinatario);
        $resposta = $this->criarComentario($publicacao, $causador, $pai);
        $notificacaoComentario = new NotificacaoInteracaoUtilizador($publicacao, $causador, 'comentou', $comentario);
        $notificacaoResposta = new NotificacaoInteracaoUtilizador($pai, $causador, 'respondeu', $resposta);

        self::assertTrue($notificacaoComentario->shouldSend($destinatario, 'database'));
        self::assertTrue($notificacaoResposta->shouldSend($destinatario, 'database'));
    }

    #[Test]
    public function comentario_de_seccao_valida_continua_a_ser_entregue(): void
    {
        [$publicacao, $causador, $destinatario] = $this->criarCenario();
        $seccao = SeccaoMetalThursday::factory()->paraMetalThursday($publicacao)->create();
        $comentario = $seccao->comentarios()->create([
            'utilizador_id' => $causador->getKey(),
            'conteudo' => 'Comentário na secção.',
            'comentario_pai_id' => null,
        ]);
        $notificacao = new NotificacaoInteracaoUtilizador($seccao, $causador, 'comentou', $comentario);

        self::assertTrue($notificacao->shouldSend($destinatario, 'database'));
        $comentario->deleteOrFail();
        self::assertFalse($notificacao->shouldSend($destinatario, 'database'));
    }

    #[Test]
    public function autor_ou_sujeito_incorreto_nao_validam_notificacao(): void
    {
        [$publicacao, $causador, $destinatario] = $this->criarCenario();
        $comentarioAlheio = $this->criarComentario($publicacao, $destinatario);
        $notificacao = new NotificacaoInteracaoUtilizador(
            $publicacao, $causador, 'comentou', $comentarioAlheio,
        );

        self::assertFalse($notificacao->shouldSend($destinatario, 'database'));
    }

    #[Test]
    public function comentario_recriado_nao_reativa_notificacao_antiga(): void
    {
        [$publicacao, $causador, $destinatario] = $this->criarCenario();
        $antigo = $this->criarComentario($publicacao, $causador);
        $notificacaoAntiga = new NotificacaoInteracaoUtilizador($publicacao, $causador, 'comentou', $antigo);
        $antigo->deleteOrFail();
        $novo = $this->criarComentario($publicacao, $causador);
        $notificacaoNova = new NotificacaoInteracaoUtilizador($publicacao, $causador, 'comentou', $novo);

        self::assertNotSame($antigo->getKey(), $novo->getKey());
        self::assertFalse($notificacaoAntiga->shouldSend($destinatario, 'database'));
        self::assertTrue($notificacaoNova->shouldSend($destinatario, 'database'));
    }

    #[Test]
    public function resposta_recriada_nao_reativa_notificacao_antiga(): void
    {
        [$publicacao, $causador, $destinatario] = $this->criarCenario();
        $pai = $this->criarComentario($publicacao, $destinatario);
        $antiga = $this->criarComentario($publicacao, $causador, $pai);
        $notificacaoAntiga = new NotificacaoInteracaoUtilizador($pai, $causador, 'respondeu', $antiga);
        $antiga->deleteOrFail();
        $nova = $this->criarComentario($publicacao, $causador, $pai);
        $notificacaoNova = new NotificacaoInteracaoUtilizador($pai, $causador, 'respondeu', $nova);

        self::assertNotSame($antiga->getKey(), $nova->getKey());
        self::assertFalse($notificacaoAntiga->shouldSend($destinatario, 'database'));
        self::assertTrue($notificacaoNova->shouldSend($destinatario, 'database'));
    }

    #[Test]
    public function comentario_respondido_com_conteudo_eliminado_cancela_aviso_de_resposta(): void
    {
        [$publicacao, $causador, $destinatario] = $this->criarCenario();
        $pai = $this->criarComentario($publicacao, $destinatario);
        $resposta = $this->criarComentario($publicacao, $causador, $pai);
        $notificacao = new NotificacaoInteracaoUtilizador($pai, $causador, 'respondeu', $resposta);

        self::assertTrue($notificacao->shouldSend($destinatario, 'database'));
        $pai->conteudo_eliminado_em = now();
        $pai->saveOrFail();
        self::assertFalse($notificacao->shouldSend($destinatario, 'database'));
    }

    #[Test]
    public function comentario_sem_origem_nao_e_entregue(): void
    {
        [$publicacao, $causador, $destinatario] = $this->criarCenario();
        $notificacao = new NotificacaoInteracaoUtilizador($publicacao, $causador, 'comentou');

        self::assertFalse($notificacao->shouldSend($destinatario, 'database'));
    }

    /**
     * @return array{MetalThursday, Utilizador, Utilizador}
     */
    private function criarCenario(): array
    {
        $hoje = CarbonImmutable::now((string) config('app.timezone'))->startOfDay();
        $edicao = Edicao::factory()->comPeriodo(
            $hoje->subMonths(2)->startOfMonth(),
            $hoje->addMonths(2)->endOfMonth(),
        )->create();
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
            'conteudo' => 'Comentário de teste válido.',
            'comentario_pai_id' => $pai?->getKey(),
        ]);
    }
}
