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
 * Caracteriza a repeticao de pedidos de criacao de artistas.
 *
 * A chave identifica uma operacao, e nao o nome do artista.
 * Artistas homonimos continuam a ser permitidos quando sao operacoes distintas.
 */
final class IdempotenciaCriacaoArtistasTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    #[Test]
    public function repeticao_da_mesma_criacao_confirmada_nao_duplica_artista(): void
    {
        $utilizador = Utilizador::factory()->create();
        Artista::factory()->comNome('Ghost')->create();

        $dados = [
            'nome' => 'Ghost',
            'confirmar_nome_repetido' => 1,
            'chave_submissao' => (string) Str::uuid(),
        ];

        $url = route('artistas.guardar');

        $primeira = $this->actingAs($utilizador, 'sessao')
            ->postJson($url, $dados);

        $segunda = $this->postJson($url, $dados);

        $primeira->assertCreated();
        $segunda->assertCreated();

        self::assertSame(
            $primeira->json('artista.id'),
            $segunda->json('artista.id'),
        );

        $this->assertDatabaseCount('artistas', 2);
    }

    #[Test]
    public function repeticao_da_criacao_inicial_sem_confirmacao_devolve_o_mesmo_artista(): void
    {
        $utilizador = Utilizador::factory()->create();

        $dados = [
            'nome' => 'Moonspell',
            'chave_submissao' => (string) Str::uuid(),
        ];

        $url = route('artistas.guardar');

        $primeira = $this->actingAs($utilizador, 'sessao')
            ->postJson($url, $dados);

        $segunda = $this->postJson($url, $dados);

        $primeira->assertCreated();
        $segunda->assertCreated();

        self::assertSame(
            $primeira->json('artista.id'),
            $segunda->json('artista.id'),
        );

        $this->assertDatabaseCount('artistas', 1);
    }

    #[Test]
    public function mesma_chave_com_dados_diferentes_e_rejeitada(): void
    {
        $utilizador = Utilizador::factory()->create();
        Artista::factory()->comNome('Ghost')->create();

        $chave = (string) Str::uuid();
        $url = route('artistas.guardar');

        $this->actingAs($utilizador, 'sessao')
            ->postJson($url, [
                'nome' => 'Ghost',
                'confirmar_nome_repetido' => 1,
                'chave_submissao' => $chave,
            ])
            ->assertCreated();

        $this->postJson($url, [
            'nome' => 'Mercyful Fate',
            'confirmar_nome_repetido' => 1,
            'chave_submissao' => $chave,
        ])->assertStatus(409);

        $this->assertDatabaseCount('artistas', 2);
    }

    #[Test]
    public function chaves_distintas_permitem_criacoes_confirmadas_de_homonimos(): void
    {
        $utilizador = Utilizador::factory()->create();
        Artista::factory()->comNome('Ghost')->create();

        $url = route('artistas.guardar');

        $this->actingAs($utilizador, 'sessao')
            ->postJson($url, [
                'nome' => 'Ghost',
                'confirmar_nome_repetido' => 1,
                'chave_submissao' => (string) Str::uuid(),
            ])
            ->assertCreated();

        $this->postJson($url, [
            'nome' => 'Ghost',
            'confirmar_nome_repetido' => 1,
            'chave_submissao' => (string) Str::uuid(),
        ])->assertCreated();

        $this->assertDatabaseCount('artistas', 3);
    }
}
