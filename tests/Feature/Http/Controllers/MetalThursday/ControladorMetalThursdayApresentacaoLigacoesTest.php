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
 * Testa a apresentação pública das ligações das secções MetalThursday.
 *
 * @since 2.0.0
 */
final class ControladorMetalThursdayApresentacaoLigacoesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Confirma que a vista completa usa exclusivamente a nova coleção de
     * ligações e respeita a ordem persistida.
     *
     * @since 2.0.0
     */
    #[Test]
    public function detalhes_apresentam_novas_ligacoes_pela_ordem(): void
    {
        $utilizador = Utilizador::factory()
            ->create();

        $this->actingAs(
            $utilizador,
            'sessao',
        );

        $metalThursday = MetalThursday::factory()
            ->comAutor(
                $utilizador,
            )
            ->create();

        $seccao = SeccaoMetalThursday::factory()
            ->paraMetalThursday(
                $metalThursday,
            )
            ->comDetalhes()
            ->create();

        LigacaoSeccaoMetalThursday::factory()
            ->create([
                'seccao_metal_thursday_id' => (int) $seccao->getKey(),
                'etiqueta' => 'Segundo recurso',
                'url' => 'https://example.com/segundo',
                'incorporar' => false,
                'ordem' => 2,
            ]);

        LigacaoSeccaoMetalThursday::factory()
            ->create([
                'seccao_metal_thursday_id' => (int) $seccao->getKey(),
                'etiqueta' => 'Primeiro recurso',
                'url' => 'https://example.com/primeiro',
                'incorporar' => false,
                'ordem' => 1,
            ]);

        $resposta = $this->get(
            route(
                'metal-thursday.detalhes',
                $metalThursday,
            ),
        );

        $resposta
            ->assertOk()
            ->assertSeeInOrder([
                'Primeiro recurso',
                'Segundo recurso',
            ]);
    }
}
