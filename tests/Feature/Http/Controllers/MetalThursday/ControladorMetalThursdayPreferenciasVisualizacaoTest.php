<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\MetalThursday;

use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\LigacaoSeccaoMetalThursday;
use App\Models\MetalThursday\MetalThursday;
use App\Models\MetalThursday\SeccaoMetalThursday;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Confirma que a apresentação depende de quem consulta a MetalThursday.
 *
 * @since 2.0.0
 */
final class ControladorMetalThursdayPreferenciasVisualizacaoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    #[Test]
    public function preferencia_do_leitor_substitui_configuracao_antiga_da_ligacao(): void
    {
        $autor = Utilizador::factory()->create();
        $leitor = Utilizador::factory()->create();

        $metalThursday = MetalThursday::factory()
            ->comAutor($autor)
            ->create();

        $seccao = SeccaoMetalThursday::factory()
            ->paraMetalThursday($metalThursday)
            ->comDetalhes()
            ->create();

        LigacaoSeccaoMetalThursday::factory()->create([
            'seccao_metal_thursday_id' => (int) $seccao->getKey(),
            'url' => 'https://open.spotify.com/track/11dFghVXANMlKmJXsNCbNl',
            'incorporar' => false,
            'ordem' => 1,
        ]);

        $this->actingAs($leitor, 'sessao')
            ->get(route('metal-thursday.detalhes', $metalThursday))
            ->assertOk()
            ->assertSee('d-flex flex-wrap align-items-start gap-2 mt-3', false)
            ->assertDontSee('open.spotify.com/embed/track', false)
            ->assertSee('Abrir no Spotify');

        $leitor->preferencias_visualizacao_ligacoes = [
            'spotify' => 'incorporar',
            'apple_music' => 'ligacao',
            'youtube' => 'ligacao',
        ];
        $leitor->save();

        $this->get(route('metal-thursday.detalhes', $metalThursday))
            ->assertOk()
            ->assertSee('open.spotify.com/embed/track', false);

        $leitor->preferencias_visualizacao_ligacoes = [
            'spotify' => 'ocultar',
            'apple_music' => 'ligacao',
            'youtube' => 'ligacao',
        ];
        $leitor->save();

        $this->get(route('metal-thursday.detalhes', $metalThursday))
            ->assertOk()
            ->assertDontSee('open.spotify.com/embed/track', false)
            ->assertDontSee('Abrir no Spotify');

        $this->get(route('inicio', ['vista' => 'simplificada']))
            ->assertOk()
            ->assertDontSee('Abrir Spotify', false)
            ->assertDontSee('open.spotify.com/track/11dFghVXANMlKmJXsNCbNl', false);
    }

    #[Test]
    public function oculta_apple_music_mas_mantem_playlists_youtube_como_ligacoes(): void
    {
        $utilizador = Utilizador::factory()->create([
            'nome' => 'Utilizador 1',
        ]);

        $utilizador->preferencias_visualizacao_ligacoes = [
            'apple_music' => 'ocultar',
            'spotify' => 'ligacao',
            'youtube' => 'incorporar',
        ];
        $utilizador->save();

        $metalThursday = MetalThursday::factory()
            ->comAutor($utilizador)
            ->create();

        $seccao = SeccaoMetalThursday::factory()
            ->paraMetalThursday($metalThursday)
            ->comDetalhes()
            ->create();

        foreach ([
            'https://music.apple.com/pt/album/teste/1234567890',
            'https://music.youtube.com/playlist?list=PL1234567890',
            'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        ] as $indice => $url) {
            LigacaoSeccaoMetalThursday::factory()->create([
                'seccao_metal_thursday_id' => (int) $seccao->getKey(),
                'url' => $url,
                'incorporar' => false,
                'ordem' => $indice + 1,
            ]);
        }

        $resposta = $this->actingAs($utilizador, 'sessao')
            ->get(route('metal-thursday.detalhes', $metalThursday));

        $resposta
            ->assertOk()
            ->assertDontSee('music.apple.com/pt/album/teste', false)
            ->assertSee('https://music.youtube.com/playlist?list=PL1234567890', false)
            ->assertSee('Abrir no YouTube Music')
            ->assertSee('Abrir no YouTube')
            ->assertDontSee('Abrir no YouTube / YouTube Music')
            ->assertSee('youtube-nocookie.com/embed/dQw4w9WgXcQ', false);

        self::assertSame(
            1,
            substr_count((string) $resposta->getContent(), 'youtube-nocookie.com/embed/'),
        );

        $this->get(route('inicio', ['vista' => 'simplificada']))
            ->assertOk()
            ->assertDontSee('music.apple.com/pt/album/teste', false)
            ->assertSee('https://music.youtube.com/playlist?list=PL1234567890', false)
            ->assertSee('aria-label="Abrir YouTube Music', false)
            ->assertSee('aria-label="Abrir YouTube de', false);
    }
}
