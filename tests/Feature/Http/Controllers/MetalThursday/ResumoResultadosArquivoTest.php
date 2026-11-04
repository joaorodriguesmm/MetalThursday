<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\MetalThursday;

use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\Edicao;
use App\Models\MetalThursday\MetalThursday;
use App\Models\MetalThursday\SeccaoMetalThursday;
use App\Models\MetalThursday\TipoSeccao;
use App\Models\Musica\Artista;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ResumoResultadosArquivoTest extends TestCase
{
    use RefreshDatabase;

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
     * @return array<string, array{string, bool}>
     */
    public static function casosContagem(): array
    {
        return [
            'completa sem resultados' => ['completa', false],
            'completa com um resultado' => ['completa', true],
            'simplificada sem resultados' => ['simplificada', false],
            'simplificada com um resultado' => ['simplificada', true],
        ];
    }

    #[Test]
    #[DataProvider('casosContagem')]
    public function apresenta_resumo_sem_depender_da_paginacao(
        string $vista,
        bool $comResultado,
    ): void {
        $utilizador = Utilizador::factory()->create();

        $this->actingAs($utilizador, 'sessao');

        if ($comResultado) {
            $this->criarRegistoComSeccao($utilizador);
        }

        $total = $comResultado ? 1 : 0;

        $resposta = $this->get(
            route('inicio', ['vista' => $vista]),
        );

        $resposta
            ->assertOk()
            ->assertSeeHtml('id="resumo-resultados-arquivo"')
            ->assertSeeHtml('data-total-resultados="'.$total.'"')
            ->assertDontSeeHtml('aria-label="Paginação dos resultados"');

        if ($comResultado) {
            $resposta
                ->assertSeeText('A mostrar')
                ->assertDontSeeText('Nenhum resultado nesta página.');
        } else {
            $resposta
                ->assertSeeText('Nenhum resultado nesta página.')
                ->assertDontSeeText('A mostrar');
        }
    }

    /**
     * @return array<string, array{string, int, int, int}>
     */
    public static function casosPaginados(): array
    {
        return [
            'completa pagina 1' => ['completa', 1, 1, 5],
            'completa pagina 2' => ['completa', 2, 6, 6],
            'simplificada pagina 1' => ['simplificada', 1, 1, 5],
            'simplificada pagina 2' => ['simplificada', 2, 6, 6],
        ];
    }

    #[Test]
    #[DataProvider('casosPaginados')]
    public function apresenta_resumo_unico_com_paginacao(
        string $vista,
        int $pagina,
        int $primeiro,
        int $ultimo,
    ): void {
        $utilizador = Utilizador::factory()->create();
        $this->actingAs($utilizador, 'sessao');

        $edicao = Edicao::factory()
            ->comNome('Edição de paginação')
            ->comPeriodo(
                CarbonImmutable::parse('2026-07-01'),
                CarbonImmutable::parse('2026-08-31'),
            )
            ->create();

        $artista = Artista::factory()->create();
        $tipo = TipoSeccao::factory()->comDetalhes()->create();

        $datas = [
            '2026-07-16',
            '2026-07-23',
            '2026-07-30',
            '2026-08-06',
            '2026-08-13',
            '2026-08-20',
        ];

        foreach ($datas as $indice => $data) {
            $registo = MetalThursday::factory()
                ->comNome('Registo paginado '.($indice + 1))
                ->comData(CarbonImmutable::parse($data))
                ->comEdicao($edicao)
                ->comAutor($utilizador)
                ->create();

            SeccaoMetalThursday::factory()
                ->paraMetalThursday($registo)
                ->comDetalhes($artista)
                ->doTipo($tipo)
                ->create();
        }

        $resposta = $this->get(route('inicio', [
            'vista' => $vista,
            'por_pagina' => 5,
            'page' => $pagina,
        ]));

        $resposta
            ->assertOk()
            ->assertSeeHtml('data-total-resultados="6"')
            ->assertSeeHtml('aria-label="Paginação dos resultados"');

        $html = (string) $resposta->getContent();

        self::assertSame(1, substr_count($html, 'A mostrar'));

        self::assertSame(
            1,
            preg_match(
                '~<p\b[^>]*id="resumo-resultados-arquivo"[^>]*>(.*?)</p>~su',
                $html,
                $correspondencias,
            ),
        );

        $resumo = trim((string) preg_replace(
            '/\s+/u',
            ' ',
            strip_tags($correspondencias[1]),
        ));

        self::assertSame(
            sprintf(
                'A mostrar %d a %d de 6 resultados',
                $primeiro,
                $ultimo,
            ),
            $resumo,
        );
    }

    private function criarRegistoComSeccao(Utilizador $autor): void
    {
        $edicao = Edicao::factory()
            ->comNome('Edição de teste da contagem')
            ->comPeriodo(
                CarbonImmutable::parse('2026-08-01'),
                CarbonImmutable::parse('2026-08-31'),
            )
            ->create();

        $metalThursday = MetalThursday::factory()
            ->comNome('MetalThursday da contagem')
            ->comData(CarbonImmutable::parse('2026-08-20'))
            ->comEdicao($edicao)
            ->comAutor($autor)
            ->create();

        $artista = Artista::factory()->create();

        $tipoSeccao = TipoSeccao::factory()
            ->comDetalhes()
            ->create();

        SeccaoMetalThursday::factory()
            ->paraMetalThursday($metalThursday)
            ->comDetalhes($artista)
            ->doTipo($tipoSeccao)
            ->create();
    }
}
