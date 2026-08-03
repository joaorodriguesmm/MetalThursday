<?php

declare(strict_types=1);

namespace Tests\Feature\Factories\MetalThursday;

use App\Enumeracoes\TipoIncorporacao;
use App\Models\MetalThursday\MetalThursday;
use App\Models\MetalThursday\SeccaoMetalThursday;
use App\Models\MetalThursday\TipoSeccao;
use App\Models\Musica\Banda;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa os estados personalizados das factories de tipos e secções.
 *
 * @since 2.0.0
 *
 * @version 1.1.0
 */
final class FactoriesSeccoesMetalThursdayTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Confirma os estados personalizados da factory dos tipos de secção.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function cria_tipo_seccao_com_estados_personalizados(): void
    {
        $tipoSeccao = TipoSeccao::factory()
            ->comDados(
                'faixa_destaque',
                'Faixa em destaque',
                'Apresenta uma faixa escolhida para a semana.',
            )
            ->comDetalhes()
            ->naOrdem(
                7,
            )
            ->create();

        self::assertSame(
            'faixa_destaque',
            $tipoSeccao->identificador,
        );

        self::assertSame(
            'Faixa em destaque',
            $tipoSeccao->nome,
        );

        self::assertSame(
            'Apresenta uma faixa escolhida para a semana.',
            $tipoSeccao->descricao,
        );

        self::assertTrue(
            $tipoSeccao->exige_detalhes,
        );

        self::assertSame(
            7,
            $tipoSeccao->ordem,
        );
    }

    /**
     * Confirma os estados personalizados da factory das secções.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function cria_seccao_com_conteudo_e_incorporacao(): void
    {
        $metalThursday = MetalThursday::factory()
            ->create();

        $tipoSeccao = TipoSeccao::factory()
            ->comDados(
                'video',
                'Vídeo',
                'Apresenta um vídeo relacionado com a seleção.',
            )
            ->comDetalhes()
            ->create();

        $banda = Banda::factory()
            ->create();

        $seccao = SeccaoMetalThursday::factory()
            ->paraMetalThursday(
                $metalThursday,
            )
            ->doTipo(
                $tipoSeccao,
            )
            ->comBanda(
                $banda,
            )
            ->naOrdem(
                3,
            )
            ->comConteudo(
                'Descrição conhecida da secção.',
                'Título conhecido',
            )
            ->comIncorporacao(
                'https://example.com/video',
                TipoIncorporacao::Ligacao,
            )
            ->create([
                'ano' => 2026,
            ]);

        self::assertSame(
            $metalThursday->getKey(),
            $seccao->metal_thursday_id,
        );

        self::assertSame(
            $tipoSeccao->getKey(),
            $seccao->tipo_seccao_id,
        );

        self::assertSame(
            $banda->getKey(),
            $seccao->banda_id,
        );

        self::assertSame(
            3,
            $seccao->ordem,
        );

        self::assertSame(
            'Título conhecido',
            $seccao->titulo,
        );

        self::assertSame(
            'Descrição conhecida da secção.',
            $seccao->descricao,
        );

        self::assertSame(
            'https://example.com/video',
            $seccao->ligacao,
        );

        self::assertSame(
            TipoIncorporacao::Ligacao,
            $seccao->tipo_incorporacao,
        );

        self::assertSame(
            2026,
            $seccao->ano,
        );
    }

    /**
     * Confirma que o estado detalhado cria todos os dados exigidos.
     *
     * O ano gerado deve respeitar o intervalo final e a ligação deve possuir
     * sempre um tipo de incorporação correspondente.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    #[Test]
    public function cria_seccao_detalhada_com_dados_validos(): void
    {
        $metalThursday = MetalThursday::factory()
            ->create();

        $banda = Banda::factory()
            ->create();

        $seccao = SeccaoMetalThursday::factory()
            ->paraMetalThursday(
                $metalThursday,
            )
            ->comDetalhes(
                $banda,
            )
            ->create();

        self::assertSame(
            $banda->getKey(),
            $seccao->banda_id,
        );

        self::assertNotNull(
            $seccao->titulo,
        );

        self::assertNotSame(
            '',
            trim(
                $seccao->descricao,
            ),
        );

        self::assertIsInt(
            $seccao->ano,
        );

        self::assertGreaterThanOrEqual(
            SeccaoMetalThursday::ANO_MINIMO,
            $seccao->ano,
        );

        self::assertLessThanOrEqual(
            SeccaoMetalThursday::ANO_MAXIMO,
            $seccao->ano,
        );

        self::assertIsString(
            $seccao->ligacao,
        );

        self::assertStringStartsWith(
            'https://example.com/musica/',
            $seccao->ligacao,
        );

        self::assertSame(
            TipoIncorporacao::Ligacao,
            $seccao->tipo_incorporacao,
        );
    }

    /**
     * Confirma que a factory rejeita ligações com credenciais incorporadas.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function rejeita_incorporacao_com_credenciais(): void
    {
        $this->expectException(
            InvalidArgumentException::class,
        );

        SeccaoMetalThursday::factory()
            ->comIncorporacao(
                'https://utilizador:segredo@example.com/video',
                TipoIncorporacao::Ligacao,
            );
    }
}
