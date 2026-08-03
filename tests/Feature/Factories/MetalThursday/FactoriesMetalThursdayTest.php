<?php

declare(strict_types=1);

namespace Tests\Feature\Factories\MetalThursday;

use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\Edicao;
use App\Models\MetalThursday\MetalThursday;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa os estados personalizados das factories do núcleo MetalThursday.
 *
 * Os testes garantem que os estados podem ser associados internamente pelo
 * Laravel e que os valores normalizados chegam aos modelos persistidos.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
final class FactoriesMetalThursdayTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Confirma os estados personalizados da factory das edições.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function cria_edicao_com_estados_personalizados(): void
    {
        $dataInicio = CarbonImmutable::create(
            2026,
            1,
            1,
        )->startOfDay();

        $dataFim = CarbonImmutable::create(
            2026,
            6,
            30,
        )->startOfDay();

        $edicao = Edicao::factory()
            ->comNome(
                '  Edição Especial  ',
            )
            ->comPeriodo(
                $dataInicio,
                $dataFim,
            )
            ->comLigacaoCompilacao(
                'https://example.com/compilacao',
            )
            ->create();

        self::assertSame(
            'Edição Especial',
            $edicao->nome,
        );

        self::assertSame(
            '2026-01-01',
            $edicao->data_inicio->toDateString(),
        );

        self::assertSame(
            '2026-06-30',
            $edicao->data_fim?->toDateString(),
        );

        self::assertSame(
            'https://example.com/compilacao',
            $edicao->ligacao_compilacao,
        );
    }

    /**
     * Confirma os estados personalizados da factory das MetalThursdays.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function cria_metal_thursday_com_estados_personalizados(): void
    {
        $data = CarbonImmutable::create(
            2026,
            2,
            5,
        )->startOfDay();

        $edicao = Edicao::factory()
            ->comPeriodo(
                $data->startOfMonth(),
                $data->endOfMonth(),
            )
            ->create();

        $autor = Utilizador::factory()
            ->create();

        $proximoNomeado = Utilizador::factory()
            ->create();

        $metalThursday = MetalThursday::factory()
            ->comNome(
                '  Especial de Inverno  ',
            )
            ->comData(
                $data,
            )
            ->comEdicao(
                $edicao,
            )
            ->comAutor(
                $autor,
            )
            ->comProximoNomeado(
                $proximoNomeado,
            )
            ->create();

        self::assertSame(
            'Especial de Inverno',
            $metalThursday->nome,
        );

        self::assertSame(
            '2026-02-05',
            $metalThursday->data->toDateString(),
        );

        self::assertSame(
            $edicao->getKey(),
            $metalThursday->edicao_id,
        );

        self::assertSame(
            $autor->getKey(),
            $metalThursday->autor_id,
        );

        self::assertSame(
            $proximoNomeado->getKey(),
            $metalThursday->proximo_nomeado_id,
        );
    }
}
