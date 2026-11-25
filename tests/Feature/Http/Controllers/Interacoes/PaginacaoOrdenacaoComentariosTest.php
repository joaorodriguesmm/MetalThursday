<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Interacoes;

use App\Enumeracoes\Interacoes\TipoEntidadeInteracao;
use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\MetalThursday;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/** Confirma a paginação e ordenação de comentários e respostas. */
final class PaginacaoOrdenacaoComentariosTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function pagina_comentarios_principais_de_vinte_em_vinte(): void
    {
        $utilizador = Utilizador::factory()->create();
        $publicacao = MetalThursday::factory()->create();

        for ($numero = 1; $numero <= 25; $numero++) {
            $publicacao->comentarios()->create([
                'utilizador_id' => $utilizador->id,
                'conteudo' => "Comentário principal número {$numero}.",
                'comentario_pai_id' => null,
            ]);
        }

        $endereco = route('comentarios.indice', [
            'tipoComentavel' => TipoEntidadeInteracao::MetalThursday->value,
            'identificadorComentavel' => $publicacao->id,
        ]);

        $this->actingAs($utilizador, 'sessao');

        $primeira = $this->getJson($endereco)
            ->assertOk()
            ->assertJsonPath('pagina', 1)
            ->assertJsonPath('ordenacao', 'recentes')
            ->assertJsonPath('proxima_pagina', 2)
            ->assertJsonPath('numero_comentarios', 25);

        $html = $primeira->json('comentarios_html');
        self::assertIsString($html);
        self::assertSame(20, substr_count($html, 'class="comentario'));
        self::assertStringContainsString('Comentário principal número 25.', $html);
        self::assertStringNotContainsString('Comentário principal número 1.', $html);

        $segunda = $this->getJson($endereco.'?pagina=2')
            ->assertOk()
            ->assertJsonPath('pagina', 2)
            ->assertJsonPath('proxima_pagina', null);

        $html = $segunda->json('comentarios_html');
        self::assertIsString($html);
        self::assertStringContainsString('Comentário principal número 1.', $html);
        self::assertStringNotContainsString('Comentário principal número 25.', $html);
    }

    #[Test]
    public function permite_ordenar_principais_por_antiguidade_e_popularidade(): void
    {
        $utilizador = Utilizador::factory()->create();
        $outro = Utilizador::factory()->create();
        $publicacao = MetalThursday::factory()->create();
        $primeiro = $publicacao->comentarios()->create([
            'utilizador_id' => $utilizador->id,
            'conteudo' => 'Primeiro comentário para ordenar.',
            'comentario_pai_id' => null,
        ]);
        $publicacao->comentarios()->create([
            'utilizador_id' => $utilizador->id,
            'conteudo' => 'Segundo comentário para ordenar.',
            'comentario_pai_id' => null,
        ]);
        $primeiro->gostos()->create(['utilizador_id' => $outro->id]);

        $endereco = route('comentarios.indice', [
            'tipoComentavel' => TipoEntidadeInteracao::MetalThursday->value,
            'identificadorComentavel' => $publicacao->id,
        ]);

        $this->actingAs($utilizador, 'sessao');

        foreach (['antigos', 'populares'] as $ordenacao) {
            $resposta = $this->getJson($endereco.'?ordenacao='.$ordenacao)
                ->assertOk()
                ->assertJsonPath('ordenacao', $ordenacao);

            $html = $resposta->json('comentarios_html');
            self::assertIsString($html);
            $posicaoPrimeiro = strpos($html, 'Primeiro comentário para ordenar.');
            $posicaoSegundo = strpos($html, 'Segundo comentário para ordenar.');
            self::assertNotFalse($posicaoPrimeiro);
            self::assertNotFalse($posicaoSegundo);
            self::assertLessThan($posicaoSegundo, $posicaoPrimeiro);
        }
    }

    #[Test]
    public function pagina_respostas_diretas_sem_perder_o_total(): void
    {
        $utilizador = Utilizador::factory()->create();
        $publicacao = MetalThursday::factory()->create();
        $pai = $publicacao->comentarios()->create([
            'utilizador_id' => $utilizador->id,
            'conteudo' => 'Comentário com muitas respostas.',
            'comentario_pai_id' => null,
        ]);

        for ($numero = 1; $numero <= 25; $numero++) {
            $publicacao->comentarios()->create([
                'utilizador_id' => $utilizador->id,
                'conteudo' => "Resposta número {$numero}.",
                'comentario_pai_id' => $pai->id,
            ]);
        }

        $endereco = route('comentarios.respostas.indice', $pai);
        $this->actingAs($utilizador, 'sessao');

        $primeira = $this->getJson($endereco)
            ->assertOk()
            ->assertJsonPath('pagina', 1)
            ->assertJsonPath('proxima_pagina', 2)
            ->assertJsonPath('numero_respostas', 25);

        self::assertCount(20, $primeira->json('respostas'));
        $this->getJson($endereco.'?pagina=2')
            ->assertOk()
            ->assertJsonPath('pagina', 2)
            ->assertJsonPath('proxima_pagina', null)
            ->assertJsonPath('numero_respostas', 25)
            ->assertJsonCount(5, 'respostas');

        $this->getJson($endereco.'?pagina=0')->assertUnprocessable();
    }
}
