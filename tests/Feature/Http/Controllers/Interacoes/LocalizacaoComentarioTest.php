<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Interacoes;

use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\MetalThursday;
use App\Models\MetalThursday\SeccaoMetalThursday;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Valida a localização de comentários sem revelar publicações indisponíveis.
 *
 * @since 2.0.0
 */
final class LocalizacaoComentarioTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function localiza_resposta_encadeada_de_uma_seccao(): void
    {
        $utilizador = Utilizador::factory()->create();
        $publicacao = MetalThursday::factory()->create();
        $seccao = SeccaoMetalThursday::factory()
            ->paraMetalThursday($publicacao)
            ->create();

        $raiz = $seccao->comentarios()->create([
            'utilizador_id' => $utilizador->id,
            'conteudo' => 'Comentário principal.',
            'comentario_pai_id' => null,
        ]);

        $resposta = $seccao->comentarios()->create([
            'utilizador_id' => $utilizador->id,
            'conteudo' => 'Resposta intermédia.',
            'comentario_pai_id' => $raiz->id,
        ]);

        $ultima = $seccao->comentarios()->create([
            'utilizador_id' => $utilizador->id,
            'conteudo' => 'Resposta final.',
            'comentario_pai_id' => $resposta->id,
        ]);

        $this->actingAs($utilizador, 'sessao')
            ->getJson(route('comentarios.localizacao', [
                'comentario' => $ultima->id,
            ]))
            ->assertOk()
            ->assertJson([
                'identificador_metal_thursday' => $publicacao->id,
                'tipo_comentavel' => $seccao->getMorphClass(),
                'identificador_comentavel' => $seccao->id,
                'caminho' => [$raiz->id, $resposta->id, $ultima->id],
            ]);
    }

    #[Test]
    public function localiza_comentario_principal_da_publicacao(): void
    {
        $utilizador = Utilizador::factory()->create();
        $publicacao = MetalThursday::factory()->create();
        $comentario = $publicacao->comentarios()->create([
            'utilizador_id' => $utilizador->id,
            'conteudo' => 'Comentário.',
            'comentario_pai_id' => null,
        ]);

        $this->actingAs($utilizador, 'sessao')
            ->getJson(route('comentarios.localizacao', [
                'comentario' => $comentario->id,
            ]))
            ->assertOk()
            ->assertJsonPath('caminho', [$comentario->id]);
    }

    #[Test]
    public function localiza_paginas_de_comentario_e_resposta_encadeada(): void
    {
        $utilizador = Utilizador::factory()->create();
        $publicacao = MetalThursday::factory()->create();
        $principal = $publicacao->comentarios()->create([
            'utilizador_id' => $utilizador->id,
            'conteudo' => 'Primeiro comentário.',
            'comentario_pai_id' => null,
        ]);

        for ($numero = 1; $numero <= 24; $numero++) {
            $publicacao->comentarios()->create([
                'utilizador_id' => $utilizador->id,
                'conteudo' => "Outro comentário {$numero}.",
                'comentario_pai_id' => null,
            ]);
        }

        $ultima = null;
        for ($numero = 1; $numero <= 25; $numero++) {
            $ultima = $publicacao->comentarios()->create([
                'utilizador_id' => $utilizador->id,
                'conteudo' => "Resposta {$numero}.",
                'comentario_pai_id' => $principal->id,
            ]);
        }

        self::assertNotNull($ultima);

        $this->actingAs($utilizador, 'sessao')
            ->getJson(route('comentarios.localizacao', ['comentario' => $ultima->id]))
            ->assertOk()
            ->assertJsonPath('caminho', [$principal->id, $ultima->id])
            ->assertJsonPath('paginas', [2, 2]);
    }

    #[Test]
    public function nao_revela_comentarios_de_publicacao_preparada(): void
    {
        $utilizador = Utilizador::factory()->create();
        $publicacao = MetalThursday::factory()->create();
        $comentario = $publicacao->comentarios()->create([
            'utilizador_id' => $utilizador->id,
            'conteudo' => 'Comentário preparado.',
            'comentario_pai_id' => null,
        ]);

        $publicacao->updateOrFail([
            'data' => CarbonImmutable::today()->addDays(10),
        ]);

        $this->actingAs($utilizador, 'sessao')
            ->getJson(route('comentarios.localizacao', [
                'comentario' => $comentario->id,
            ]))
            ->assertNotFound();
    }

    #[Test]
    public function rejeita_identificadores_invalidos_ou_inexistentes(): void
    {
        $utilizador = Utilizador::factory()->create();

        $this->actingAs($utilizador, 'sessao')
            ->getJson(route('comentarios.localizacao', ['comentario' => 'abc']))
            ->assertUnprocessable();

        $this->getJson(route('comentarios.localizacao', ['comentario' => 999999999]))
            ->assertNotFound();
    }
}
