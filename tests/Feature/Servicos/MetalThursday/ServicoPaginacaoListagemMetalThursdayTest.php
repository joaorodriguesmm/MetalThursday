<?php

declare(strict_types=1);

namespace Tests\Feature\Servicos\MetalThursday;

use App\Models\Musica\Artista;
use App\Servicos\MetalThursday\ServicoPaginacaoListagemMetalThursday;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa a paginação otimizada da listagem MetalThursday.
 *
 * @since 2.0.0
 */
final class ServicoPaginacaoListagemMetalThursdayTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function pagina_valida_obtem_total_sem_consulta_de_contagem_separada(): void
    {
        [
            $primeiro,
            $segundo,
        ] = $this->criarArtistas();

        $pedido = Request::create(
            '/arquivo',
            'GET',
            [
                'pesquisa' => 'doom',
                'page' => 1,
            ],
        );

        app()->instance(
            'request',
            $pedido,
        );

        DB::flushQueryLog();
        DB::enableQueryLog();

        $paginador =
            app(
                ServicoPaginacaoListagemMetalThursday::class,
            )->paginar(
                Artista::query()
                    ->orderBy('id'),
                2,
                $pedido,
            );

        $consultas =
            DB::getQueryLog();

        DB::disableQueryLog();

        self::assertSame(
            3,
            $paginador->total(),
        );

        self::assertSame(
            1,
            $paginador->currentPage(),
        );

        self::assertSame(
            2,
            $paginador->lastPage(),
        );

        self::assertSame(
            [
                $primeiro->id,
                $segundo->id,
            ],
            $paginador
                ->getCollection()
                ->pluck('id')
                ->all(),
        );

        self::assertSame(
            'http://localhost/arquivo?pesquisa=doom&page=2',
            $paginador->url(2),
        );

        foreach (
            $paginador->getCollection() as $artista
        ) {
            self::assertArrayNotHasKey(
                'total_resultados_paginacao',
                $artista->getAttributes(),
            );
        }

        self::assertCount(
            1,
            $consultas,
        );

        self::assertStringContainsString(
            'count(*) over()',
            mb_strtolower(
                $consultas[0]['query'],
            ),
        );
    }

    #[Test]
    public function pagina_vazia_recupera_total_com_contagem_convencional(): void
    {
        $this->criarArtistas();

        $pedido = Request::create(
            '/arquivo',
            'GET',
            [
                'pesquisa' => 'doom',
                'page' => 3,
            ],
        );

        app()->instance(
            'request',
            $pedido,
        );

        DB::flushQueryLog();
        DB::enableQueryLog();

        $paginador =
            app(
                ServicoPaginacaoListagemMetalThursday::class,
            )->paginar(
                Artista::query()
                    ->orderBy('id'),
                2,
                $pedido,
            );

        $consultas =
            DB::getQueryLog();

        DB::disableQueryLog();

        self::assertSame(
            3,
            $paginador->total(),
        );

        self::assertSame(
            3,
            $paginador->currentPage(),
        );

        self::assertSame(
            2,
            $paginador->lastPage(),
        );

        self::assertCount(
            0,
            $paginador->items(),
        );

        self::assertNull(
            $paginador->firstItem(),
        );

        self::assertNull(
            $paginador->lastItem(),
        );

        self::assertCount(
            2,
            $consultas,
        );

        self::assertStringContainsString(
            'count(*) over()',
            mb_strtolower(
                $consultas[0]['query'],
            ),
        );

        self::assertStringContainsString(
            'count(*) as',
            mb_strtolower(
                $consultas[1]['query'],
            ),
        );
    }

    /**
     * Cria três artistas ordenáveis para os testes de paginação.
     *
     * @return array{Artista, Artista, Artista} Artistas criados.
     */
    private function criarArtistas(): array
    {
        return [
            Artista::factory()
                ->comNome('Artista A')
                ->create(),
            Artista::factory()
                ->comNome('Artista B')
                ->create(),
            Artista::factory()
                ->comNome('Artista C')
                ->create(),
        ];
    }
}
