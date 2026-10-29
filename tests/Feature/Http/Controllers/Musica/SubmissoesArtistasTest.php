<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Musica;

use App\Models\Autenticacao\Utilizador;
use App\Models\Musica\Artista;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Confirma o contrato HTTP da criação idempotente de artistas.
 *
 * @since 2.0.0
 */
final class SubmissoesArtistasTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    #[Test]
    public function confirmacao_de_homonimo_conserva_chave_e_repeticao_recupera_criacao(): void
    {
        $utilizador = Utilizador::factory()->create();
        Artista::factory()->comNome('Ghost')->create();

        $chave = (string) Str::uuid();
        $url = route('artistas.guardar');

        $this->actingAs($utilizador, 'sessao')
            ->postJson($url, [
                'nome' => 'Ghost',
                'chave_submissao' => $chave,
            ])
            ->assertStatus(409)
            ->assertJsonPath('codigo', 'confirmacao_nome_repetido_necessaria');

        $primeira = $this->postJson($url, [
            'nome' => 'Ghost',
            'confirmar_nome_repetido' => 1,
            'chave_submissao' => $chave,
        ])->assertCreated();

        $repetida = $this->postJson($url, [
            'nome' => 'Ghost',
            'chave_submissao' => $chave,
        ])->assertCreated();

        self::assertSame(
            $primeira->json('artista.id'),
            $repetida->json('artista.id'),
        );

        $this->assertDatabaseCount('artistas', 2);
        $this->assertDatabaseCount('submissoes_artistas', 1);
        $this->assertDatabaseCount('bloqueios_nomes_artistas', 1);
    }

    #[Test]
    public function pedidos_anteriores_sem_chave_continuam_a_ser_aceites(): void
    {
        $utilizador = Utilizador::factory()->create();

        $this->actingAs($utilizador, 'sessao')
            ->postJson(route('artistas.guardar'), [
                'nome' => 'Moonspell',
            ])
            ->assertCreated();

        $this->assertDatabaseCount('artistas', 1);
        $this->assertDatabaseCount('submissoes_artistas', 0);
    }

    #[Test]
    public function rejeita_chave_de_submissao_invalida(): void
    {
        $utilizador = Utilizador::factory()->create();

        $this->actingAs($utilizador, 'sessao')
            ->postJson(route('artistas.guardar'), [
                'nome' => 'Moonspell',
                'chave_submissao' => 'invalida',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('chave_submissao');

        $this->assertDatabaseCount('artistas', 0);
        $this->assertDatabaseCount('submissoes_artistas', 0);
    }

    #[Test]
    public function nao_recupera_artista_eliminado_numa_repeticao(): void
    {
        $utilizador = Utilizador::factory()->create();
        $dados = [
            'nome' => 'Moonspell',
            'chave_submissao' => (string) Str::uuid(),
        ];

        $url = route('artistas.guardar');

        $resposta = $this->actingAs($utilizador, 'sessao')
            ->postJson($url, $dados)
            ->assertCreated();

        $artista = Artista::query()->findOrFail(
            (int) $resposta->json('artista.id'),
        );

        $artista->deleteOrFail();

        $this->postJson($url, $dados)
            ->assertStatus(410);

        $this->assertDatabaseCount('artistas', 1);
        $this->assertDatabaseCount('submissoes_artistas', 1);
    }
}
