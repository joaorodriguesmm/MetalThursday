<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Interacoes;

use App\Enumeracoes\Interacoes\TipoEntidadeInteracao;
use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\MetalThursday;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Confirma que o conteúdo dos comentários nunca é interpretado como HTML.
 *
 * @since 2.0.0
 */
final class ProtecaoXssComentariosTest extends TestCase
{
    use RefreshDatabase;

    private const CONTEUDO_HTML =
        '<svg onload="alert(1)">Texto de teste</svg>';

    /**
     * O carregamento assíncrono deve escapar o conteúdo já persistido.
     *
     * @since 2.0.0
     */
    #[Test]
    public function listagem_escapa_conteudo_html_do_comentario(): void
    {
        $autor = Utilizador::factory()->create();
        $metalThursday = MetalThursday::factory()->create();

        $metalThursday->comentarios()->create([
            'utilizador_id' => $autor->getKey(),
            'conteudo' => self::CONTEUDO_HTML,
            'comentario_pai_id' => null,
        ]);

        $resposta = $this
            ->actingAs($autor, 'sessao')
            ->getJson(route('comentarios.indice', [
                'tipoComentavel' => TipoEntidadeInteracao::MetalThursday->value,
                'identificadorComentavel' => $metalThursday->getKey(),
            ]));

        $resposta->assertOk()->assertJsonPath('numero_comentarios', 1);

        $this->confirmarHtmlEscapado(
            $resposta->json('comentarios_html'),
        );
    }

    /**
     * A publicação deve devolver JSON com texto original e HTML escapado.
     *
     * @since 2.0.0
     */
    #[Test]
    public function publicacao_devolve_fragmento_html_escapado(): void
    {
        Notification::fake();

        $autor = Utilizador::factory()->create();
        $metalThursday = MetalThursday::factory()->create();

        $resposta = $this
            ->actingAs($autor, 'sessao')
            ->postJson(route('comentarios.guardar', [
                'tipoComentavel' => TipoEntidadeInteracao::MetalThursday->value,
                'identificadorComentavel' => $metalThursday->getKey(),
            ]), [
                'conteudo' => self::CONTEUDO_HTML,
            ]);

        $resposta
            ->assertCreated()
            ->assertJsonPath('comentario.conteudo', self::CONTEUDO_HTML);

        $this->confirmarHtmlEscapado(
            $resposta->json('comentario_html'),
        );
    }

    /**
     * As respostas não devem introduzir HTML activo ao serem inseridas no DOM.
     *
     * @since 2.0.0
     */
    #[Test]
    public function resposta_publicada_e_consultada_mantem_html_escapado(): void
    {
        Notification::fake();

        $autor = Utilizador::factory()->create();
        $metalThursday = MetalThursday::factory()->create();

        $comentarioPai = $metalThursday->comentarios()->create([
            'utilizador_id' => $autor->getKey(),
            'conteudo' => 'Comentário principal.',
            'comentario_pai_id' => null,
        ]);

        $publicacao = $this
            ->actingAs($autor, 'sessao')
            ->postJson(route('comentarios.respostas.guardar', $comentarioPai), [
                'conteudo' => self::CONTEUDO_HTML,
            ]);

        $publicacao->assertCreated()
            ->assertJsonPath('comentario.conteudo', self::CONTEUDO_HTML);

        $this->confirmarHtmlEscapado(
            $publicacao->json('comentario_html'),
        );

        $consulta = $this
            ->actingAs($autor, 'sessao')
            ->getJson(route('comentarios.respostas.indice', $comentarioPai));

        $consulta->assertOk()->assertJsonPath('numero_respostas', 1);

        $this->confirmarHtmlEscapado(
            $consulta->json('respostas.0.comentario_html'),
        );
    }

    /**
     * A edição pode devolver texto original em JSON, mas não HTML executável.
     *
     * @since 2.0.0
     */
    #[Test]
    public function edicao_persistida_e_renderizada_escapa_html(): void
    {
        $autor = Utilizador::factory()->create();
        $metalThursday = MetalThursday::factory()->create();

        $comentario = $metalThursday->comentarios()->create([
            'utilizador_id' => $autor->getKey(),
            'conteudo' => 'Comentário original.',
            'comentario_pai_id' => null,
        ]);

        $this->actingAs($autor, 'sessao')
            ->patchJson(route('comentarios.atualizar', $comentario), [
                'conteudo' => self::CONTEUDO_HTML,
            ])
            ->assertOk()
            ->assertJsonPath('comentario.conteudo', self::CONTEUDO_HTML);

        $consulta = $this->actingAs($autor, 'sessao')
            ->getJson(route('comentarios.indice', [
                'tipoComentavel' => TipoEntidadeInteracao::MetalThursday->value,
                'identificadorComentavel' => $metalThursday->getKey(),
            ]));

        $consulta->assertOk();

        $this->confirmarHtmlEscapado(
            $consulta->json('comentarios_html'),
        );
    }

    /**
     * Verifica o escape sem proibir as tags legítimas do componente Blade.
     *
     * @param  mixed  $html  Fragmento devolvido pelo servidor.
     *
     * @since 2.0.0
     */
    private function confirmarHtmlEscapado(mixed $html): void
    {
        self::assertIsString($html);

        self::assertStringContainsString(
            e(self::CONTEUDO_HTML),
            $html,
        );

        self::assertStringNotContainsString(
            self::CONTEUDO_HTML,
            $html,
        );
    }
}
