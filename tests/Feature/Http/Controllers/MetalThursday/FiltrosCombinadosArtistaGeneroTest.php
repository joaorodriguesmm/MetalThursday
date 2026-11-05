<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\MetalThursday;

use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\Edicao;
use App\Models\MetalThursday\MetalThursday;
use App\Models\MetalThursday\SeccaoMetalThursday;
use App\Models\MetalThursday\TipoSeccao;
use App\Models\Musica\Artista;
use App\Models\Musica\Genero;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class FiltrosCombinadosArtistaGeneroTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function artista_e_genero_devem_corresponder_a_mesma_seccao(): void
    {
        $this->withoutVite();

        $this->travelTo(
            CarbonImmutable::parse('2026-08-27 12:00:00', 'Europe/Lisbon'),
        );

        $autor = Utilizador::factory()->create();
        $this->actingAs($autor, 'sessao');

        $edicao = Edicao::factory()
            ->comPeriodo(
                CarbonImmutable::parse('2026-08-01'),
                CarbonImmutable::parse('2026-08-31'),
            )
            ->create();

        $metalThursday = MetalThursday::factory()
            ->comNome('Teste de filtros combinados')
            ->comData(CarbonImmutable::parse('2026-08-20'))
            ->comEdicao($edicao)
            ->comAutor($autor)
            ->create();

        $artistaFiltrado = Artista::factory()->create();
        $outroArtista = Artista::factory()->create();
        $generoFiltrado = Genero::factory()->create();

        $outroArtista->generos()->attach($generoFiltrado->getKey());

        $tipo = TipoSeccao::factory()->comDetalhes()->create();

        foreach ([$artistaFiltrado, $outroArtista] as $indice => $artista) {
            SeccaoMetalThursday::factory()
                ->paraMetalThursday($metalThursday)
                ->comDetalhes($artista)
                ->doTipo($tipo)
                ->naOrdem($indice + 1)
                ->create();
        }

        $filtros = [
            'filtro_artista' => $artistaFiltrado->getKey(),
            'filtro_genero' => $generoFiltrado->getKey(),
        ];

        $simplificada = $this->get(route('inicio', [
            ...$filtros,
            'vista' => 'simplificada',
        ]));

        $simplificada->assertOk();

        self::assertSame(
            0,
            $simplificada->viewData('seccoesSimplificadas')->total(),
        );

        $completa = $this->get(route('inicio', [
            ...$filtros,
            'vista' => 'completa',
        ]));

        $completa->assertOk();

        self::assertSame(
            0,
            $completa->viewData('registosMetalThursday')->total(),
        );
        // verificar_filtros_isolados
        foreach ([
            ['filtro_artista' => $artistaFiltrado->getKey()],
            ['filtro_genero' => $generoFiltrado->getKey()],
        ] as $filtroIsolado) {
            foreach ([
                'completa' => 'registosMetalThursday',
                'simplificada' => 'seccoesSimplificadas',
            ] as $vista => $paginador) {
                $resposta = $this->get(route('inicio', [
                    ...$filtroIsolado,
                    'vista' => $vista,
                ]));

                $resposta->assertOk();

                self::assertSame(1, $resposta->viewData($paginador)->total());
            }
        }

        $artistaFiltrado->generos()->attach($generoFiltrado->getKey());

        foreach ([
            'completa' => 'registosMetalThursday',
            'simplificada' => 'seccoesSimplificadas',
        ] as $vista => $paginador) {
            $resposta = $this->get(route('inicio', [
                ...$filtros,
                'vista' => $vista,
            ]));

            $resposta->assertOk();

            self::assertSame(1, $resposta->viewData($paginador)->total());
        }

    }
}
