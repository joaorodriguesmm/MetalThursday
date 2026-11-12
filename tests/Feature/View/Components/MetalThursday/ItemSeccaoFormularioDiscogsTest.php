<?php

declare(strict_types=1);

namespace Tests\Feature\View\Components\MetalThursday;

use App\Models\MetalThursday\TipoSeccao;
use App\Models\Musica\Artista;
use App\Models\Musica\Lancamento;
use App\View\Components\MetalThursday\ItemSeccaoFormulario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ItemSeccaoFormularioDiscogsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function rascunho_recupera_associacao_real_discogs(): void
    {
        $lancamento = Lancamento::factory()->create([
            'titulo' => 'Roots',
            'discogs_release_id' => 480908,
        ]);

        $componente = $this->criarComponente([
            'lancamento_id' => $lancamento->id,
        ]);

        $this->assertSame(
            480908,
            $componente->identificadorDiscogsAssociado,
        );
    }

    #[Test]
    public function rascunho_manual_nao_inventa_associacao_discogs(): void
    {
        $lancamento = Lancamento::factory()->create([
            'discogs_release_id' => null,
        ]);

        $componente = $this->criarComponente([
            'lancamento_id' => $lancamento->id,
        ]);

        $this->assertNull(
            $componente->identificadorDiscogsAssociado,
        );
    }

    #[Test]
    public function rascunho_sem_lancamento_nao_apresenta_discogs(): void
    {
        $componente = $this->criarComponente([]);

        $this->assertNull(
            $componente->identificadorDiscogsAssociado,
        );
    }

    private function criarComponente(array $seccao): ItemSeccaoFormulario
    {
        return new ItemSeccaoFormulario(
            pedido: request(),
            indice: 0,
            tiposSeccao: TipoSeccao::query()->get(),
            artistas: Artista::query()->get(),
            seccao: $seccao,
        );
    }
}
