<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\MetalThursday;

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
 * Testa a visibilidade temporal das MetalThursdays no arquivo.
 *
 * O arquivo completo e a vista simplificada apresentam exclusivamente
 * conteúdo cuja data de publicação já chegou. As MetalThursdays preparadas
 * para datas futuras permanecem fora destas consultas.
 *
 * @since 2.0.0
 */
final class VisibilidadeArquivoMetalThursdayTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Prepara cada teste sem depender dos ficheiros produzidos pelo Vite.
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
     * Confirma que a vista completa apresenta passado e dia atual, mas
     * exclui uma MetalThursday preparada para publicação futura.
     *
     * @since 2.0.0
     */
    #[Test]
    public function vista_completa_apresenta_apenas_metal_thursdays_publicadas(): void
    {
        $autor =
            $this->autenticarUtilizador();

        $edicao =
            $this->criarEdicao();

        $publicadaPassada =
            $this->criarMetalThursday(
                $edicao,
                $autor,
                '2026-08-20',
                'Arquivo Passado',
            );

        $publicadaHoje =
            $this->criarMetalThursday(
                $edicao,
                $autor,
                '2026-08-27',
                'Arquivo Atual',
            );

        $preparadaFutura =
            $this->criarMetalThursday(
                $edicao,
                $autor,
                '2026-09-03',
                'Arquivo Futuro',
            );

        $resposta =
            $this->get(
                route(
                    'inicio',
                ),
            );

        $resposta->assertOk();

        $paginador =
            $resposta->viewData(
                'registosMetalThursday',
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
            (int) $publicadaPassada->getKey(),
            $identificadores,
        );

        self::assertContains(
            (int) $publicadaHoje->getKey(),
            $identificadores,
        );

        self::assertNotContains(
            (int) $preparadaFutura->getKey(),
            $identificadores,
        );
    }

    /**
     * Confirma que a vista simplificada não apresenta secções pertencentes a
     * uma MetalThursday cuja publicação ainda não ocorreu.
     *
     * @since 2.0.0
     */
    #[Test]
    public function vista_simplificada_apresenta_apenas_seccoes_publicadas(): void
    {
        $autor =
            $this->autenticarUtilizador();

        $edicao =
            $this->criarEdicao();

        $publicadaPassada =
            $this->criarMetalThursday(
                $edicao,
                $autor,
                '2026-08-20',
                'Simplificada Passada',
            );

        $publicadaHoje =
            $this->criarMetalThursday(
                $edicao,
                $autor,
                '2026-08-27',
                'Simplificada Atual',
            );

        $preparadaFutura =
            $this->criarMetalThursday(
                $edicao,
                $autor,
                '2026-09-03',
                'Simplificada Futura',
            );

        $seccaoPassada =
            $this->criarSeccaoDetalhada(
                $publicadaPassada,
            );

        $seccaoHoje =
            $this->criarSeccaoDetalhada(
                $publicadaHoje,
            );

        $seccaoFutura =
            $this->criarSeccaoDetalhada(
                $preparadaFutura,
            );

        $resposta =
            $this->get(
                route(
                    'inicio',
                    [
                        'vista' => 'simplificada',
                    ],
                ),
            );

        $resposta->assertOk();

        $paginador =
            $resposta->viewData(
                'seccoesSimplificadas',
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
     * Autentica um utilizador válido para consultar o arquivo.
     *
     * @return Utilizador Utilizador autenticado.
     *
     * @since 2.0.0
     */
    private function autenticarUtilizador(): Utilizador
    {
        $utilizador =
            Utilizador::factory()
                ->create();

        $this->actingAs(
            $utilizador,
            'sessao',
        );

        return $utilizador;
    }

    /**
     * Cria uma edição que abrange as datas utilizadas nos testes.
     *
     * @return Edicao Edição criada.
     *
     * @since 2.0.0
     */
    private function criarEdicao(): Edicao
    {
        return Edicao::factory()
            ->comNome(
                'Edição de Visibilidade Temporal',
            )
            ->comPeriodo(
                CarbonImmutable::parse(
                    '2026-08-01',
                ),
                CarbonImmutable::parse(
                    '2026-09-30',
                ),
            )
            ->create();
    }

    /**
     * Cria uma MetalThursday identificável no arquivo.
     *
     * @param  Edicao  $edicao  Edição associada.
     * @param  Utilizador  $autor  Autor associado.
     * @param  string  $data  Data de publicação.
     * @param  string  $nome  Nome identificável.
     * @return MetalThursday MetalThursday criada.
     *
     * @since 2.0.0
     */
    private function criarMetalThursday(
        Edicao $edicao,
        Utilizador $autor,
        string $data,
        string $nome,
    ): MetalThursday {
        return MetalThursday::factory()
            ->comNome(
                $nome,
            )
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
     * Cria uma secção elegível para a vista simplificada.
     *
     * @param  MetalThursday  $metalThursday  MetalThursday associada.
     * @return SeccaoMetalThursday Secção criada.
     *
     * @since 2.0.0
     */
    private function criarSeccaoDetalhada(
        MetalThursday $metalThursday,
    ): SeccaoMetalThursday {
        $banda =
            Banda::factory()
                ->create();

        $tipoSeccao =
            TipoSeccao::factory()
                ->comDetalhes()
                ->create();

        return SeccaoMetalThursday::factory()
            ->paraMetalThursday(
                $metalThursday,
            )
            ->comDetalhes(
                $banda,
            )
            ->doTipo(
                $tipoSeccao,
            )
            ->create();
    }
}
