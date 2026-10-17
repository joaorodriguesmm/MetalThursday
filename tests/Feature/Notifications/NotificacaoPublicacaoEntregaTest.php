<?php

declare(strict_types=1);

namespace Tests\Feature\Notifications;

use App\Enumeracoes\IdentificadorPermissaoEmail;
use App\Enumeracoes\PapelUtilizador;
use App\Models\Autenticacao\Utilizador;
use App\Models\Comunicacao\PermissaoEmail;
use App\Models\MetalThursday\Edicao;
use App\Models\MetalThursday\MetalThursday;
use App\Notifications\NotificacaoMetalThursdayCriada;
use Carbon\CarbonImmutable;
use Database\Seeders\PermissaoEmailSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa a elegibilidade no momento da entrega das novas publicações.
 *
 * @since 2.0.0
 */
final class NotificacaoPublicacaoEntregaTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function publicacao_valida_continua_a_ser_entregue(): void
    {
        $destinatario = Utilizador::factory()->create();
        $publicacao = $this->criarPublicacao();
        $notificacao = new NotificacaoMetalThursdayCriada($publicacao);

        self::assertTrue($notificacao->shouldSend($destinatario, 'database'));
    }

    #[Test]
    public function publicacao_eliminada_logicamente_deixa_de_ser_entregue(): void
    {
        $destinatario = Utilizador::factory()->create();
        $publicacao = $this->criarPublicacao();
        $notificacao = new NotificacaoMetalThursdayCriada($publicacao);

        self::assertTrue($notificacao->shouldSend($destinatario, 'database'));

        $publicacao->deleteOrFail();

        self::assertFalse($notificacao->shouldSend($destinatario, 'database'));
    }

    #[Test]
    public function publicacao_reagendada_para_o_futuro_deixa_de_ser_entregue(): void
    {
        $destinatario = Utilizador::factory()->create();
        $publicacao = $this->criarPublicacao();
        $notificacao = new NotificacaoMetalThursdayCriada($publicacao);

        self::assertTrue($notificacao->shouldSend($destinatario, 'database'));

        $publicacao->updateOrFail([
            'data' => CarbonImmutable::now((string) config('app.timezone'))
                ->startOfDay()->addDays(20),
        ]);

        self::assertFalse($notificacao->shouldSend($destinatario, 'database'));
    }

    #[Test]
    public function publicacao_eliminada_definitivamente_deixa_de_ser_entregue(): void
    {
        $destinatario = Utilizador::factory()->create();
        $publicacao = $this->criarPublicacao();
        $notificacao = new NotificacaoMetalThursdayCriada($publicacao);

        $publicacao->forceDelete();

        self::assertFalse($notificacao->shouldSend($destinatario, 'database'));
    }

    #[Test]
    public function destinatario_suspenso_antes_do_worker_nao_recebe_publicacao(): void
    {
        $administrador = Utilizador::factory()
            ->comPapel(PapelUtilizador::SuperAdministrador)
            ->create();
        $destinatario = Utilizador::factory()->create();
        $publicacao = $this->criarPublicacao();
        $notificacao = new NotificacaoMetalThursdayCriada($publicacao);

        $destinatario->forceFill([
            'suspenso_em' => now(),
            'motivo_suspensao' => 'Suspensão posterior ao enfileiramento.',
            'suspenso_por_id' => $administrador->getKey(),
        ])->saveOrFail();

        self::assertFalse($notificacao->shouldSend($destinatario, 'database'));
    }

    #[Test]
    public function revogacao_da_permissao_email_continua_a_ser_respeitada(): void
    {
        app(PermissaoEmailSeeder::class)->run();
        $destinatario = Utilizador::factory()->create();
        $publicacao = $this->criarPublicacao();
        $permissao = PermissaoEmail::query()
            ->where('identificador', IdentificadorPermissaoEmail::NovasPublicacoes->value)
            ->sole();
        $destinatario->permissoesEmail()->attach($permissao->getKey());
        $notificacao = new NotificacaoMetalThursdayCriada($publicacao);

        self::assertTrue($notificacao->shouldSend($destinatario, 'mail'));

        $destinatario->permissoesEmail()->detach($permissao->getKey());

        self::assertFalse($notificacao->shouldSend($destinatario, 'mail'));
        self::assertTrue($notificacao->shouldSend($destinatario, 'database'));
    }

    #[Test]
    public function destinatario_eliminado_nao_recebe_publicacao(): void
    {
        $destinatario = Utilizador::factory()->create();
        $publicacao = $this->criarPublicacao();
        $notificacao = new NotificacaoMetalThursdayCriada($publicacao);

        $destinatario->deleteOrFail();

        self::assertFalse($notificacao->shouldSend($destinatario, 'database'));
    }

    private function criarPublicacao(): MetalThursday
    {
        $hoje = CarbonImmutable::now((string) config('app.timezone'))
            ->startOfDay();
        $data = $hoje->subDays(3);
        $edicao = Edicao::factory()
            ->comPeriodo(
                $hoje->subMonths(2)->startOfMonth(),
                $hoje->addMonths(2)->endOfMonth(),
            )->create();
        $autor = Utilizador::factory()->create();
        $nomeado = Utilizador::factory()->create();

        return MetalThursday::factory()
            ->comNome('Publicação para teste de entrega B24-16')
            ->comData($data)
            ->comEdicao($edicao)
            ->comAutor($autor)
            ->comProximoNomeado($nomeado)
            ->create();
    }
}
