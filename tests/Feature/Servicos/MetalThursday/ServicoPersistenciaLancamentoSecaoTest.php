<?php

declare(strict_types=1);

namespace Tests\Feature\Servicos\MetalThursday;

use App\Enumeracoes\TipoLancamento;
use App\Models\MetalThursday\TipoSeccao;
use App\Models\Musica\FaixaLancamento;
use App\Models\Musica\Lancamento;
use App\Models\Musica\Musica;
use App\Servicos\MetalThursday\ServicoPersistenciaLancamentoSecao;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa a persistência das correções de um lançamento no formulário.
 *
 * @since 2.0.0
 */
final class ServicoPersistenciaLancamentoSecaoTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Confirma que um lançamento pode ser criado manualmente sem Discogs.
     *
     * @since 2.0.0
     */
    #[Test]
    public function cria_lancamento_manual_sem_discogs(): void
    {
        $tipoSeccao = TipoSeccao::factory()
            ->comDados(
                'lancamento',
                'Lançamento',
                'Lançamento musical.',
            )
            ->comDetalhes()
            ->create();

        $servico = app(
            ServicoPersistenciaLancamentoSecao::class,
        );

        $dadosLancamento = $servico->normalizarDados(
            [
                'titulo' => 'Lançamento manual',
                'tipo' => TipoLancamento::AlbumEstudio->value,
                'ano_original' => 2026,
                'faixas' => [
                    [
                        'id' => null,
                        'musica_id' => null,
                        'titulo' => 'Faixa manual',
                        'posicao' => '1',
                        'ordem' => 1,
                    ],
                ],
            ],
            'seccoes.0.lancamento',
        );

        self::assertIsArray(
            $dadosLancamento,
        );

        $lancamento = DB::transaction(
            fn (): Lancamento => $servico->sincronizar(
                [
                    'lancamento_id' => null,
                    'lancamento' => $dadosLancamento,
                ],
                $tipoSeccao,
            ),
        );

        self::assertTrue(
            $lancamento->exists,
        );

        self::assertNull(
            $lancamento->discogs_release_id,
        );

        self::assertSame(
            'Lançamento manual',
            $lancamento->titulo,
        );

        self::assertSame(
            TipoLancamento::AlbumEstudio,
            $lancamento->tipo,
        );

        self::assertSame(
            2026,
            $lancamento->ano_original,
        );

        $faixas = $lancamento
            ->faixas()
            ->with('musica')
            ->get();

        self::assertCount(
            1,
            $faixas,
        );

        self::assertSame(
            'Faixa manual',
            $faixas[0]->musica->titulo,
        );
    }

    /**
     * Confirma a atualização dos metadados e a sincronização segura da tracklist.
     *
     * @since 2.0.0
     */
    #[Test]
    public function sincroniza_lancamento_e_preserva_musicas_omitidas(): void
    {
        $tipoSeccao = TipoSeccao::factory()
            ->comDados(
                'lancamento',
                'Lançamento',
                'Lançamento musical.',
            )
            ->comDetalhes()
            ->create();

        $lancamento = Lancamento::factory()
            ->create([
                'titulo' => 'Título inicial',
                'tipo' => TipoLancamento::EP,
                'ano_original' => 1985,
            ]);

        $musicaMantida = Musica::factory()
            ->create([
                'titulo' => 'Faixa antiga',
            ]);

        $musicaOmitida = Musica::factory()
            ->create([
                'titulo' => 'Faixa removida da edição',
            ]);

        $faixaMantida = FaixaLancamento::factory()
            ->create([
                'lancamento_id' => $lancamento->getKey(),
                'musica_id' => $musicaMantida->getKey(),
                'posicao' => 'A1',
                'ordem' => 1,
            ]);

        $faixaOmitida = FaixaLancamento::factory()
            ->create([
                'lancamento_id' => $lancamento->getKey(),
                'musica_id' => $musicaOmitida->getKey(),
                'posicao' => 'A2',
                'ordem' => 2,
            ]);

        DB::transaction(
            function () use (
                $tipoSeccao,
                $lancamento,
                $faixaMantida,
                $musicaMantida,
            ): void {
                $servico = app(
                    ServicoPersistenciaLancamentoSecao::class,
                );

                $dadosLancamento = $servico->normalizarDados(
                    [
                        'titulo' => 'Título revisto',
                        'tipo' => TipoLancamento::AlbumEstudio->value,
                        'ano_original' => 1986,
                        'faixas' => [
                            [
                                'id' => $faixaMantida->getKey(),
                                'musica_id' => $musicaMantida->getKey(),
                                'titulo' => 'Faixa revista',
                                'posicao' => '1',
                                'ordem' => 10,
                            ],
                            [
                                'id' => null,
                                'musica_id' => null,
                                'titulo' => 'Faixa nova',
                                'posicao' => '2',
                                'ordem' => 20,
                            ],
                        ],
                    ],
                    'seccoes.0.lancamento',
                );

                self::assertIsArray(
                    $dadosLancamento,
                );

                $servico->sincronizar(
                    [
                        'lancamento_id' => (int) $lancamento->getKey(),
                        'lancamento' => $dadosLancamento,
                    ],
                    $tipoSeccao,
                );
            },
        );

        $lancamento->refresh();

        self::assertSame(
            'Título revisto',
            $lancamento->titulo,
        );

        self::assertSame(
            TipoLancamento::AlbumEstudio,
            $lancamento->tipo,
        );

        self::assertSame(
            1986,
            $lancamento->ano_original,
        );

        $faixas = $lancamento
            ->faixas()
            ->with('musica')
            ->get();

        self::assertCount(
            2,
            $faixas,
        );

        self::assertSame(
            $faixaMantida->getKey(),
            $faixas[0]->getKey(),
        );

        self::assertSame(
            'Faixa revista',
            $faixas[0]->musica->titulo,
        );

        self::assertSame(
            1,
            $faixas[0]->ordem,
        );

        self::assertSame(
            'Faixa nova',
            $faixas[1]->musica->titulo,
        );

        self::assertSame(
            2,
            $faixas[1]->ordem,
        );

        $this->assertDatabaseMissing(
            'faixas_lancamento',
            [
                'id' => $faixaOmitida->getKey(),
            ],
        );

        $this->assertDatabaseHas(
            'musicas',
            [
                'id' => $musicaOmitida->getKey(),
                'titulo' => 'Faixa removida da edição',
                'deleted_at' => null,
            ],
        );
    }

    /**
     * Confirma que uma faixa de outro lançamento não pode ser transferida.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_faixa_pertencente_a_outro_lancamento(): void
    {
        $tipoSeccao = TipoSeccao::factory()
            ->comDados(
                'lancamento',
                'Lançamento',
                'Lançamento musical.',
            )
            ->comDetalhes()
            ->create();

        $lancamento = Lancamento::factory()
            ->create([
                'titulo' => 'Primeiro lançamento',
            ]);

        $outroLancamento = Lancamento::factory()
            ->create([
                'titulo' => 'Outro lançamento',
            ]);

        $musica = Musica::factory()
            ->create();

        $faixaAlheia = FaixaLancamento::factory()
            ->create([
                'lancamento_id' => $outroLancamento->getKey(),
                'musica_id' => $musica->getKey(),
                'ordem' => 1,
            ]);

        try {
            DB::transaction(
                function () use (
                    $tipoSeccao,
                    $lancamento,
                    $faixaAlheia,
                    $musica,
                ): void {
                    $servico = app(
                        ServicoPersistenciaLancamentoSecao::class,
                    );

                    $dadosLancamento = $servico->normalizarDados(
                        [
                            'titulo' => 'Título adulterado',
                            'tipo' => null,
                            'ano_original' => null,
                            'faixas' => [
                                [
                                    'id' => $faixaAlheia->getKey(),
                                    'musica_id' => $musica->getKey(),
                                    'titulo' => 'Faixa alheia',
                                    'posicao' => '1',
                                ],
                            ],
                        ],
                        'seccoes.0.lancamento',
                    );

                    self::assertIsArray(
                        $dadosLancamento,
                    );

                    $servico->sincronizar(
                        [
                            'lancamento_id' => (int) $lancamento->getKey(),
                            'lancamento' => $dadosLancamento,
                        ],
                        $tipoSeccao,
                    );
                },
            );

            self::fail(
                'Era esperada uma exceção para uma faixa pertencente a outro lançamento.',
            );
        } catch (InvalidArgumentException $excecao) {
            self::assertSame(
                'Foi indicada uma faixa que não pertence ao lançamento editado.',
                $excecao->getMessage(),
            );
        }

        self::assertSame(
            'Primeiro lançamento',
            $lancamento->fresh()?->titulo,
        );

        $this->assertDatabaseHas(
            'faixas_lancamento',
            [
                'id' => $faixaAlheia->getKey(),
                'lancamento_id' => $outroLancamento->getKey(),
            ],
        );
    }
}
