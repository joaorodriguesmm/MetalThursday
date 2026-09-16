<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\MetalThursday;

use App\Enumeracoes\PapelUtilizador;
use App\Enumeracoes\TipoLancamento;
use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\Edicao;
use App\Models\MetalThursday\MetalThursday;
use App\Models\MetalThursday\TipoSeccao;
use App\Models\Musica\Artista;
use App\Models\Musica\Lancamento;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa o fluxo HTTP do editor estruturado de lançamentos.
 *
 * @since 2.0.0
 */
final class ControladorMetalThursdayLancamentoEstruturadoTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Confirma que o lançamento é a fonte dos metadados da secção e que o
     * ano original pode permanecer desconhecido.
     *
     * @since 2.0.0
     */
    #[Test]
    public function cria_seccao_lancamento_com_metadados_estruturados_e_ano_original_nulo(): void
    {
        Notification::fake();

        $administrador = Utilizador::factory()
            ->comPapel(
                PapelUtilizador::Administrador,
            )
            ->create();

        $proximoNomeado = Utilizador::factory()
            ->create();

        Edicao::factory()
            ->comPeriodo(
                CarbonImmutable::parse(
                    '2026-01-01',
                ),
                CarbonImmutable::parse(
                    '2026-01-31',
                ),
            )
            ->create();

        $tipoSeccao = TipoSeccao::factory()
            ->comDados(
                'lancamento',
                'Lançamento',
                'Lançamento musical.',
            )
            ->comDetalhes()
            ->create();

        $artista = Artista::factory()
            ->create();

        $lancamento = Lancamento::factory()
            ->create([
                'titulo' => 'Título importado',
                'tipo' => TipoLancamento::EP,
                'ano_original' => 1999,
                'discogs_release_id' => 123456,
            ]);

        $this->actingAs(
            $administrador,
            'sessao',
        );

        $this
            ->postJson(
                route(
                    'metal-thursday.guardar',
                ),
                [
                    'data' => '2026-01-15',
                    'nome' => null,
                    'autor_id' => $administrador->getKey(),
                    'proximo_nomeado_id' => $proximoNomeado->getKey(),
                    'seccoes' => [
                        [
                            'id' => null,
                            'tipo_seccao_id' => $tipoSeccao->getKey(),
                            'descricao' => 'Descrição do lançamento.',
                            'artista_id' => $artista->getKey(),
                            'lancamento_id' => $lancamento->getKey(),
                            'lancamento' => [
                                'titulo' => 'Título revisto',
                                'tipo' => TipoLancamento::AlbumEstudio->value,
                                'ano_original' => null,
                                'faixas' => [],
                            ],
                            'ligacao' => 'https://example.com/ouvir',
                            'tipo_incorporacao' => 'ligacao',
                        ],
                    ],
                ],
            )
            ->assertCreated();

        $this->assertDatabaseHas(
            'lancamentos',
            [
                'id' => $lancamento->getKey(),
                'titulo' => 'Título revisto',
                'tipo' => TipoLancamento::AlbumEstudio->value,
                'ano_original' => null,
            ],
        );

        $this->assertDatabaseHas(
            'seccoes_metal_thursday',
            [
                'tipo_seccao_id' => $tipoSeccao->getKey(),
                'artista_id' => $artista->getKey(),
                'lancamento_id' => $lancamento->getKey(),
                'titulo' => 'Título revisto',
                'ano' => null,
                'deleted_at' => null,
            ],
        );
    }

    /**
     * Confirma que a criação manual não depende de uma importação Discogs.
     *
     * @since 2.0.0
     */
    #[Test]
    public function cria_lancamento_manual_sem_discogs(): void
    {
        Notification::fake();

        $administrador = Utilizador::factory()
            ->comPapel(
                PapelUtilizador::Administrador,
            )
            ->create();

        $proximoNomeado = Utilizador::factory()
            ->create();

        Edicao::factory()
            ->comPeriodo(
                CarbonImmutable::parse(
                    '2026-02-01',
                ),
                CarbonImmutable::parse(
                    '2026-02-28',
                ),
            )
            ->create();

        $tipoSeccao = TipoSeccao::factory()
            ->comDados(
                'lancamento',
                'Lançamento',
                'Lançamento musical.',
            )
            ->comDetalhes()
            ->create();

        $artista = Artista::factory()
            ->create();

        $this
            ->actingAs(
                $administrador,
                'sessao',
            )
            ->postJson(
                route(
                    'metal-thursday.guardar',
                ),
                [
                    'data' => '2026-02-12',
                    'nome' => null,
                    'autor_id' => $administrador->getKey(),
                    'proximo_nomeado_id' => $proximoNomeado->getKey(),
                    'seccoes' => [
                        [
                            'id' => null,
                            'tipo_seccao_id' => $tipoSeccao->getKey(),
                            'descricao' => 'Criado manualmente.',
                            'artista_id' => $artista->getKey(),
                            'lancamento_id' => null,
                            'lancamento' => [
                                'titulo' => 'Lançamento manual',
                                'tipo' => TipoLancamento::EP->value,
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
                            'ligacao' => 'https://example.com/ouvir-manual',
                            'tipo_incorporacao' => 'ligacao',
                        ],
                    ],
                ],
            )
            ->assertCreated();

        $lancamento = Lancamento::query()
            ->where(
                'titulo',
                'Lançamento manual',
            )
            ->firstOrFail();

        self::assertNull(
            $lancamento->discogs_release_id,
        );

        self::assertSame(
            TipoLancamento::EP,
            $lancamento->tipo,
        );

        self::assertSame(
            2026,
            $lancamento->ano_original,
        );

        $this->assertDatabaseHas(
            'seccoes_metal_thursday',
            [
                'tipo_seccao_id' => $tipoSeccao->getKey(),
                'artista_id' => $artista->getKey(),
                'lancamento_id' => $lancamento->getKey(),
                'titulo' => 'Lançamento manual',
                'ano' => 2026,
                'deleted_at' => null,
            ],
        );

        $this->assertDatabaseHas(
            'musicas',
            [
                'titulo' => 'Faixa manual',
                'deleted_at' => null,
            ],
        );

        $metalThursday = MetalThursday::query()
            ->whereDate(
                'data',
                '2026-02-12',
            )
            ->firstOrFail();

        $this
            ->get(
                route(
                    'metal-thursday.editar',
                    $metalThursday,
                ),
            )
            ->assertOk()
            ->assertSee(
                'Lançamento manual',
            )
            ->assertSee(
                'Faixa manual',
            )
            ->assertSeeHtml(
                'name="seccoes[0][lancamento_id]"',
            )
            ->assertSeeHtml(
                'value="'.$lancamento->getKey().'"',
            );
    }
}
