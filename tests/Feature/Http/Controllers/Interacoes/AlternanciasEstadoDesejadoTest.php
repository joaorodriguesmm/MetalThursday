<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Interacoes;

use App\Enumeracoes\Interacoes\TipoEntidadeInteracao;
use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\MetalThursday;
use App\Notifications\NotificacaoInteracaoUtilizador;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class AlternanciasEstadoDesejadoTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Confirma uma única notificação por destinatário ativo, sem assumir
     * quantos utilizadores as factories criaram indiretamente.
     */
    private function afirmarUmaNotificacaoPorDestinatarioAtivo(Utilizador $causador): void
    {
        $destinatarios = Utilizador::query()
            ->comAcessoAtivo()
            ->whereKeyNot($causador->id)
            ->get();

        self::assertGreaterThan(0, $destinatarios->count());

        foreach ($destinatarios as $destinatario) {
            Notification::assertSentTo(
                $destinatario,
                NotificacaoInteracaoUtilizador::class,
            );
        }

        Notification::assertCount($destinatarios->count());
    }

    #[Test]
    public function repetir_pedido_para_adicionar_gosto_nao_remove_gosto(): void
    {
        Notification::fake();

        $autor = Utilizador::factory()->create();
        $utilizador = Utilizador::factory()->create();
        $metalThursday = MetalThursday::factory()->create();

        $comentario = $metalThursday->comentarios()->create([
            'utilizador_id' => $autor->id,
            'conteudo' => 'Comentário de teste.',
            'comentario_pai_id' => null,
        ]);

        $url = route('gostos.alternar', $comentario);
        $dados = ['estado_desejado' => true];

        $this->actingAs($utilizador, 'sessao')
            ->postJson($url, $dados)
            ->assertOk()
            ->assertJsonPath('adicionado', true);

        $this->postJson($url, $dados)
            ->assertOk()
            ->assertJsonPath('adicionado', true);

        $this->assertDatabaseHas('gostos', [
            'comentario_id' => $comentario->id,
            'utilizador_id' => $utilizador->id,
        ]);

        $this->assertDatabaseCount('gostos', 1);
        $this->afirmarUmaNotificacaoPorDestinatarioAtivo($utilizador);
    }

    #[Test]
    public function repetir_pedido_para_marcar_ouvido_nao_remove_audicao(): void
    {
        Notification::fake();

        Utilizador::factory()->create();
        $utilizador = Utilizador::factory()->create();
        $metalThursday = MetalThursday::factory()->create();

        $url = route('audicoes.alternar', [
            'tipoAudivel' => TipoEntidadeInteracao::MetalThursday->value,
            'identificadorAudivel' => $metalThursday->id,
        ]);

        $dados = ['estado_desejado' => true];

        $this->actingAs($utilizador, 'sessao')
            ->postJson($url, $dados)
            ->assertOk()
            ->assertJsonPath('marcado_como_ouvido', true);

        $this->postJson($url, $dados)
            ->assertOk()
            ->assertJsonPath('marcado_como_ouvido', true);

        $this->assertDatabaseHas('audicoes', [
            'utilizador_id' => $utilizador->id,
            'tipo_audivel' => $metalThursday->getMorphClass(),
            'audivel_id' => $metalThursday->id,
        ]);

        $this->assertDatabaseCount('audicoes', 1);
        $this->afirmarUmaNotificacaoPorDestinatarioAtivo($utilizador);
    }

    #[Test]
    public function estado_falso_repetido_remove_gosto_sem_notificacoes(): void
    {
        Notification::fake();

        $autor = Utilizador::factory()->create();
        $utilizador = Utilizador::factory()->create();
        $metalThursday = MetalThursday::factory()->create();
        $comentario = $metalThursday->comentarios()->create([
            'utilizador_id' => $autor->id,
            'conteudo' => 'Remoção idempotente de gosto.',
            'comentario_pai_id' => null,
        ]);

        $url = route('gostos.alternar', $comentario);

        $this->actingAs($utilizador, 'sessao')
            ->postJson($url, ['estado_desejado' => true])
            ->assertOk()
            ->assertJsonPath('adicionado', true);

        $this->afirmarUmaNotificacaoPorDestinatarioAtivo($utilizador);

        $this->postJson($url, ['estado_desejado' => false])
            ->assertOk()
            ->assertJsonPath('adicionado', false);

        $this->postJson($url, ['estado_desejado' => false])
            ->assertOk()
            ->assertJsonPath('adicionado', false);

        $this->assertDatabaseMissing('gostos', [
            'comentario_id' => $comentario->id,
            'utilizador_id' => $utilizador->id,
        ]);
        $this->afirmarUmaNotificacaoPorDestinatarioAtivo($utilizador);
    }

    #[Test]
    public function estado_falso_repetido_remove_audicao_sem_notificacoes(): void
    {
        Notification::fake();

        Utilizador::factory()->create();
        $utilizador = Utilizador::factory()->create();
        $metalThursday = MetalThursday::factory()->create();
        $url = route('audicoes.alternar', [
            'tipoAudivel' => TipoEntidadeInteracao::MetalThursday->value,
            'identificadorAudivel' => $metalThursday->id,
        ]);

        $this->actingAs($utilizador, 'sessao')
            ->postJson($url, ['estado_desejado' => true])
            ->assertOk()
            ->assertJsonPath('marcado_como_ouvido', true);

        $this->afirmarUmaNotificacaoPorDestinatarioAtivo($utilizador);

        $this->postJson($url, ['estado_desejado' => false])
            ->assertOk()
            ->assertJsonPath('marcado_como_ouvido', false);

        $this->postJson($url, ['estado_desejado' => false])
            ->assertOk()
            ->assertJsonPath('marcado_como_ouvido', false);

        $this->assertDatabaseMissing('audicoes', [
            'utilizador_id' => $utilizador->id,
            'tipo_audivel' => $metalThursday->getMorphClass(),
            'audivel_id' => $metalThursday->id,
        ]);
        $this->afirmarUmaNotificacaoPorDestinatarioAtivo($utilizador);
    }

    #[Test]
    public function gostos_rejeitam_estados_invalidos_sem_alterar_dados(): void
    {
        Notification::fake();

        $autor = Utilizador::factory()->create();
        $utilizador = Utilizador::factory()->create();
        $metalThursday = MetalThursday::factory()->create();
        $comentario = $metalThursday->comentarios()->create([
            'utilizador_id' => $autor->id,
            'conteudo' => 'Rejeição de estado inválido.',
            'comentario_pai_id' => null,
        ]);
        $url = route('gostos.alternar', $comentario);

        $this->actingAs($utilizador, 'sessao');

        foreach ([null, 'sim', 'true', [], ['valor' => true]] as $valor) {
            $this->postJson($url, ['estado_desejado' => $valor])
                ->assertUnprocessable()
                ->assertJsonValidationErrors('estado_desejado');
        }

        $this->assertDatabaseMissing('gostos', [
            'comentario_id' => $comentario->id,
            'utilizador_id' => $utilizador->id,
        ]);
        Notification::assertNothingSent();
    }

    #[Test]
    public function audicoes_rejeitam_estados_invalidos_sem_alterar_dados(): void
    {
        Notification::fake();

        Utilizador::factory()->create();
        $utilizador = Utilizador::factory()->create();
        $metalThursday = MetalThursday::factory()->create();
        $url = route('audicoes.alternar', [
            'tipoAudivel' => TipoEntidadeInteracao::MetalThursday->value,
            'identificadorAudivel' => $metalThursday->id,
        ]);

        $this->actingAs($utilizador, 'sessao');

        foreach ([null, 'sim', 'true', [], ['valor' => true]] as $valor) {
            $this->postJson($url, ['estado_desejado' => $valor])
                ->assertUnprocessable()
                ->assertJsonValidationErrors('estado_desejado');
        }

        $this->assertDatabaseMissing('audicoes', [
            'utilizador_id' => $utilizador->id,
            'tipo_audivel' => $metalThursday->getMorphClass(),
            'audivel_id' => $metalThursday->id,
        ]);
        Notification::assertNothingSent();
    }
}
