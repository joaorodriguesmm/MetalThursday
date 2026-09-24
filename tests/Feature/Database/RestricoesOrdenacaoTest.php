<?php

declare(strict_types=1);

namespace Tests\Feature\Database;

use App\Models\MetalThursday\LigacaoSeccaoMetalThursday;
use App\Models\MetalThursday\SeccaoMetalThursday;
use App\Models\Musica\FaixaLancamento;
use App\Models\Musica\Lancamento;
use App\Models\Musica\Musica;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa as restrições de integridade das ordens persistidas.
 *
 * @since 2.0.0
 */
final class RestricoesOrdenacaoTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Confirma que duas ligações da mesma secção não podem ocupar a mesma
     * posição funcional.
     *
     * @since 2.0.0
     */
    #[Test]
    public function impede_ordens_de_ligacao_repetidas_na_mesma_seccao(): void
    {
        $seccao = SeccaoMetalThursday::factory()
            ->create();

        LigacaoSeccaoMetalThursday::factory()
            ->for(
                $seccao,
                'seccaoMetalThursday',
            )
            ->create([
                'url' => 'https://example.com/primeira',
                'ordem' => 1,
            ]);

        try {
            LigacaoSeccaoMetalThursday::factory()
                ->for(
                    $seccao,
                    'seccaoMetalThursday',
                )
                ->create([
                    'url' => 'https://example.com/segunda',
                    'ordem' => 1,
                ]);

            self::fail(
                'A base de dados aceitou duas ligações com a mesma ordem na mesma secção.',
            );
        } catch (QueryException) {
            self::assertSame(
                1,
                LigacaoSeccaoMetalThursday::query()
                    ->where(
                        'seccao_metal_thursday_id',
                        $seccao->getKey(),
                    )
                    ->where(
                        'ordem',
                        1,
                    )
                    ->count(),
            );
        }
    }

    /**
     * Confirma que duas faixas do mesmo lançamento não podem possuir a mesma
     * ordem conhecida.
     *
     * @since 2.0.0
     */
    #[Test]
    public function impede_ordens_de_faixa_conhecidas_repetidas_no_mesmo_lancamento(): void
    {
        $lancamento = Lancamento::factory()
            ->create();

        $primeiraMusica = Musica::factory()
            ->create();

        $segundaMusica = Musica::factory()
            ->create();

        FaixaLancamento::factory()
            ->create([
                'lancamento_id' => $lancamento->getKey(),
                'musica_id' => $primeiraMusica->getKey(),
                'ordem' => 1,
            ]);

        try {
            FaixaLancamento::factory()
                ->create([
                    'lancamento_id' => $lancamento->getKey(),
                    'musica_id' => $segundaMusica->getKey(),
                    'ordem' => 1,
                ]);

            self::fail(
                'A base de dados aceitou duas faixas com a mesma ordem conhecida no mesmo lançamento.',
            );
        } catch (QueryException) {
            self::assertSame(
                1,
                FaixaLancamento::query()
                    ->where(
                        'lancamento_id',
                        $lancamento->getKey(),
                    )
                    ->where(
                        'ordem',
                        1,
                    )
                    ->count(),
            );
        }
    }

    /**
     * Confirma que várias faixas sem ordem conhecida continuam válidas.
     *
     * @since 2.0.0
     */
    #[Test]
    public function permite_varias_faixas_sem_ordem_conhecida_no_mesmo_lancamento(): void
    {
        $lancamento = Lancamento::factory()
            ->create();

        $primeiraMusica = Musica::factory()
            ->create();

        $segundaMusica = Musica::factory()
            ->create();

        FaixaLancamento::factory()
            ->create([
                'lancamento_id' => $lancamento->getKey(),
                'musica_id' => $primeiraMusica->getKey(),
                'ordem' => null,
            ]);

        FaixaLancamento::factory()
            ->create([
                'lancamento_id' => $lancamento->getKey(),
                'musica_id' => $segundaMusica->getKey(),
                'ordem' => null,
            ]);

        self::assertSame(
            2,
            FaixaLancamento::query()
                ->where(
                    'lancamento_id',
                    $lancamento->getKey(),
                )
                ->whereNull(
                    'ordem',
                )
                ->count(),
        );
    }
}
