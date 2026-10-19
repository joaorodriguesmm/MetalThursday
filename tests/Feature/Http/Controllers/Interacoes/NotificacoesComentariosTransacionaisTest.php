<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Interacoes;

use App\Enumeracoes\Interacoes\TipoEntidadeInteracao;
use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\MetalThursday;
use App\Servicos\Notificacoes\NotificadorInteracoes;
use Illuminate\Contracts\Notifications\Dispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

final class NotificacoesComentariosTransacionaisTest extends TestCase
{
    use RefreshDatabase;

    private function prepararPedido(): array
    {
        $autor = Utilizador::factory()->create();

        Utilizador::factory()->create();

        $alvo = MetalThursday::factory()->create();

        return [
            $autor,
            route('comentarios.guardar', [
                'tipoComentavel' => TipoEntidadeInteracao::MetalThursday->value,
                'identificadorComentavel' => $alvo->id,
            ]),
            [
                'conteudo' => 'Teste de enfileiramento transacional.',
                'chave_submissao' => (string) Str::uuid(),
            ],
        ];
    }

    #[Test]
    public function falha_no_despacho_reverte_comentario_e_chave(): void
    {
        [$autor, $url, $dados] = $this->prepararPedido();

        $despachante = Mockery::mock(Dispatcher::class);

        $despachante
            ->shouldReceive('send')
            ->once()
            ->andThrow(
                new RuntimeException('Falha intencional.'),
            );

        $this->app->instance(
            NotificadorInteracoes::class,
            new NotificadorInteracoes($despachante),
        );

        $this->actingAs($autor, 'sessao')
            ->postJson($url, $dados)
            ->assertStatus(500);

        $this->assertDatabaseCount('comentarios', 0);
        $this->assertDatabaseCount('submissoes_comentarios', 0);
        $this->assertDatabaseCount('trabalhos_fila', 0);
    }

    #[Test]
    public function fila_database_recebe_trabalhos_uma_unica_vez(): void
    {
        [$autor, $url, $dados] = $this->prepararPedido();

        $anteriores = DB::table('trabalhos_fila')->count();

        $primeira = $this->actingAs($autor, 'sessao')
            ->postJson($url, $dados);

        $primeira->assertCreated()
            ->assertJsonPath('submissao_repetida', false);

        $depoisPrimeira = DB::table('trabalhos_fila')->count();

        self::assertGreaterThan(
            $anteriores,
            $depoisPrimeira,
            'As notificações devem ser enfileiradas antes do commit.',
        );

        $segunda = $this->postJson($url, $dados);

        $segunda->assertCreated()
            ->assertJsonPath('submissao_repetida', true);

        self::assertSame(
            $primeira->json('comentario.id'),
            $segunda->json('comentario.id'),
        );

        self::assertSame(
            $depoisPrimeira,
            DB::table('trabalhos_fila')->count(),
        );

        $this->assertDatabaseCount('comentarios', 1);
        $this->assertDatabaseCount('submissoes_comentarios', 1);
    }
}
