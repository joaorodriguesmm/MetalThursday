<?php

declare(strict_types=1);

namespace Tests\Feature\Notifications;

use App\Enumeracoes\IdentificadorPermissaoEmail;
use App\Models\Autenticacao\Utilizador;
use App\Models\Comunicacao\PermissaoEmail;
use App\Models\MetalThursday\Edicao;
use App\Models\MetalThursday\MetalThursday;
use App\Models\MetalThursday\SeccaoMetalThursday;
use App\Notifications\NotificacaoInteracaoUtilizador;
use Carbon\CarbonImmutable;
use Database\Seeders\PermissaoEmailSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Revalida a publicação associada a uma interação no momento da entrega.
 *
 * @since 2.0.0
 */
final class NotificacaoInteracaoPublicacaoEntregaTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function interacao_de_publicacao_valida_continua_a_ser_entregue(): void
    {
        [$publicacao, $causador, $destinatario] = $this->criarCenario();

        $notificacao = new NotificacaoInteracaoUtilizador(
            $publicacao,
            $causador,
            'avaliou',
        );

        self::assertTrue($notificacao->shouldSend($destinatario, 'database'));
    }

    #[Test]
    public function interacao_de_publicacao_eliminada_logicamente_e_bloqueada(): void
    {
        [$publicacao, $causador, $destinatario] = $this->criarCenario();

        app(PermissaoEmailSeeder::class)->run();

        $permissao = PermissaoEmail::query()
            ->where('identificador', IdentificadorPermissaoEmail::TodasInteracoes->value)
            ->sole();

        $destinatario->permissoesEmail()->attach($permissao->getKey());

        $notificacao = new NotificacaoInteracaoUtilizador(
            $publicacao,
            $causador,
            'avaliou',
        );

        self::assertTrue($notificacao->shouldSend($destinatario, 'mail'));

        $publicacao->deleteOrFail();

        self::assertFalse($notificacao->shouldSend($destinatario, 'database'));
        self::assertFalse($notificacao->shouldSend($destinatario, 'mail'));
    }

    #[Test]
    public function interacao_de_publicacao_reagendada_para_o_futuro_e_bloqueada(): void
    {
        [$publicacao, $causador, $destinatario] = $this->criarCenario();

        $notificacao = new NotificacaoInteracaoUtilizador(
            $publicacao,
            $causador,
            'avaliou',
        );

        $publicacao->updateOrFail([
            'data' => CarbonImmutable::today()->addDays(20),
        ]);

        self::assertFalse($notificacao->shouldSend($destinatario, 'database'));
    }

    #[Test]
    public function interacao_de_publicacao_eliminada_definitivamente_e_bloqueada(): void
    {
        [$publicacao, $causador, $destinatario] = $this->criarCenario();

        $notificacao = new NotificacaoInteracaoUtilizador(
            $publicacao,
            $causador,
            'comentou',
        );

        $publicacao->forceDelete();

        self::assertFalse($notificacao->shouldSend($destinatario, 'database'));
    }

    #[Test]
    public function interacao_de_seccao_de_publicacao_eliminada_e_bloqueada(): void
    {
        [$publicacao, $causador, $destinatario] = $this->criarCenario();

        $seccao = SeccaoMetalThursday::factory()
            ->paraMetalThursday($publicacao)
            ->create();

        $seccao->avaliacoes()->create([
            'utilizador_id' => $causador->getKey(),
            'pontuacao' => 8.5,
        ]);

        $notificacao = new NotificacaoInteracaoUtilizador(
            $seccao,
            $causador,
            'avaliou',
        );

        self::assertTrue($notificacao->shouldSend($destinatario, 'database'));

        $publicacao->deleteOrFail();

        self::assertFalse($notificacao->shouldSend($destinatario, 'database'));
    }

    #[Test]
    public function interacao_de_comentario_de_publicacao_reagendada_e_bloqueada(): void
    {
        [$publicacao, $causador, $destinatario] = $this->criarCenario();

        $comentario = $publicacao->comentarios()->create([
            'utilizador_id' => $causador->getKey(),
            'conteudo' => 'Comentário válido antes do reagendamento.',
            'comentario_pai_id' => null,
        ]);

        $comentario->gostos()->create([
            'utilizador_id' => $causador->getKey(),
        ]);

        $notificacao = new NotificacaoInteracaoUtilizador(
            $comentario,
            $causador,
            'gostou',
        );

        self::assertTrue($notificacao->shouldSend($destinatario, 'database'));

        $publicacao->updateOrFail([
            'data' => CarbonImmutable::today()->addDays(20),
        ]);

        self::assertFalse($notificacao->shouldSend($destinatario, 'database'));
    }

    #[Test]
    public function interacoes_de_seccao_e_comentario_validos_continuam_a_ser_entregues(): void
    {
        [$publicacao, $causador, $destinatario] = $this->criarCenario();

        $seccao = SeccaoMetalThursday::factory()
            ->paraMetalThursday($publicacao)
            ->create();

        $comentario = $seccao->comentarios()->create([
            'utilizador_id' => $causador->getKey(),
            'conteudo' => 'Comentário numa secção publicada.',
            'comentario_pai_id' => null,
        ]);

        $seccao->avaliacoes()->create([
            'utilizador_id' => $causador->getKey(),
            'pontuacao' => 8.5,
        ]);
        $comentario->gostos()->create([
            'utilizador_id' => $causador->getKey(),
        ]);

        $notificacaoSeccao = new NotificacaoInteracaoUtilizador(
            $seccao,
            $causador,
            'avaliou',
        );

        $notificacaoComentario = new NotificacaoInteracaoUtilizador(
            $comentario,
            $causador,
            'gostou',
        );

        self::assertTrue($notificacaoSeccao->shouldSend($destinatario, 'database'));
        self::assertTrue($notificacaoComentario->shouldSend($destinatario, 'database'));
    }

    /**
     * @return array{MetalThursday, Utilizador, Utilizador}
     */
    private function criarCenario(): array
    {
        $hoje = CarbonImmutable::today();

        $edicao = Edicao::factory()
            ->comPeriodo(
                $hoje->subMonths(2)->startOfMonth(),
                $hoje->addMonths(2)->endOfMonth(),
            )
            ->create();

        $causador = Utilizador::factory()->create();
        $destinatario = Utilizador::factory()->create();

        $publicacao = MetalThursday::factory()
            ->comData($hoje->subDays(3))
            ->comEdicao($edicao)
            ->comAutor($causador)
            ->comProximoNomeado($causador)
            ->create();

        $publicacao->avaliacoes()->create([
            'utilizador_id' => $causador->getKey(),
            'pontuacao' => 8.5,
        ]);

        return [$publicacao, $causador, $destinatario];
    }
}
