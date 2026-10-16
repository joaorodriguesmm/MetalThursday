<?php

declare(strict_types=1);

namespace Tests\Feature\Notifications;

use App\Enumeracoes\IdentificadorPermissaoEmail;
use App\Enumeracoes\PapelUtilizador;
use App\Models\Autenticacao\Utilizador;
use App\Models\Comunicacao\PermissaoEmail;
use App\Models\MetalThursday\Edicao;
use App\Models\MetalThursday\MetalThursday;
use App\Models\MetalThursday\ReservaMetalThursday;
use App\Notifications\NotificacaoLembreteAtrasoMetalThursday;
use App\Notifications\NotificacaoLembreteTarefaMetalThursday;
use App\Notifications\NotificacaoUtilizadorNomeado;
use Carbon\CarbonImmutable;
use Database\Seeders\PermissaoEmailSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Revalida a elegibilidade temporal das notificações ligadas a reservas.
 *
 * @since 2.0.0
 */
final class NotificacoesReservasEntregaTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function nomeacao_pendente_e_entregue_ao_responsavel_atual(): void
    {
        $destinatario = Utilizador::factory()->create();
        $reserva = ReservaMetalThursday::factory()
            ->comData(CarbonImmutable::parse('2026-10-29'))
            ->comResponsavel($destinatario)
            ->create();

        $notificacao = new NotificacaoUtilizadorNomeado($reserva);

        self::assertTrue($notificacao->shouldSend($destinatario, 'database'));
    }

    #[Test]
    public function nomeacao_transferida_nao_e_entregue_ao_antigo_responsavel(): void
    {
        $antigo = Utilizador::factory()->create();
        $novo = Utilizador::factory()->create();
        $reserva = ReservaMetalThursday::factory()
            ->comData(CarbonImmutable::parse('2026-10-29'))
            ->comResponsavel($antigo)
            ->create();
        $notificacao = new NotificacaoUtilizadorNomeado($reserva);

        $reserva->updateOrFail(['responsavel_id' => $novo->getKey()]);

        self::assertFalse($notificacao->shouldSend($antigo, 'database'));
    }

    #[Test]
    public function nomeacao_pendente_de_responsavel_suspenso_nao_e_entregue(): void
    {
        $administrador = Utilizador::factory()
            ->comPapel(PapelUtilizador::SuperAdministrador)
            ->create();
        $destinatario = Utilizador::factory()->create();
        $reserva = ReservaMetalThursday::factory()
            ->comData(CarbonImmutable::parse('2026-10-29'))
            ->comResponsavel($destinatario)
            ->create();
        $notificacao = new NotificacaoUtilizadorNomeado($reserva);

        $destinatario->forceFill([
            'suspenso_em' => now(),
            'motivo_suspensao' => 'Suspensão posterior ao enfileiramento.',
            'suspenso_por_id' => $administrador->getKey(),
        ])->saveOrFail();

        self::assertFalse($notificacao->shouldSend($destinatario, 'database'));
    }

    #[Test]
    public function lembrete_de_tarefa_pendente_e_entregue(): void
    {
        $destinatario = Utilizador::factory()->create();
        $reserva = ReservaMetalThursday::factory()
            ->comData(CarbonImmutable::parse('2026-10-22'))
            ->comResponsavel($destinatario)
            ->create();

        $notificacao = new NotificacaoLembreteTarefaMetalThursday($reserva);

        self::assertTrue($notificacao->shouldSend($destinatario, 'database'));
    }

    #[Test]
    public function lembrete_de_tarefa_cumprida_antes_do_worker_e_cancelado(): void
    {
        $destinatario = Utilizador::factory()->create();
        $outro = Utilizador::factory()->create();
        $data = CarbonImmutable::parse('2026-10-15');
        $reserva = ReservaMetalThursday::factory()
            ->comData($data)
            ->comResponsavel($destinatario)
            ->create();
        $notificacao = new NotificacaoLembreteTarefaMetalThursday($reserva);

        $edicao = Edicao::factory()
            ->comPeriodo($data->startOfMonth(), $data->endOfMonth())
            ->create();
        $publicacao = MetalThursday::factory()
            ->comData($data)
            ->comEdicao($edicao)
            ->comAutor($destinatario)
            ->comProximoNomeado($outro)
            ->create();
        $reserva->updateOrFail(['metal_thursday_id' => $publicacao->getKey()]);

        self::assertFalse($notificacao->shouldSend($destinatario, 'database'));
    }

    #[Test]
    public function lembrete_de_tarefa_transferida_e_cancelado(): void
    {
        $antigo = Utilizador::factory()->create();
        $novo = Utilizador::factory()->create();
        $reserva = ReservaMetalThursday::factory()
            ->comData(CarbonImmutable::parse('2026-10-22'))
            ->comResponsavel($antigo)
            ->create();
        $notificacao = new NotificacaoLembreteTarefaMetalThursday($reserva);

        $reserva->updateOrFail(['responsavel_id' => $novo->getKey()]);

        self::assertFalse($notificacao->shouldSend($antigo, 'database'));
    }

    #[Test]
    public function lembrete_de_atraso_pendente_respeita_permissao_email(): void
    {
        app(PermissaoEmailSeeder::class)->run();
        $destinatario = Utilizador::factory()->create();
        $reserva = ReservaMetalThursday::factory()
            ->comData(CarbonImmutable::parse('2026-10-08'))
            ->comResponsavel($destinatario)
            ->create();
        $permissao = PermissaoEmail::query()
            ->where('identificador', IdentificadorPermissaoEmail::LembreteDiarioAtrasos->value)
            ->sole();
        $destinatario->permissoesEmail()->attach($permissao->getKey());
        $notificacao = new NotificacaoLembreteAtrasoMetalThursday(
            $reserva,
            CarbonImmutable::parse('2026-10-09'),
        );

        self::assertTrue($notificacao->shouldSend($destinatario, 'mail'));

        $destinatario->permissoesEmail()->detach($permissao->getKey());

        self::assertFalse($notificacao->shouldSend($destinatario, 'mail'));
    }

    #[Test]
    public function lembrete_de_atraso_transferido_nao_e_enviado(): void
    {
        app(PermissaoEmailSeeder::class)->run();
        $antigo = Utilizador::factory()->create();
        $novo = Utilizador::factory()->create();
        $reserva = ReservaMetalThursday::factory()
            ->comData(CarbonImmutable::parse('2026-10-08'))
            ->comResponsavel($antigo)
            ->create();
        $permissao = PermissaoEmail::query()
            ->where('identificador', IdentificadorPermissaoEmail::LembreteDiarioAtrasos->value)
            ->sole();
        $antigo->permissoesEmail()->attach($permissao->getKey());
        $notificacao = new NotificacaoLembreteAtrasoMetalThursday(
            $reserva,
            CarbonImmutable::parse('2026-10-09'),
        );

        self::assertTrue($notificacao->shouldSend($antigo, 'mail'));

        $reserva->updateOrFail(['responsavel_id' => $novo->getKey()]);

        self::assertFalse($notificacao->shouldSend($antigo, 'mail'));
    }

    #[Test]
    public function lembrete_de_atraso_cumprido_nao_e_enviado(): void
    {
        app(PermissaoEmailSeeder::class)->run();
        $destinatario = Utilizador::factory()->create();
        $outro = Utilizador::factory()->create();
        $data = CarbonImmutable::parse('2026-10-08');
        $reserva = ReservaMetalThursday::factory()
            ->comData($data)
            ->comResponsavel($destinatario)
            ->create();
        $permissao = PermissaoEmail::query()
            ->where('identificador', IdentificadorPermissaoEmail::LembreteDiarioAtrasos->value)
            ->sole();
        $destinatario->permissoesEmail()->attach($permissao->getKey());
        $notificacao = new NotificacaoLembreteAtrasoMetalThursday(
            $reserva,
            CarbonImmutable::parse('2026-10-09'),
        );

        self::assertTrue($notificacao->shouldSend($destinatario, 'mail'));

        $edicao = Edicao::factory()
            ->comPeriodo($data->startOfMonth(), $data->endOfMonth())
            ->create();
        $publicacao = MetalThursday::factory()
            ->comData($data)
            ->comEdicao($edicao)
            ->comAutor($destinatario)
            ->comProximoNomeado($outro)
            ->create();
        $reserva->updateOrFail(['metal_thursday_id' => $publicacao->getKey()]);

        self::assertFalse($notificacao->shouldSend($destinatario, 'mail'));
    }
}
