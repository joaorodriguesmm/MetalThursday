<?php

declare(strict_types=1);

namespace Tests\Feature\Notifications;

use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\Edicao;
use App\Models\MetalThursday\MetalThursday;
use App\Models\MetalThursday\SeccaoMetalThursday;
use App\Notifications\NotificacaoInteracaoUtilizador;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Revalida a disponibilidade de secções antes de entregar interações.
 *
 * @since 2.0.0
 */
final class NotificacaoInteracaoSeccaoEliminadaEntregaTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string}>
     */
    public static function acoesDeSeccao(): array
    {
        return [
            'avaliacao' => ['avaliou'],
            'audicao' => ['ouviu'],
            'comentario' => ['comentou'],
            'resposta' => ['respondeu'],
            'gosto' => ['gostou'],
        ];
    }

    #[Test]
    #[DataProvider('acoesDeSeccao')]
    public function secao_eliminada_bloqueia_notificacao_da_interacao_original(string $acao): void
    {
        [$publicacao, $causador, $destinatario] = $this->criarCenario();
        $seccao = SeccaoMetalThursday::factory()
            ->paraMetalThursday($publicacao)
            ->create();

        $notificacao = $this->criarNotificacao($seccao, $causador, $destinatario, $acao);

        self::assertTrue($notificacao->shouldSend($destinatario, 'database'));
        $seccao->deleteOrFail();
        self::assertTrue(SeccaoMetalThursday::withTrashed()->whereKey($seccao->getKey())->exists());
        self::assertTrue(MetalThursday::query()->publicadas()->whereKey($publicacao->getKey())->exists());
        self::assertFalse($notificacao->shouldSend($destinatario, 'database'));
        self::assertFalse($notificacao->shouldSend($destinatario, 'mail'));
    }

    #[Test]
    #[DataProvider('acoesDeSeccao')]
    public function secao_valida_continua_a_permitir_notificacao(string $acao): void
    {
        [$publicacao, $causador, $destinatario] = $this->criarCenario();
        $seccao = SeccaoMetalThursday::factory()
            ->paraMetalThursday($publicacao)
            ->create();

        $notificacao = $this->criarNotificacao($seccao, $causador, $destinatario, $acao);

        self::assertTrue($notificacao->shouldSend($destinatario, 'database'));
    }

    #[Test]
    public function eliminar_outra_seccao_nao_bloqueia_interacao_na_publicacao(): void
    {
        [$publicacao, $causador, $destinatario] = $this->criarCenario();
        $seccao = SeccaoMetalThursday::factory()->paraMetalThursday($publicacao)->create();
        $publicacao->avaliacoes()->create([
            'utilizador_id' => $causador->getKey(),
            'pontuacao' => 4.0,
        ]);
        $notificacao = new NotificacaoInteracaoUtilizador($publicacao, $causador, 'avaliou');

        $seccao->deleteOrFail();

        self::assertTrue($notificacao->shouldSend($destinatario, 'database'));
    }

    #[Test]
    public function interacao_em_comentario_de_publicacao_nao_depende_de_seccoes(): void
    {
        [$publicacao, $causador, $destinatario] = $this->criarCenario();
        $seccao = SeccaoMetalThursday::factory()->paraMetalThursday($publicacao)->create();
        $comentario = $publicacao->comentarios()->create([
            'utilizador_id' => $destinatario->getKey(),
            'conteudo' => 'Comentário na publicação.',
            'comentario_pai_id' => null,
        ]);
        $comentario->gostos()->create([
            'utilizador_id' => $causador->getKey(),
        ]);
        $notificacao = new NotificacaoInteracaoUtilizador($comentario, $causador, 'gostou');

        $seccao->deleteOrFail();

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

    private function criarNotificacao(
        SeccaoMetalThursday $seccao,
        Utilizador $causador,
        Utilizador $destinatario,
        string $acao,
    ): NotificacaoInteracaoUtilizador {
        if ($acao === 'avaliou') {
            $seccao->avaliacoes()->create([
                'utilizador_id' => $causador->getKey(),
                'pontuacao' => 4.0,
            ]);

            return new NotificacaoInteracaoUtilizador($seccao, $causador, $acao);
        }

        if ($acao === 'ouviu') {
            $seccao->audicoes()->create([
                'utilizador_id' => $causador->getKey(),
            ]);

            return new NotificacaoInteracaoUtilizador($seccao, $causador, $acao);
        }

        if ($acao === 'comentou') {
            $comentario = $seccao->comentarios()->create([
                'utilizador_id' => $causador->getKey(),
                'conteudo' => 'Comentário numa secção.',
                'comentario_pai_id' => null,
            ]);

            return new NotificacaoInteracaoUtilizador($seccao, $causador, $acao, $comentario);
        }

        $comentario = $seccao->comentarios()->create([
            'utilizador_id' => $destinatario->getKey(),
            'conteudo' => 'Comentário de referência.',
            'comentario_pai_id' => null,
        ]);

        if ($acao === 'respondeu') {
            $resposta = $seccao->comentarios()->create([
                'utilizador_id' => $causador->getKey(),
                'conteudo' => 'Resposta numa secção.',
                'comentario_pai_id' => $comentario->getKey(),
            ]);

            return new NotificacaoInteracaoUtilizador($comentario, $causador, $acao, $resposta);
        }

        if ($acao === 'gostou') {
            $comentario->gostos()->create([
                'utilizador_id' => $causador->getKey(),
            ]);

            return new NotificacaoInteracaoUtilizador($comentario, $causador, $acao);
        }

        throw new \InvalidArgumentException('Ação não suportada no teste: '.$acao);
    }
}
