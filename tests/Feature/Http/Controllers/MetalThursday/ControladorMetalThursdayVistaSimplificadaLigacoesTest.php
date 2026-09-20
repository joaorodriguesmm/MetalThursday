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
 * Testa as ligações da vista simplificada de MetalThursdays.
 *
 * @since 2.0.0
 */
final class ControladorMetalThursdayVistaSimplificadaLigacoesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Confirma que a tabela usa a nova coleção e preserva a ordem.
     *
     * @since 2.0.0
     */
    #[Test]
    public function vista_simplificada_apresenta_novas_ligacoes_pela_ordem(): void
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
                'inicio',
                [
                    'vista' => 'simplificada',
                ],
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
