<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\MetalThursday;

use App\Enumeracoes\PapelUtilizador;
use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\Edicao;
use App\Models\MetalThursday\TipoSeccao;
use App\Models\Musica\Artista;
use App\Servicos\MetalThursday\ServicoPersistenciaMetalThursday;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa o editor de ligações múltiplas nos formulários de MetalThursday.
 *
 * @since 2.0.0
 */
final class ControladorMetalThursdayFormularioLigacoesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    #[Test]
    public function formulario_criacao_expoe_editor_de_ligacoes_multiplas(): void
    {
        $administrador = Utilizador::factory()
            ->comPapel(
                PapelUtilizador::Administrador,
            )
            ->create();

        $this->actingAs(
            $administrador,
            'sessao',
        );

        $this->criarEdicao();

        $this
            ->get(
                route(
                    'metal-thursday.criar',
                ),
            )
            ->assertOk()
            ->assertSee(
                'Adicionar ligação',
            )
            ->assertSeeHtml(
                'data-editor-ligacoes-seccao',
            )
            ->assertSeeHtml(
                'name="seccoes[__INDICE_SECCAO__][ligacoes][__INDICE_LIGACAO__][url]"',
            )
            ->assertSeeHtml(
                'name="seccoes[__INDICE_SECCAO__][ligacoes][__INDICE_LIGACAO__][incorporar]"',
            );
    }

    #[Test]
    public function formulario_edicao_apresenta_ligacoes_persistidas(): void
    {
        $administrador = Utilizador::factory()
            ->comPapel(
                PapelUtilizador::Administrador,
            )
            ->create();

        $this->actingAs(
            $administrador,
            'sessao',
        );

        $edicao = $this->criarEdicao();

        $tipoSeccao = TipoSeccao::factory()
            ->comDados(
                'musica',
                'Música',
                'Música recomendada.',
            )
            ->comDetalhes()
            ->create();

        $artista = Artista::factory()
            ->create();

        $metalThursday = app(
            ServicoPersistenciaMetalThursday::class,
        )->criar([
            'edicao_id' => (int) $edicao->getKey(),
            'data' => '2026-01-15',
            'nome' => null,
            'autor_id' => (int) $administrador->getKey(),
            'proximo_nomeado_id' => null,
            'seccoes' => [
                [
                    'id' => null,
                    'tipo_seccao_id' => (int) $tipoSeccao->getKey(),
                    'titulo' => 'Battery',
                    'descricao' => 'Faixa de abertura de Master of Puppets.',
                    'artista_id' => (int) $artista->getKey(),
                    'lancamento_id' => null,
                    'ligacoes' => [
                        [
                            'url' => 'https://open.spotify.com/track/0cFQ4VtZtV3s8y2lKp0x9Q',
                            'etiqueta' => null,
                            'incorporar' => true,
                        ],
                        [
                            'url' => 'https://bandcamp.com/track/battery',
                            'etiqueta' => 'Comprar',
                            'incorporar' => false,
                        ],
                    ],
                    'ano' => 1986,
                ],
            ],
        ]);

        $this
            ->get(
                route(
                    'metal-thursday.editar',
                    $metalThursday,
                ),
            )
            ->assertOk()
            ->assertSeeHtml(
                'name="seccoes[0][ligacoes][0][url]"',
            )
            ->assertSeeHtml(
                'value="https://open.spotify.com/track/0cFQ4VtZtV3s8y2lKp0x9Q"',
            )
            ->assertSeeHtml(
                'name="seccoes[0][ligacoes][1][etiqueta]"',
            )
            ->assertSeeHtml(
                'value="Comprar"',
            );
    }

    private function criarEdicao(): Edicao
    {
        return Edicao::factory()
            ->comPeriodo(
                CarbonImmutable::parse(
                    '2026-01-01',
                ),
                CarbonImmutable::parse(
                    '2026-01-31',
                ),
            )
            ->create();
    }
}
