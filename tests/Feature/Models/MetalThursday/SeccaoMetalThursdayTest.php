<?php

declare(strict_types=1);

namespace Tests\Feature\Models\MetalThursday;

use App\Enumeracoes\TipoIncorporacao;
use App\Models\MetalThursday\MetalThursday;
use App\Models\MetalThursday\SeccaoMetalThursday;
use App\Models\MetalThursday\TipoSeccao;
use App\Models\Musica\Artista;
use App\Models\Musica\Lancamento;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa os contratos do modelo e da tabela das secções.
 *
 * @since 2.0.0
 */
final class SeccaoMetalThursdayTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Confirma os limites aceites para a ordem e o ano.
     *
     * @since 2.0.0
     */
    #[Test]
    public function aceita_limites_de_ordem_e_ano(): void
    {
        $seccao = new SeccaoMetalThursday;

        $seccao->ordem =
            SeccaoMetalThursday::ORDEM_MINIMA;

        $seccao->ano =
            SeccaoMetalThursday::ANO_MINIMO;

        self::assertSame(
            SeccaoMetalThursday::ORDEM_MINIMA,
            $seccao->ordem,
        );

        self::assertSame(
            SeccaoMetalThursday::ANO_MINIMO,
            $seccao->ano,
        );

        $seccao->ordem =
            SeccaoMetalThursday::ORDEM_MAXIMA;

        $seccao->ano =
            SeccaoMetalThursday::ANO_MAXIMO;

        self::assertSame(
            SeccaoMetalThursday::ORDEM_MAXIMA,
            $seccao->ordem,
        );

        self::assertSame(
            SeccaoMetalThursday::ANO_MAXIMO,
            $seccao->ano,
        );
    }

    /**
     * Confirma que uma ordem acima da capacidade da coluna é rejeitada.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_ordem_acima_do_limite(): void
    {
        $this->expectException(
            InvalidArgumentException::class,
        );

        $seccao = new SeccaoMetalThursday;

        $seccao->ordem =
            SeccaoMetalThursday::ORDEM_MAXIMA + 1;
    }

    /**
     * Confirma que uma ligação com barra invertida é rejeitada.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_ligacao_com_barra_invertida(): void
    {
        $this->expectException(
            InvalidArgumentException::class,
        );

        $seccao = new SeccaoMetalThursday;

        $seccao->ligacao =
            'https://example.com\\video';
    }

    /**
     * Confirma que uma ligação sem tipo de incorporação não é persistida.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_ligacao_sem_tipo_de_incorporacao(): void
    {
        $this->expectException(
            InvalidArgumentException::class,
        );

        $seccao = new SeccaoMetalThursday;

        $seccao->ligacao =
            'https://example.com/video';

        $seccao->saveOrFail();
    }

    /**
     * Confirma que um tipo de incorporação sem ligação não é persistido.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_tipo_de_incorporacao_sem_ligacao(): void
    {
        $this->expectException(
            InvalidArgumentException::class,
        );

        $seccao = new SeccaoMetalThursday;

        $seccao->tipo_incorporacao =
            TipoIncorporacao::Ligacao;

        $seccao->saveOrFail();
    }

    /**
     * Confirma que a base de dados rejeita um ano superior ao contrato.
     *
     * @since 2.0.0
     */
    #[Test]
    public function base_de_dados_rejeita_ano_acima_do_limite(): void
    {
        $dadosBase =
            $this->criarDadosBase();

        $this->expectException(
            QueryException::class,
        );

        DB::table(
            'seccoes_metal_thursday',
        )->insert([
            ...$dadosBase,

            'ano' => SeccaoMetalThursday::ANO_MAXIMO + 1,
        ]);
    }

    /**
     * Confirma que a base de dados exige ligação e tipo em conjunto.
     *
     * @since 2.0.0
     */
    #[Test]
    public function base_de_dados_rejeita_incorporacao_incompleta(): void
    {
        $dadosBase =
            $this->criarDadosBase();

        $this->expectException(
            QueryException::class,
        );

        DB::table(
            'seccoes_metal_thursday',
        )->insert([
            ...$dadosBase,

            'ligacao' => 'https://example.com/video',

            'tipo_incorporacao' => null,
        ]);
    }

    /**
     * Confirma que uma secção preserva o artista eliminado logicamente.
     *
     * @since 2.0.0
     */
    #[Test]
    public function preserva_artista_eliminado_logicamente_na_relacao(): void
    {
        $artista = Artista::factory()
            ->create();

        $seccao = SeccaoMetalThursday::factory()
            ->comDetalhes(
                $artista,
            )
            ->create();

        $identificadorArtista = (int) $artista->getKey();

        $artista->deleteOrFail();

        $seccao->refresh();

        self::assertSame(
            $identificadorArtista,
            $seccao->artista_id,
        );

        $artistaHistorico = $seccao->artista;

        self::assertInstanceOf(
            Artista::class,
            $artistaHistorico,
        );

        self::assertSame(
            $identificadorArtista,
            (int) $artistaHistorico->getKey(),
        );

        self::assertTrue(
            $artistaHistorico->trashed(),
        );
    }

    /**
     * Confirma que uma secção pode ficar associada a um lançamento.
     *
     * @since 2.0.0
     */
    #[Test]
    public function associa_lancamento_a_seccao(): void
    {
        $lancamento =
            Lancamento::factory()
                ->create();

        $seccao =
            SeccaoMetalThursday::factory()
                ->create();

        $seccao
            ->lancamento()
            ->associate(
                $lancamento,
            );

        $seccao->saveOrFail();
        $seccao->refresh();

        self::assertSame(
            $lancamento->getKey(),
            $seccao->lancamento_id,
        );

        self::assertInstanceOf(
            Lancamento::class,
            $seccao->lancamento,
        );

        self::assertSame(
            $lancamento->getKey(),
            $seccao->lancamento->getKey(),
        );
    }

    /**
     * Confirma que uma secção preserva o lançamento eliminado logicamente.
     *
     * @since 2.0.0
     */
    #[Test]
    public function preserva_lancamento_eliminado_logicamente_na_relacao(): void
    {
        $lancamento =
            Lancamento::factory()
                ->create();

        $seccao =
            SeccaoMetalThursday::factory()
                ->create();

        $seccao
            ->lancamento()
            ->associate(
                $lancamento,
            );

        $seccao->saveOrFail();

        $identificadorLancamento =
            (int) $lancamento->getKey();

        $lancamento->deleteOrFail();

        $seccao->refresh();

        self::assertSame(
            $identificadorLancamento,
            $seccao->lancamento_id,
        );

        $lancamentoHistorico =
            $seccao->lancamento;

        self::assertInstanceOf(
            Lancamento::class,
            $lancamentoHistorico,
        );

        self::assertSame(
            $identificadorLancamento,
            (int) $lancamentoHistorico->getKey(),
        );

        self::assertTrue(
            $lancamentoHistorico->trashed(),
        );
    }

    /**
     * Cria os dados mínimos de uma secção para testes diretos da tabela.
     *
     * @return array<string, mixed> Dados mínimos válidos.
     *
     * @since 2.0.0
     */
    private function criarDadosBase(): array
    {
        $metalThursday = MetalThursday::factory()
            ->create();

        $tipoSeccao = TipoSeccao::factory()
            ->semDetalhes()
            ->create();

        return [
            'metal_thursday_id' => $metalThursday->getKey(),

            'tipo_seccao_id' => $tipoSeccao->getKey(),

            'ordem' => SeccaoMetalThursday::ORDEM_MINIMA,

            'titulo' => null,

            'descricao' => 'Descrição válida.',

            'artista_id' => null,

            'ligacao' => null,

            'tipo_incorporacao' => null,

            'ano' => null,

            'criado_por_id' => null,

            'atualizado_por_id' => null,

            'created_at' => now(),

            'updated_at' => now(),

            'deleted_at' => null,
        ];
    }
}
