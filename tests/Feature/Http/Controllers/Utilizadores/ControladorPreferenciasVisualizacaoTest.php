<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Utilizadores;

use App\Enumeracoes\ModoVisualizacaoLigacao;
use App\Enumeracoes\PlataformaLigacao;
use App\Models\Autenticacao\Utilizador;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Verifica a personalização das plataformas no perfil autenticado.
 *
 * @since 2.0.0
 */
final class ControladorPreferenciasVisualizacaoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    #[Test]
    public function perfil_apresenta_preferencias_com_ligacoes_por_defeito(): void
    {
        $utilizador = Utilizador::factory()->create();

        $this->actingAs($utilizador, 'sessao')
            ->get(route('perfil.editar'))
            ->assertOk()
            ->assertSee('Preferências de visualização')
            ->assertSeeHtml('name="plataformas[apple_music]"')
            ->assertSeeHtml('name="plataformas[spotify]"')
            ->assertSeeHtml('name="plataformas[youtube]"')
            ->assertSee('Mostrar ligação');

        self::assertSame(
            ModoVisualizacaoLigacao::Ligacao,
            $utilizador->modoVisualizacaoLigacao(PlataformaLigacao::Spotify),
        );
    }

    #[Test]
    public function guarda_preferencias_apenas_na_conta_autenticada(): void
    {
        $utilizador = Utilizador::factory()->create();
        $outro = Utilizador::factory()->create();

        $this->actingAs($utilizador, 'sessao')
            ->patch(route('perfil.visualizacao.atualizar'), [
                'plataformas' => [
                    'apple_music' => 'ocultar',
                    'spotify' => 'incorporar',
                    'youtube' => 'ligacao',
                ],
            ])
            ->assertRedirect(route('perfil.editar'))
            ->assertSessionHas('sucesso');

        self::assertSame(
            ModoVisualizacaoLigacao::Ocultar,
            $utilizador->fresh()->modoVisualizacaoLigacao(PlataformaLigacao::AppleMusic),
        );

        self::assertSame(
            ModoVisualizacaoLigacao::Incorporar,
            $utilizador->fresh()->modoVisualizacaoLigacao(PlataformaLigacao::Spotify),
        );

        self::assertSame(
            ModoVisualizacaoLigacao::Ligacao,
            $outro->fresh()->modoVisualizacaoLigacao(PlataformaLigacao::AppleMusic),
        );
    }

    #[Test]
    public function rejeita_modos_invalidos_e_campos_desconhecidos(): void
    {
        $utilizador = Utilizador::factory()->create();

        $this->actingAs($utilizador, 'sessao')
            ->from(route('perfil.editar'))
            ->patch(route('perfil.visualizacao.atualizar'), [
                'plataformas' => [
                    'apple_music' => 'ocultar',
                    'spotify' => 'qualquer',
                    'youtube' => 'ligacao',
                    'desconhecida' => 'incorporar',
                ],
            ])
            ->assertRedirect(route('perfil.editar'))
            ->assertSessionHasErrors(
                ['plataformas', 'plataformas.spotify'],
                null,
                'preferenciasVisualizacao',
            );

        self::assertNull($utilizador->fresh()->preferencias_visualizacao_ligacoes);
    }

    #[Test]
    public function impede_atualizacao_sem_sessao(): void
    {
        $this->patch(route('perfil.visualizacao.atualizar'), [
            'plataformas' => [
                'apple_music' => 'ocultar',
                'spotify' => 'ocultar',
                'youtube' => 'ocultar',
            ],
        ])->assertRedirect();
    }
}
