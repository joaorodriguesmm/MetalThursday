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
 * Garante que os indicadores HTML das interações escapam nomes de utilizadores.
 *
 * Os fragmentos são enviados por JSON e apresentados em tooltips no navegador.
 *
 * @since 2.0.0
 */
final class ProtecaoXssIndicadoresInteracoesTest extends TestCase
{
    use RefreshDatabase;

    private const NOME_COM_HTML = 'Pessoa "><img src=x onerror=alert(1)>';

    /**
     * Confirma que a alternância e a consulta dos gostos escapam o nome no HTML.
     *
     * @since 2.0.0
     */
    #[Test]
    public function gostos_escapam_nome_no_indicador_html(): void
    {
        Notification::fake();

        $autor = Utilizador::factory()->create();

        $utilizador = Utilizador::factory()->create([
            'nome' => self::NOME_COM_HTML,
        ]);

        $metalThursday = MetalThursday::factory()->create();

        $comentario = $metalThursday->comentarios()->create([
            'utilizador_id' => $autor->getKey(),
            'conteudo' => 'Comentário para testar o indicador.',
            'comentario_pai_id' => null,
        ]);

        $this->actingAs($utilizador, 'sessao')
            ->postJson(route('gostos.alternar', $comentario))
            ->assertOk()
            ->assertJsonPath('numero_gostos', 1)
            ->assertJsonPath('conteudo_indicador_html', e(self::NOME_COM_HTML));

        $this->actingAs($utilizador, 'sessao')
            ->getJson(route('comentarios.utilizadores-gosto', $comentario))
            ->assertOk()
            ->assertJsonPath('nomes.0', self::NOME_COM_HTML)
            ->assertJsonPath('conteudo_indicador_html', e(self::NOME_COM_HTML));
    }

    /**
     * Confirma que os nomes das audições são escapados antes de chegar ao tooltip.
     *
     * @since 2.0.0
     */
    #[Test]
    public function audicoes_escapam_nome_no_indicador_html(): void
    {
        Notification::fake();

        $utilizador = Utilizador::factory()->create([
            'nome' => self::NOME_COM_HTML,
        ]);

        $metalThursday = MetalThursday::factory()->create();

        $this->actingAs($utilizador, 'sessao')
            ->postJson(route('audicoes.alternar', [
                'tipoAudivel' => TipoEntidadeInteracao::MetalThursday->value,
                'identificadorAudivel' => $metalThursday->getKey(),
            ]))
            ->assertOk()
            ->assertJsonPath('numero_audicoes', 1)
            ->assertJsonPath('conteudo_indicador_html', e(self::NOME_COM_HTML));
    }

    /**
     * Confirma que nomes perigosos não se tornam HTML nos indicadores de avaliação.
     *
     * @since 2.0.0
     */
    #[Test]
    public function avaliacoes_escapam_nome_no_indicador_html(): void
    {
        Notification::fake();

        $utilizador = Utilizador::factory()->create([
            'nome' => self::NOME_COM_HTML,
        ]);

        $metalThursday = MetalThursday::factory()->create();

        $this->actingAs($utilizador, 'sessao')
            ->postJson(route('avaliacoes.guardar', [
                'tipoAvaliavel' => TipoEntidadeInteracao::MetalThursday->value,
                'identificadorAvaliavel' => $metalThursday->getKey(),
            ]), [
                'pontuacao' => '8,5',
            ])
            ->assertOk()
            ->assertJsonPath('numero_avaliacoes', 1)
            ->assertJsonPath(
                'conteudo_indicador_html',
                e(self::NOME_COM_HTML).': 8,5',
            );
    }
}
