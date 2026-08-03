<?php

declare(strict_types=1);

namespace Tests\Feature\Models\MetalThursday;

use App\Enumeracoes\TipoIncorporacao;
use App\Models\MetalThursday\MetalThursday;
use App\Models\MetalThursday\SeccaoMetalThursday;
use App\Models\MetalThursday\TipoSeccao;
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
 *
 * @version 1.0.0
 */
final class SeccaoMetalThursdayTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Confirma os limites aceites para a ordem e o ano.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
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
     *
     * @version 1.0.0
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
     *
     * @version 1.0.0
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
     *
     * @version 1.0.0
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
     *
     * @version 1.0.0
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
     *
     * @version 1.0.0
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
     *
     * @version 1.0.0
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
     * Cria os dados mínimos de uma secção para testes diretos da tabela.
     *
     * @return array<string, mixed> Dados mínimos válidos.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
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

            'banda_id' => null,

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
