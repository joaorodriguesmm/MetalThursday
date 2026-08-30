<?php

declare(strict_types=1);

namespace Tests\Feature\Models\MetalThursday;

use App\Models\MetalThursday\Edicao;
use App\Models\MetalThursday\MetalThursday;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa os atributos calculados do modelo MetalThursday.
 *
 * Os testes garantem que o número sequencial dentro da edição é calculado
 * pela base de dados sem introduzir uma consulta por registo apresentado.
 *
 * @since 2.0.0
 */
final class MetalThursdayTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Confirma que a posição na edição é carregada na consulta principal.
     *
     * MetalThursdays eliminadas logicamente e registos de outras edições não
     * devem alterar a posição dos registos ativos apresentados.
     *
     * @since 2.0.0
     */
    #[Test]
    public function carrega_numeros_semana_numa_unica_consulta(): void
    {
        $edicao = $this->criarEdicao(
            'Edição Principal',
            '2026-01-01',
            '2026-01-31',
        );

        $outraEdicao = $this->criarEdicao(
            'Outra Edição',
            '2026-01-01',
            '2026-01-31',
        );

        $primeira = $this->criarMetalThursday(
            $edicao,
            '2026-01-01',
        );

        $eliminada = $this->criarMetalThursday(
            $edicao,
            '2026-01-08',
        );

        $terceira = $this->criarMetalThursday(
            $edicao,
            '2026-01-15',
        );

        $this->criarMetalThursday(
            $outraEdicao,
            '2026-01-03',
        );

        $eliminada->deleteOrFail();

        DB::flushQueryLog();
        DB::enableQueryLog();

        try {
            $registos = MetalThursday::query()
                ->comNumeroSemanaNaEdicao()
                ->where(
                    'edicao_id',
                    $edicao->getKey(),
                )
                ->orderBy(
                    'data',
                )
                ->get();

            $numerosSemana = $registos
                ->pluck(
                    MetalThursday::COLUNA_NUMERO_SEMANA_NA_EDICAO,
                )
                ->all();

            $consultas = DB::getQueryLog();
        } finally {
            DB::disableQueryLog();
        }

        self::assertSame(
            [
                (int) $primeira->getKey(),
                (int) $terceira->getKey(),
            ],
            $registos
                ->modelKeys(),
        );

        self::assertSame(
            [
                1,
                2,
            ],
            $numerosSemana,
        );

        self::assertCount(
            1,
            $consultas,
        );
    }

    /**
     * Confirma que o carregamento explícito executa apenas uma consulta.
     *
     * Leituras repetidas do atributo devem reutilizar o valor carregado e o
     * alias não pode ficar marcado para persistência.
     *
     * @since 2.0.0
     */
    #[Test]
    public function carrega_numero_semana_explicitamente_sem_consultas_repetidas(): void
    {
        $edicao = $this->criarEdicao(
            'Edição de Teste',
            '2026-02-01',
            '2026-02-28',
        );

        $this->criarMetalThursday(
            $edicao,
            '2026-02-05',
        );

        $segunda = $this->criarMetalThursday(
            $edicao,
            '2026-02-12',
        );

        $metalThursday = MetalThursday::query()
            ->findOrFail(
                $segunda->getKey(),
            );

        DB::flushQueryLog();
        DB::enableQueryLog();

        try {
            $metalThursday->carregarNumeroSemanaNaEdicao();

            $primeiraLeitura = $metalThursday->getAttribute(
                MetalThursday::COLUNA_NUMERO_SEMANA_NA_EDICAO,
            );

            $segundaLeitura = $metalThursday->getAttribute(
                MetalThursday::COLUNA_NUMERO_SEMANA_NA_EDICAO,
            );

            $consultas = DB::getQueryLog();
        } finally {
            DB::disableQueryLog();
        }

        self::assertSame(
            2,
            $primeiraLeitura,
        );

        self::assertSame(
            2,
            $segundaLeitura,
        );

        self::assertCount(
            1,
            $consultas,
        );

        self::assertFalse(
            $metalThursday->isDirty(
                MetalThursday::COLUNA_NUMERO_SEMANA_NA_EDICAO,
            ),
        );
    }

    /**
     * Confirma a fronteira temporal entre preparada e publicada.
     *
     * @since 2.0.0
     */
    #[Test]
    public function distingue_estado_temporal_pela_data(): void
    {
        $edicao = $this->criarEdicao(
            'Edição Temporal',
            '2026-08-01',
            '2026-09-30',
        );

        $passada = $this->criarMetalThursday(
            $edicao,
            '2026-08-27',
        );

        $atual = $this->criarMetalThursday(
            $edicao,
            '2026-08-28',
        );

        $futura = $this->criarMetalThursday(
            $edicao,
            '2026-09-03',
        );

        $referencia =
            CarbonImmutable::parse(
                '2026-08-28 12:00:00',
                'Europe/Lisbon',
            );

        self::assertTrue(
            $passada->estaPublicada(
                $referencia,
            ),
        );

        self::assertTrue(
            $atual->estaPublicada(
                $referencia,
            ),
        );

        self::assertFalse(
            $futura->estaPublicada(
                $referencia,
            ),
        );

        self::assertFalse(
            $passada->estaPreparada(
                $referencia,
            ),
        );

        self::assertFalse(
            $atual->estaPreparada(
                $referencia,
            ),
        );

        self::assertTrue(
            $futura->estaPreparada(
                $referencia,
            ),
        );
    }

    /**
     * Confirma que os scopes temporais separam registos publicados e
     * preparados sem depender de uma coluna de estado.
     *
     * @since 2.0.0
     */
    #[Test]
    public function scopes_separam_publicadas_e_preparadas(): void
    {
        $edicao = $this->criarEdicao(
            'Edição dos Scopes',
            '2026-08-01',
            '2026-09-30',
        );

        $passada = $this->criarMetalThursday(
            $edicao,
            '2026-08-27',
        );

        $atual = $this->criarMetalThursday(
            $edicao,
            '2026-08-28',
        );

        $futura = $this->criarMetalThursday(
            $edicao,
            '2026-09-03',
        );

        $referencia =
            CarbonImmutable::parse(
                '2026-08-28 12:00:00',
                'Europe/Lisbon',
            );

        self::assertSame(
            [
                (int) $passada->getKey(),
                (int) $atual->getKey(),
            ],
            MetalThursday::query()
                ->publicadas(
                    $referencia,
                )
                ->orderBy(
                    'data',
                )
                ->get()
                ->modelKeys(),
        );

        self::assertSame(
            [
                (int) $futura->getKey(),
            ],
            MetalThursday::query()
                ->preparadasPorPublicar(
                    $referencia,
                )
                ->orderBy(
                    'data',
                )
                ->get()
                ->modelKeys(),
        );
    }

    /**
     * Confirma que a mudança de dia utiliza o fuso horário da aplicação.
     *
     * @since 2.0.0
     */
    #[Test]
    public function publicacao_respeita_fuso_horario_da_aplicacao(): void
    {
        config([
            'app.timezone' => 'Europe/Lisbon',
        ]);

        $edicao = $this->criarEdicao(
            'Edição de Fuso Horário',
            '2026-08-01',
            '2026-08-31',
        );

        $metalThursday = $this->criarMetalThursday(
            $edicao,
            '2026-08-29',
        );

        $referenciaUtc =
            CarbonImmutable::parse(
                '2026-08-28 23:30:00',
                'UTC',
            );

        self::assertTrue(
            $metalThursday->estaPublicada(
                $referenciaUtc,
            ),
        );

        self::assertFalse(
            $metalThursday->estaPreparada(
                $referenciaUtc,
            ),
        );
    }

    /**
     * Cria uma edição persistida com o período indicado.
     *
     * @param  string  $nome  Nome da edição.
     * @param  string  $dataInicio  Data inicial no formato AAAA-MM-DD.
     * @param  string  $dataFim  Data final no formato AAAA-MM-DD.
     * @return Edicao Edição criada.
     *
     * @since 2.0.0
     */
    private function criarEdicao(
        string $nome,
        string $dataInicio,
        string $dataFim,
    ): Edicao {
        return Edicao::factory()
            ->comNome(
                $nome,
            )
            ->comPeriodo(
                CarbonImmutable::parse(
                    $dataInicio,
                ),
                CarbonImmutable::parse(
                    $dataFim,
                ),
            )
            ->create();
    }

    /**
     * Cria uma MetalThursday persistida na edição e data indicadas.
     *
     * @param  Edicao  $edicao  Edição relacionada.
     * @param  string  $data  Data no formato AAAA-MM-DD.
     * @return MetalThursday MetalThursday criada.
     *
     * @since 2.0.0
     */
    private function criarMetalThursday(
        Edicao $edicao,
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
            ->create();
    }
}
