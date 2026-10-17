<?php

declare(strict_types=1);

namespace Tests\Feature\Notifications;

use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\Edicao;
use App\Models\MetalThursday\MetalThursday;
use App\Notifications\NotificacaoInteracaoUtilizador;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Evita avisos de ações retiradas enquanto aguardam na fila.
 *
 * @since 2.0.0
 */
final class NotificacaoInteracaoRevogadaEntregaTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function avaliacao_retirada_nao_e_entregue(): void
    {
        [$publicacao, $causador, $destinatario] = $this->criarCenario();
        $avaliacao = $publicacao->avaliacoes()->create([
            'utilizador_id' => $causador->getKey(),
            'pontuacao' => 8.5,
        ]);
        $notificacao = new NotificacaoInteracaoUtilizador($publicacao, $causador, 'avaliou');

        self::assertTrue($notificacao->shouldSend($destinatario, 'database'));
        $avaliacao->deleteOrFail();
        self::assertFalse($notificacao->shouldSend($destinatario, 'database'));
        self::assertFalse($notificacao->shouldSend($destinatario, 'mail'));
    }

    #[Test]
    public function gosto_retirado_nao_e_entregue(): void
    {
        [$publicacao, $causador, $destinatario] = $this->criarCenario();
        $comentario = $publicacao->comentarios()->create([
            'utilizador_id' => $destinatario->getKey(),
            'conteudo' => 'Comentário original.',
            'comentario_pai_id' => null,
        ]);
        $gosto = $comentario->gostos()->create(['utilizador_id' => $causador->getKey()]);
        $notificacao = new NotificacaoInteracaoUtilizador($comentario, $causador, 'gostou');

        self::assertTrue($notificacao->shouldSend($destinatario, 'database'));
        $gosto->deleteOrFail();
        self::assertFalse($notificacao->shouldSend($destinatario, 'database'));
    }

    #[Test]
    public function audicao_retirada_nao_e_entregue(): void
    {
        [$publicacao, $causador, $destinatario] = $this->criarCenario();
        $audicao = $publicacao->audicoes()->create(['utilizador_id' => $causador->getKey()]);
        $notificacao = new NotificacaoInteracaoUtilizador($publicacao, $causador, 'ouviu');

        self::assertTrue($notificacao->shouldSend($destinatario, 'database'));
        $audicao->deleteOrFail();
        self::assertFalse($notificacao->shouldSend($destinatario, 'database'));
    }

    #[Test]
    public function avaliacao_recriada_nao_reativa_aviso_antigo(): void
    {
        [$publicacao, $causador, $destinatario] = $this->criarCenario();
        $original = $publicacao->avaliacoes()->create([
            'utilizador_id' => $causador->getKey(), 'pontuacao' => 7.0,
        ]);
        $antiga = new NotificacaoInteracaoUtilizador($publicacao, $causador, 'avaliou');
        $identificadorOriginal = (int) $original->getKey();
        $original->deleteOrFail();
        $nova = $publicacao->avaliacoes()->create([
            'utilizador_id' => $causador->getKey(), 'pontuacao' => 9.0,
        ]);
        $recente = new NotificacaoInteracaoUtilizador($publicacao, $causador, 'avaliou');

        self::assertNotSame($identificadorOriginal, (int) $nova->getKey());
        self::assertFalse($antiga->shouldSend($destinatario, 'database'));
        self::assertTrue($recente->shouldSend($destinatario, 'database'));
    }

    #[Test]
    public function gosto_recriado_nao_reativa_aviso_antigo(): void
    {
        [$publicacao, $causador, $destinatario] = $this->criarCenario();
        $comentario = $publicacao->comentarios()->create([
            'utilizador_id' => $destinatario->getKey(),
            'conteudo' => 'Conteúdo válido.',
            'comentario_pai_id' => null,
        ]);
        $original = $comentario->gostos()->create(['utilizador_id' => $causador->getKey()]);
        $antiga = new NotificacaoInteracaoUtilizador($comentario, $causador, 'gostou');
        $identificadorOriginal = (int) $original->getKey();
        $original->deleteOrFail();
        $nova = $comentario->gostos()->create(['utilizador_id' => $causador->getKey()]);
        $recente = new NotificacaoInteracaoUtilizador($comentario, $causador, 'gostou');

        self::assertNotSame($identificadorOriginal, (int) $nova->getKey());
        self::assertFalse($antiga->shouldSend($destinatario, 'database'));
        self::assertTrue($recente->shouldSend($destinatario, 'database'));
    }

    #[Test]
    public function audicao_recriada_nao_reativa_aviso_antigo(): void
    {
        [$publicacao, $causador, $destinatario] = $this->criarCenario();
        $original = $publicacao->audicoes()->create(['utilizador_id' => $causador->getKey()]);
        $antiga = new NotificacaoInteracaoUtilizador($publicacao, $causador, 'ouviu');
        $identificadorOriginal = (int) $original->getKey();
        $original->deleteOrFail();
        $nova = $publicacao->audicoes()->create(['utilizador_id' => $causador->getKey()]);
        $recente = new NotificacaoInteracaoUtilizador($publicacao, $causador, 'ouviu');

        self::assertNotSame($identificadorOriginal, (int) $nova->getKey());
        self::assertFalse($antiga->shouldSend($destinatario, 'database'));
        self::assertTrue($recente->shouldSend($destinatario, 'database'));
    }

    #[Test]
    public function acoes_reversiveis_sem_registo_nao_sao_entregues(): void
    {
        [$publicacao, $causador, $destinatario] = $this->criarCenario();
        $notificacao = new NotificacaoInteracaoUtilizador($publicacao, $causador, 'avaliou');
        self::assertFalse($notificacao->shouldSend($destinatario, 'database'));
    }

    #[Test]
    public function outras_acoes_nao_dependem_dos_registos_reversiveis(): void
    {
        [$publicacao, $causador, $destinatario] = $this->criarCenario();
        $comentario = $publicacao->comentarios()->create([
            'utilizador_id' => $causador->getKey(),
            'conteudo' => 'Comentário que deve continuar a ser entregue.',
            'comentario_pai_id' => null,
        ]);
        $notificacao = new NotificacaoInteracaoUtilizador($publicacao, $causador, 'comentou', $comentario);
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
}
