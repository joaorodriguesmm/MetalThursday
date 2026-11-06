<?php

declare(strict_types=1);

namespace Tests\Feature\Servicos\MetalThursday;

use App\Enumeracoes\TipoLancamento;
use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\Edicao;
use App\Models\MetalThursday\MetalThursday;
use App\Models\MetalThursday\SeccaoMetalThursday;
use App\Models\MetalThursday\TipoSeccao;
use App\Models\Musica\Artista;
use App\Models\Musica\Lancamento;
use App\Servicos\MetalThursday\ServicoPersistenciaMetalThursday;
use App\View\Components\MetalThursday\ItemSeccaoFormulario;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Protege a edição de secções antigas sem lançamentos no catálogo.
 */
final class CompatibilidadeLancamentosLegadosTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function formulario_recupera_titulo_e_ano_da_seccao_antiga(): void
    {
        $seccao = new SeccaoMetalThursday;
        $seccao->forceFill(['id' => 1, 'tipo_seccao_id' => 1, 'artista_id' => 1, 'descricao' => 'Descrição histórica.']);
        $seccao->exists = true;
        $seccao->lancamento_id = null;
        $seccao->titulo = 'Álbum histórico';
        $seccao->ano = 1987;

        $componente = new ItemSeccaoFormulario(
            Request::create('/'),
            0,
            new Collection,
            new Collection,
            $seccao,
        );

        self::assertSame(
            'Álbum histórico',
            $componente->dadosLancamento['titulo'],
        );
        self::assertSame(
            '1987',
            $componente->dadosLancamento['ano_original'],
        );
        self::assertSame([], $componente->dadosLancamento['faixas']);
    }

    #[Test]
    public function editar_seccao_antiga_preserva_catalogo_ate_existir_conversao_explicita(): void
    {
        $autor = Utilizador::factory()->create();
        $artista = Artista::factory()->create();
        $edicao = Edicao::factory()->comPeriodo(
            CarbonImmutable::parse('2026-01-01'),
            CarbonImmutable::parse('2026-01-31'),
        )->create();
        $tipo = TipoSeccao::factory()
            ->comDados('lancamento', 'Lançamento', 'Lançamento musical.')
            ->comDetalhes()
            ->create();

        $metalThursday = MetalThursday::factory()->create([
            'edicao_id' => $edicao->getKey(),
            'data' => CarbonImmutable::parse('2026-01-08'),
            'autor_id' => $autor->getKey(),
            'proximo_nomeado_id' => null,
        ]);

        $seccao = SeccaoMetalThursday::factory()->create([
            'metal_thursday_id' => $metalThursday->getKey(),
            'tipo_seccao_id' => $tipo->getKey(),
            'artista_id' => $artista->getKey(),
            'lancamento_id' => null,
            'ordem' => 1,
            'titulo' => 'Álbum histórico',
            'ano' => 1987,
            'descricao' => 'Descrição original.',
        ]);

        $dados = [
            'edicao_id' => (int) $edicao->getKey(),
            'data' => '2026-01-08',
            'nome' => null,
            'autor_id' => (int) $autor->getKey(),
            'proximo_nomeado_id' => null,
            'seccoes' => [
                [
                    'id' => (int) $seccao->getKey(),
                    'tipo_seccao_id' => (int) $tipo->getKey(),
                    'artista_id' => (int) $artista->getKey(),
                    'lancamento_id' => null,
                    'titulo' => 'Álbum histórico corrigido',
                    'ano' => 1988,
                    'descricao' => 'Descrição corrigida.',
                    'ligacoes' => [],
                    'lancamento' => [
                        'titulo' => 'Álbum histórico corrigido',
                        'tipo' => null,
                        'ano_original' => 1988,
                        'faixas' => [],
                    ],
                ],
            ],
        ];

        $quantidadeInicial = Lancamento::query()->count();
        $servico = app(ServicoPersistenciaMetalThursday::class);

        $servico->atualizar($metalThursday, $dados);

        $seccao->refresh();
        self::assertNull($seccao->lancamento_id);
        self::assertSame('Álbum histórico corrigido', $seccao->titulo);
        self::assertSame(1988, $seccao->ano);
        self::assertSame($quantidadeInicial, Lancamento::query()->count());

        // Selecionar um tipo estruturado converte apenas esta secção.
        $dados['seccoes'][0]['lancamento']['tipo'] =
            TipoLancamento::AlbumEstudio->value;

        $servico->atualizar($metalThursday, $dados);

        $seccao->refresh();
        self::assertNotNull($seccao->lancamento_id);
        self::assertSame('Álbum histórico corrigido', $seccao->lancamento->titulo);
        self::assertSame($quantidadeInicial + 1, Lancamento::query()->count());
    }
}
