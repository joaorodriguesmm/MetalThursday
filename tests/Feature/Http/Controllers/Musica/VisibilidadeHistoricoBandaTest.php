<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Musica;

use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\Edicao;
use App\Models\MetalThursday\MetalThursday;
use App\Models\MetalThursday\SeccaoMetalThursday;
use App\Models\MetalThursday\TipoSeccao;
use App\Models\Musica\Banda;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa a visibilidade temporal do histórico de uma banda.
 *
 * Apenas secções pertencentes a MetalThursdays já publicadas podem surgir no
 * histórico público da banda. Conteúdo preparado para datas futuras permanece
 * oculto até à respetiva publicação.
 *
 * @since 2.0.0
 */
final class VisibilidadeHistoricoBandaTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Prepara o teste com uma referência temporal determinística.
     *
     * @since 2.0.0
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $this->travelTo(
            CarbonImmutable::parse(
                '2026-08-27 12:00:00',
                'Europe/Lisbon',
            ),
        );
    }

    /**
     * Confirma que o histórico da banda apresenta passado e dia atual, mas
     * exclui secções pertencentes a uma MetalThursday preparada futura.
     *
     * @since 2.0.0
     */
    #[Test]
    public function historico_apresenta_apenas_metal_thursdays_publicadas(): void
    {
        $utilizador =
            Utilizador::factory()
                ->create();

        $this->actingAs(
            $utilizador,
            'sessao',
        );

        $banda =
            Banda::factory()
                ->create();

        $edicao =
            Edicao::factory()
                ->comPeriodo(
                    CarbonImmutable::parse(
                        '2026-08-01',
                    ),
                    CarbonImmutable::parse(
                        '2026-09-30',
                    ),
                )
                ->create();

        $publicadaPassada =
            $this->criarMetalThursday(
                $edicao,
                $utilizador,
                '2026-08-20',
            );

        $publicadaHoje =
            $this->criarMetalThursday(
                $edicao,
                $utilizador,
                '2026-08-27',
            );

        $preparadaFutura =
            $this->criarMetalThursday(
                $edicao,
                $utilizador,
                '2026-09-03',
            );

        $tipoSeccao =
            TipoSeccao::factory()
                ->comDetalhes()
                ->create();

        $seccaoPassada =
            $this->criarSeccao(
                $publicadaPassada,
                $tipoSeccao,
                $banda,
                'Secção histórica passada',
            );

        $seccaoHoje =
            $this->criarSeccao(
                $publicadaHoje,
                $tipoSeccao,
                $banda,
                'Secção histórica atual',
            );

        $seccaoFutura =
            $this->criarSeccao(
                $preparadaFutura,
                $tipoSeccao,
                $banda,
                'Secção futura privada',
            );

        $resposta =
            $this->get(
                route(
                    'bandas.detalhes',
                    $banda,
                ),
            );

        $resposta->assertOk();

        $paginador =
            $resposta->viewData(
                'seccoes',
            );

        self::assertInstanceOf(
            LengthAwarePaginator::class,
            $paginador,
        );

        $identificadores =
            $paginador
                ->getCollection()
                ->modelKeys();

        self::assertContains(
            (int) $seccaoPassada->getKey(),
            $identificadores,
        );

        self::assertContains(
            (int) $seccaoHoje->getKey(),
            $identificadores,
        );

        self::assertNotContains(
            (int) $seccaoFutura->getKey(),
            $identificadores,
        );
    }

    /**
     * Cria uma MetalThursday numa data determinada.
     *
     * @param  Edicao  $edicao  Edição associada.
     * @param  Utilizador  $autor  Autor associado.
     * @param  string  $data  Data pretendida.
     * @return MetalThursday MetalThursday criada.
     *
     * @since 2.0.0
     */
    private function criarMetalThursday(
        Edicao $edicao,
        Utilizador $autor,
        string $data,
    ): MetalThursday {
        return MetalThursday::factory()
            ->comData(
                CarbonImmutable::parse(
                    $data,
                ),
            )
            ->comEdicao(
                $edicao,
            )
            ->comAutor(
                $autor,
            )
            ->create();
    }

    /**
     * Cria uma secção associada à banda e à MetalThursday indicadas.
     *
     * @param  MetalThursday  $metalThursday  MetalThursday associada.
     * @param  TipoSeccao  $tipoSeccao  Tipo da secção.
     * @param  Banda  $banda  Banda associada.
     * @param  string  $titulo  Título identificável.
     * @return SeccaoMetalThursday Secção criada.
     *
     * @since 2.0.0
     */
    private function criarSeccao(
        MetalThursday $metalThursday,
        TipoSeccao $tipoSeccao,
        Banda $banda,
        string $titulo,
    ): SeccaoMetalThursday {
        return SeccaoMetalThursday::factory()
            ->paraMetalThursday(
                $metalThursday,
            )
            ->doTipo(
                $tipoSeccao,
            )
            ->comDetalhes(
                $banda,
            )
            ->comConteudo(
                'Descrição de teste.',
                $titulo,
            )
            ->create();
    }
}
