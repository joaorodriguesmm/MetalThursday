<?php

declare(strict_types=1);

namespace Tests\Feature\View\Components\MetalThursday;

use App\Enumeracoes\TipoLancamento;
use App\Models\MetalThursday\SeccaoMetalThursday;
use App\Models\Musica\FaixaLancamento;
use App\Models\Musica\Lancamento;
use App\Models\Musica\Musica;
use App\View\Components\MetalThursday\ItemSeccaoFormulario;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa a preparação do editor estruturado de lançamentos.
 *
 * @since 2.0.0
 */
final class ItemSeccaoFormularioLancamentoTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Confirma que uma secção existente carrega metadados e tracklist.
     *
     * @since 2.0.0
     */
    #[Test]
    public function carrega_lancamento_existente_com_tracklist(): void
    {
        $lancamento = Lancamento::factory()
            ->create([
                'titulo' => 'Master of Puppets',
                'tipo' => TipoLancamento::AlbumEstudio,
                'ano_original' => 1986,
            ]);

        $musica = Musica::factory()
            ->create([
                'titulo' => 'Battery',
            ]);

        $faixa = FaixaLancamento::factory()
            ->create([
                'lancamento_id' => $lancamento->getKey(),
                'musica_id' => $musica->getKey(),
                'posicao' => 'A1',
                'ordem' => 1,
            ]);

        $seccao = new SeccaoMetalThursday;
        $seccao->lancamento_id = $lancamento->getKey();
        $seccao->setRelation(
            'lancamento',
            $lancamento,
        );

        $componente = new ItemSeccaoFormulario(
            Request::create('/'),
            0,
            new Collection,
            new Collection,
            $seccao,
        );

        self::assertSame(
            'Master of Puppets',
            $componente->dadosLancamento['titulo'],
        );

        self::assertSame(
            TipoLancamento::AlbumEstudio->value,
            $componente->dadosLancamento['tipo'],
        );

        self::assertSame(
            '1986',
            $componente->dadosLancamento['ano_original'],
        );

        self::assertCount(
            1,
            $componente->dadosLancamento['faixas'],
        );

        self::assertSame(
            (string) $faixa->getKey(),
            $componente->dadosLancamento['faixas'][0]['id'],
        );

        self::assertSame(
            (string) $musica->getKey(),
            $componente->dadosLancamento['faixas'][0]['musica_id'],
        );

        self::assertSame(
            'Battery',
            $componente->dadosLancamento['faixas'][0]['titulo'],
        );

        self::assertSame(
            'A1',
            $componente->dadosLancamento['faixas'][0]['posicao'],
        );
    }

    /**
     * Confirma que os dados estruturados de um rascunho são preservados.
     *
     * @since 2.0.0
     */
    #[Test]
    public function carrega_dados_estruturados_de_rascunho(): void
    {
        $componente = new ItemSeccaoFormulario(
            Request::create('/'),
            0,
            new Collection,
            new Collection,
            [
                'lancamento' => [
                    'titulo' => 'Título corrigido',
                    'tipo' => TipoLancamento::EP->value,
                    'ano_original' => '',
                    'faixas' => [
                        [
                            'id' => '',
                            'musica_id' => '',
                            'titulo' => 'Faixa corrigida',
                            'posicao' => '1',
                            'ordem' => '1',
                        ],
                    ],
                ],
            ],
        );

        self::assertSame(
            'Título corrigido',
            $componente->dadosLancamento['titulo'],
        );

        self::assertSame(
            TipoLancamento::EP->value,
            $componente->dadosLancamento['tipo'],
        );

        self::assertSame(
            '',
            $componente->dadosLancamento['ano_original'],
        );

        self::assertSame(
            'Faixa corrigida',
            $componente->dadosLancamento['faixas'][0]['titulo'],
        );
    }
}
