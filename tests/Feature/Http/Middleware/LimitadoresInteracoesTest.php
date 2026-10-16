<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Middleware;

use App\Enumeracoes\Interacoes\TipoEntidadeInteracao;
use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\MetalThursday;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

/**
 * Confirma os limites de pedidos das operações de interação autenticadas.
 *
 * @since 2.0.0
 */
final class LimitadoresInteracoesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Garante que todas as rotas de escrita partilham o mesmo limitador.
     *
     * As rotas de leitura não consomem o limite destinado às escritas.
     *
     * @since 2.0.0
     */
    #[Test]
    public function protege_todas_as_rotas_de_escrita_sem_limitar_listagens(): void
    {
        foreach ([
            'comentarios.guardar',
            'comentarios.respostas.guardar',
            'comentarios.atualizar',
            'comentarios.eliminar',
            'gostos.alternar',
            'avaliacoes.guardar',
            'avaliacoes.eliminar',
            'audicoes.alternar',
        ] as $nome) {
            $rota = Route::getRoutes()->getByName($nome);

            self::assertNotNull($rota, $nome);
            self::assertContains(
                'throttle:interacoes-escrita',
                $rota->gatherMiddleware(),
                $nome,
            );
        }

        foreach ([
            'comentarios.indice',
            'comentarios.respostas.indice',
            'comentarios.utilizadores-gosto',
        ] as $nome) {
            $rota = Route::getRoutes()->getByName($nome);

            self::assertNotNull($rota, $nome);
            self::assertNotContains(
                'throttle:interacoes-escrita',
                $rota->gatherMiddleware(),
                $nome,
            );
        }
    }

    /**
     * Impede o terceiro pedido do mesmo utilizador, mesmo em outra rota,
     * preservando a quota independente dos restantes utilizadores.
     *
     * @since 2.0.0
     */
    #[Test]
    public function limite_e_partilhado_entre_rotas_e_isolado_por_utilizador(): void
    {
        Notification::fake();

        config()->set(
            'interacoes.limites_pedidos_http.escritas_por_minuto',
            2,
        );

        $autor = Utilizador::factory()->create();
        $primeiroUtilizador = Utilizador::factory()->create();
        $segundoUtilizador = Utilizador::factory()->create();

        $metalThursday = MetalThursday::factory()->create();
        $comentario = $metalThursday->comentarios()->create([
            'utilizador_id' => $autor->id,
            'conteudo' => 'Comentário para testar o limite de pedidos.',
            'comentario_pai_id' => null,
        ]);

        foreach ([$primeiroUtilizador, $segundoUtilizador] as $utilizador) {
            RateLimiter::clear(
                'interacoes-escrita:utilizador:'.$utilizador->id,
            );
        }

        $rotaGosto = route('gostos.alternar', $comentario);
        $rotaAudicao = route('audicoes.alternar', [
            'tipoAudivel' => TipoEntidadeInteracao::MetalThursday->value,
            'identificadorAudivel' => $metalThursday->id,
        ]);

        $this->actingAs($primeiroUtilizador, 'sessao')
            ->postJson($rotaGosto)
            ->assertOk()
            ->assertJsonPath('adicionado', true);

        $this->postJson($rotaAudicao)
            ->assertOk()
            ->assertJsonPath('marcado_como_ouvido', true);

        $this->postJson($rotaGosto)
            ->assertStatus(Response::HTTP_TOO_MANY_REQUESTS)
            ->assertHeader('Retry-After');

        $this->assertDatabaseHas('gostos', [
            'comentario_id' => $comentario->id,
            'utilizador_id' => $primeiroUtilizador->id,
        ]);

        $this->actingAs($segundoUtilizador, 'sessao')
            ->postJson($rotaGosto)
            ->assertOk()
            ->assertJsonPath('adicionado', true);

        $this->assertDatabaseHas('gostos', [
            'comentario_id' => $comentario->id,
            'utilizador_id' => $segundoUtilizador->id,
        ]);
    }
}
