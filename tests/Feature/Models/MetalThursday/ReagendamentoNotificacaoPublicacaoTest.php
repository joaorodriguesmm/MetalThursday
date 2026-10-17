<?php

declare(strict_types=1);

namespace Tests\Feature\Models\MetalThursday;

use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\Edicao;
use App\Models\MetalThursday\MetalThursday;
use App\Notifications\NotificacaoMetalThursdayCriada;
use App\Servicos\MetalThursday\ServicoNotificacaoPublicacaoMetalThursday;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Protege o ciclo de publicação, reagendamento e nova notificação.
 *
 * @since 2.0.0
 */
final class ReagendamentoNotificacaoPublicacaoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(
            CarbonImmutable::parse('2026-10-17 12:00:00', 'Europe/Lisbon'),
        );
    }

    #[Test]
    public function reagendamento_para_o_futuro_reabre_publicacao_notificada(): void
    {
        Notification::fake();

        $publicacao = $this->criarPublicacao('2026-10-15');
        $servico = app(ServicoNotificacaoPublicacaoMetalThursday::class);

        self::assertTrue($servico->processar($publicacao));
        self::assertNotNull($publicacao->fresh()?->publicacao_notificada_em);

        $publicacao->updateOrFail([
            'data' => CarbonImmutable::parse('2026-11-05'),
        ]);

        self::assertTrue($publicacao->fresh()?->estaPreparada());
        self::assertNull($publicacao->fresh()?->publicacao_notificada_em);
        self::assertFalse($servico->processar($publicacao));
    }

    #[Test]
    public function edicao_do_nome_nao_reabre_uma_publicacao(): void
    {
        Notification::fake();

        $publicacao = $this->criarPublicacao('2026-10-15');
        $servico = app(ServicoNotificacaoPublicacaoMetalThursday::class);

        self::assertTrue($servico->processar($publicacao));

        $marcador = $publicacao->fresh()?->publicacao_notificada_em;
        self::assertNotNull($marcador);

        $publicacao->updateOrFail(['nome' => 'Nome corrigido sem nova notificação']);

        self::assertEquals($marcador, $publicacao->fresh()?->publicacao_notificada_em);
        self::assertFalse($servico->processar($publicacao));
    }

    #[Test]
    public function alteracao_entre_datas_ja_publicadas_nao_gera_duplicados(): void
    {
        Notification::fake();

        $publicacao = $this->criarPublicacao('2026-10-14');
        $servico = app(ServicoNotificacaoPublicacaoMetalThursday::class);

        self::assertTrue($servico->processar($publicacao));
        $marcador = $publicacao->fresh()?->publicacao_notificada_em;
        self::assertNotNull($marcador);

        $publicacao->updateOrFail([
            'data' => CarbonImmutable::parse('2026-10-15'),
        ]);

        self::assertEquals($marcador, $publicacao->fresh()?->publicacao_notificada_em);
        self::assertFalse($servico->processar($publicacao));
    }

    #[Test]
    public function alteracao_de_publicacao_futura_sem_marcador_mantem_pendencia(): void
    {
        $publicacao = $this->criarPublicacao('2026-11-05');
        self::assertNull($publicacao->fresh()?->publicacao_notificada_em);

        $publicacao->updateOrFail([
            'data' => CarbonImmutable::parse('2026-11-06'),
        ]);

        self::assertNull($publicacao->fresh()?->publicacao_notificada_em);
        self::assertTrue($publicacao->fresh()?->estaPreparada());
    }

    #[Test]
    public function trabalho_antigo_nao_e_entregue_na_nova_data_e_novo_trabalho_e_valido(): void
    {
        Notification::fake();

        $publicacao = $this->criarPublicacao('2026-10-15');
        $destinatario = Utilizador::factory()->create();
        $servico = app(ServicoNotificacaoPublicacaoMetalThursday::class);
        $notificacaoAntiga = new NotificacaoMetalThursdayCriada($publicacao);

        self::assertTrue($notificacaoAntiga->shouldSend($destinatario, 'database'));
        self::assertTrue($servico->processar($publicacao));

        $publicacao->updateOrFail([
            'data' => CarbonImmutable::parse('2026-11-05'),
        ]);
        self::assertNull($publicacao->fresh()?->publicacao_notificada_em);

        $this->travelTo(
            CarbonImmutable::parse('2026-11-06 12:00:00', 'Europe/Lisbon'),
        );

        self::assertTrue($publicacao->fresh()?->estaPublicada());
        self::assertFalse($notificacaoAntiga->shouldSend($destinatario, 'database'));
        self::assertTrue($servico->processar($publicacao->fresh()));

        $notificacaoNova = new NotificacaoMetalThursdayCriada($publicacao->fresh());
        self::assertTrue($notificacaoNova->shouldSend($destinatario, 'database'));
        self::assertNotNull($publicacao->fresh()?->publicacao_notificada_em);
        self::assertFalse($servico->processar($publicacao->fresh()));
    }

    private function criarPublicacao(string $data): MetalThursday
    {
        $dataMetalThursday = CarbonImmutable::parse($data, 'Europe/Lisbon');
        $edicao = Edicao::factory()
            ->comPeriodo(
                CarbonImmutable::parse('2026-10-01'),
                CarbonImmutable::parse('2026-11-30'),
            )
            ->create();

        $autor = Utilizador::factory()->create();
        $nomeado = Utilizador::factory()->create();

        return MetalThursday::factory()
            ->comNome('Publicação B24-16 — ciclo de reagendamento')
            ->comData($dataMetalThursday)
            ->comEdicao($edicao)
            ->comAutor($autor)
            ->comProximoNomeado($nomeado)
            ->create(['criado_por_id' => $autor->getKey()]);
    }
}
