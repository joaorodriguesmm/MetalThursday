<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Interacoes;

use App\Enumeracoes\Interacoes\TipoEntidadeInteracao;
use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\MetalThursday;
use App\Models\MetalThursday\SeccaoMetalThursday;
use App\Notifications\NotificacaoInteracaoUtilizador;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class IdempotenciaComentariosHttpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
    }

    private function urlComentario(
        MetalThursday|SeccaoMetalThursday $alvo,
    ): string {
        $tipo = $alvo instanceof MetalThursday
            ? TipoEntidadeInteracao::MetalThursday->value
            : TipoEntidadeInteracao::SeccaoMetalThursday->value;

        return route('comentarios.guardar', [
            'tipoComentavel' => $tipo,
            'identificadorComentavel' => $alvo->id,
        ]);
    }

    private function chave(): string
    {
        return (string) Str::uuid();
    }

    #[Test]
    public function comentario_repetido_preserva_id_e_uma_notificacao(): void
    {
        $autor = Utilizador::factory()->create();
        $destinatario = Utilizador::factory()->create();
        $alvo = MetalThursday::factory()->create();

        $url = $this->urlComentario($alvo);

        $dados = [
            'conteudo' => 'Comentário idempotente.',
            'chave_submissao' => $this->chave(),
        ];

        $primeira = $this->actingAs($autor, 'sessao')
            ->postJson($url, $dados);

        $segunda = $this->postJson($url, $dados);

        $primeira->assertCreated()
            ->assertJsonPath('submissao_repetida', false);

        $segunda->assertCreated()
            ->assertJsonPath('submissao_repetida', true);

        self::assertSame(
            $primeira->json('comentario.id'),
            $segunda->json('comentario.id'),
        );

        $this->assertDatabaseCount('comentarios', 1);
        $this->assertDatabaseCount('submissoes_comentarios', 1);

        Notification::assertSentTo(
            $destinatario,
            NotificacaoInteracaoUtilizador::class,
            1,
        );
    }

    #[Test]
    public function comentario_de_seccao_repetido_nao_e_duplicado(): void
    {
        $autor = Utilizador::factory()->create();
        $alvo = SeccaoMetalThursday::factory()->create();

        $dados = [
            'conteudo' => 'Comentário da secção.',
            'chave_submissao' => $this->chave(),
        ];

        $url = $this->urlComentario($alvo);

        $primeira = $this->actingAs($autor, 'sessao')
            ->postJson($url, $dados);

        $segunda = $this->postJson($url, $dados);

        $primeira->assertCreated();
        $segunda->assertCreated()
            ->assertJsonPath('submissao_repetida', true);

        self::assertSame(
            $primeira->json('comentario.id'),
            $segunda->json('comentario.id'),
        );

        $this->assertDatabaseCount('comentarios', 1);
        $this->assertDatabaseCount('submissoes_comentarios', 1);
    }

    #[Test]
    public function resposta_repetida_nao_e_duplicada(): void
    {
        $autor = Utilizador::factory()->create();
        $destinatario = Utilizador::factory()->create();

        $alvo = MetalThursday::factory()->create();

        $pai = $alvo->comentarios()->create([
            'utilizador_id' => $destinatario->id,
            'conteudo' => 'Comentário pai.',
            'comentario_pai_id' => null,
        ]);

        $dados = [
            'conteudo' => 'Resposta idempotente.',
            'chave_submissao' => $this->chave(),
        ];

        $url = route('comentarios.respostas.guardar', $pai);

        $primeira = $this->actingAs($autor, 'sessao')
            ->postJson($url, $dados);

        $segunda = $this->postJson($url, $dados);

        $primeira->assertCreated()
            ->assertJsonPath('submissao_repetida', false);

        $segunda->assertCreated()
            ->assertJsonPath('submissao_repetida', true);

        self::assertSame(
            $primeira->json('comentario.id'),
            $segunda->json('comentario.id'),
        );

        $this->assertDatabaseCount('comentarios', 2);
        $this->assertDatabaseCount('submissoes_comentarios', 1);

        Notification::assertSentTo(
            $destinatario,
            NotificacaoInteracaoUtilizador::class,
            1,
        );
    }

    #[Test]
    public function mesma_chave_com_conteudo_diferente_devolve_409(): void
    {
        $autor = Utilizador::factory()->create();
        $url = $this->urlComentario(
            MetalThursday::factory()->create(),
        );

        $chave = $this->chave();

        $this->actingAs($autor, 'sessao')
            ->postJson($url, [
                'conteudo' => 'Conteúdo original.',
                'chave_submissao' => $chave,
            ])
            ->assertCreated();

        $this->postJson($url, [
            'conteudo' => 'Conteúdo diferente.',
            'chave_submissao' => $chave,
        ])->assertStatus(409);

        $this->assertDatabaseCount('comentarios', 1);
        $this->assertDatabaseCount('submissoes_comentarios', 1);
    }

    #[Test]
    public function mesma_chave_noutro_alvo_devolve_409(): void
    {
        $autor = Utilizador::factory()->create();
        $primeiroAlvo = MetalThursday::factory()->create();
        $segundoAlvo = MetalThursday::factory()->create();

        $dados = [
            'conteudo' => 'Mesmo conteúdo.',
            'chave_submissao' => $this->chave(),
        ];

        $this->actingAs($autor, 'sessao')
            ->postJson($this->urlComentario($primeiroAlvo), $dados)
            ->assertCreated();

        $this->postJson(
            $this->urlComentario($segundoAlvo),
            $dados,
        )->assertStatus(409);

        $this->assertDatabaseCount('comentarios', 1);
        $this->assertDatabaseCount('submissoes_comentarios', 1);
    }

    #[Test]
    public function chaves_diferentes_permitem_comentarios_iguais(): void
    {
        $autor = Utilizador::factory()->create();
        $url = $this->urlComentario(
            MetalThursday::factory()->create(),
        );

        $primeira = $this->actingAs($autor, 'sessao')
            ->postJson($url, [
                'conteudo' => 'Texto igual.',
                'chave_submissao' => $this->chave(),
            ]);

        $segunda = $this->postJson($url, [
            'conteudo' => 'Texto igual.',
            'chave_submissao' => $this->chave(),
        ]);

        $primeira->assertCreated();
        $segunda->assertCreated();

        self::assertNotSame(
            $primeira->json('comentario.id'),
            $segunda->json('comentario.id'),
        );

        $this->assertDatabaseCount('comentarios', 2);
        $this->assertDatabaseCount('submissoes_comentarios', 2);
    }

    #[Test]
    public function mesma_chave_e_independente_por_utilizador(): void
    {
        $primeiroAutor = Utilizador::factory()->create();
        $segundoAutor = Utilizador::factory()->create();

        $url = $this->urlComentario(
            MetalThursday::factory()->create(),
        );

        $dados = [
            'conteudo' => 'Mesmo conteúdo e UUID.',
            'chave_submissao' => $this->chave(),
        ];

        $primeira = $this->actingAs($primeiroAutor, 'sessao')
            ->postJson($url, $dados);

        $segunda = $this->actingAs($segundoAutor, 'sessao')
            ->postJson($url, $dados);

        $primeira->assertCreated();
        $segunda->assertCreated();

        self::assertNotSame(
            $primeira->json('comentario.id'),
            $segunda->json('comentario.id'),
        );

        $this->assertDatabaseCount('comentarios', 2);
        $this->assertDatabaseCount('submissoes_comentarios', 2);
    }

    #[Test]
    public function pedidos_sem_chave_mantem_compatibilidade_anterior(): void
    {
        $autor = Utilizador::factory()->create();
        $url = $this->urlComentario(
            MetalThursday::factory()->create(),
        );

        $dados = [
            'conteudo' => 'Sem chave de submissão.',
        ];

        $primeira = $this->actingAs($autor, 'sessao')
            ->postJson($url, $dados);

        $segunda = $this->postJson($url, $dados);

        $primeira->assertCreated();
        $segunda->assertCreated();

        self::assertNotSame(
            $primeira->json('comentario.id'),
            $segunda->json('comentario.id'),
        );

        $this->assertDatabaseCount('comentarios', 2);
        $this->assertDatabaseCount('submissoes_comentarios', 0);
    }

    #[Test]
    public function chave_invalida_e_rejeitada(): void
    {
        $autor = Utilizador::factory()->create();
        $url = $this->urlComentario(
            MetalThursday::factory()->create(),
        );

        $this->actingAs($autor, 'sessao')
            ->postJson($url, [
                'conteudo' => 'Conteúdo válido.',
                'chave_submissao' => 'chave-invalida',
            ])->assertUnprocessable();

        $this->assertDatabaseCount('comentarios', 0);
        $this->assertDatabaseCount('submissoes_comentarios', 0);
    }
}
