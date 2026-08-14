<?php

declare(strict_types=1);

namespace Tests\Feature\Factories\MetalThursday;

use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\Edicao;
use App\Models\MetalThursday\MetalThursday;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa os estados personalizados das factories do núcleo MetalThursday.
 *
 * Os testes garantem que os estados podem ser associados internamente pelo
 * Laravel e que os valores normalizados chegam aos modelos persistidos.
 *
 * @since 2.0.0
 */
final class FactoriesMetalThursdayTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Confirma os estados personalizados da factory das edições.
     *
     * @since 2.0.0
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
     * Confirma que o estado em curso não define uma data de fim.
     *
     * @since 2.0.0
     */
    #[Test]
    public function cria_edicao_em_curso(): void
    {
        CarbonImmutable::setTestNow(
            CarbonImmutable::create(
                2026,
                8,
                14,
                12,
            ),
        );

        try {
            $edicao = Edicao::factory()
                ->emCurso()
                ->create();

            self::assertSame(
                '2026-07-14',
                $edicao->data_inicio->toDateString(),
            );

            self::assertNull(
                $edicao->data_fim,
            );
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    /**
     * Confirma que a factory rejeita um período temporal incoerente.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_periodo_com_data_final_anterior_ao_inicio(): void
    {
        $dataInicio = CarbonImmutable::create(
            2026,
            6,
            1,
        );

        $dataFim = CarbonImmutable::create(
            2026,
            5,
            31,
        );

        $this->expectException(
            InvalidArgumentException::class,
        );

        Edicao::factory()
            ->comPeriodo(
                $dataInicio,
                $dataFim,
            );
    }

    /**
     * Confirma os estados personalizados da factory das MetalThursdays.
     *
     * @since 2.0.0
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

    /**
     * Confirma que uma edição não persistida não pode ser associada.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_edicao_nao_persistida(): void
    {
        $this->expectException(
            InvalidArgumentException::class,
        );

        MetalThursday::factory()
            ->comEdicao(
                new Edicao,
            );
    }
}
