<?php

declare(strict_types=1);

namespace Tests\Feature\Servicos\MetalThursday;

use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\Edicao;
use App\Models\MetalThursday\ReservaMetalThursday;
use App\Models\MetalThursday\TipoSeccao;
use App\Models\Musica\Artista;
use App\Models\Musica\Lancamento;
use App\Resultados\MetalThursday\MetalThursdayCriada;
use App\Servicos\MetalThursday\ServicoPersistenciaMetalThursday;
use App\Servicos\MetalThursday\ServicoReservasMetalThursday;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa a persistência transacional das MetalThursdays e das respetivas
 * secções.
 *
 * @since 2.0.0
 */
final class ServicoPersistenciaMetalThursdayTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Confirma que o serviço traduz o campo público do tipo para a coluna
     * física correta da secção.
     *
     * @since 2.0.0
     */
    #[Test]
    public function cria_metal_thursday_com_seccao_detalhada(): void
    {
        $utilizador = Utilizador::factory()
            ->create();

        $this->actingAs(
            $utilizador,
            'sessao',
        );

        $edicao =
            $this->criarEdicao();

        $tipoSeccao = TipoSeccao::factory()
            ->comDetalhes()
            ->create();

        $artista = Artista::factory()
            ->create();

        $metalThursday = $this
            ->servico()
            ->criar([
                'edicao_id' => (int) $edicao->getKey(),

                'data' => '2026-01-08',

                'nome' => 'Especial de janeiro',

                'autor_id' => (int) $utilizador->getKey(),

                'proximo_nomeado_id' => (int) $utilizador->getKey(),

                'seccoes' => [
                    [
                        'id' => null,

                        'tipo_seccao_id' => (int) $tipoSeccao->getKey(),

                        'titulo' => 'Faixa da semana',

                        'descricao' => 'Descrição conhecida da secção.',

                        'artista_id' => (int) $artista->getKey(),

                        'ligacoes' => [
                            [
                                'url' => 'https://example.com/faixa',
                                'etiqueta' => 'Ouvir',
                                'incorporar' => false,
                            ],
                        ],

                        'ano' => 2026,
                    ],
                ],
            ]);

        self::assertSame(
            'Especial de janeiro',
            $metalThursday->nome,
        );

        $this->assertDatabaseHas(
            'seccoes_metal_thursday',
            [
                'metal_thursday_id' => $metalThursday->getKey(),

                'tipo_seccao_id' => $tipoSeccao->getKey(),

                'artista_id' => $artista->getKey(),

                'ordem' => 1,

                'titulo' => 'Faixa da semana',

                'ano' => 2026,

                'deleted_at' => null,
            ],
        );
    }

    /**
     * Confirma que a publicação cumpre a reserva atual e encadeia a reserva da
     * quinta-feira seguinte para o utilizador nomeado.
     *
     * @since 2.0.0
     */
    #[Test]
    public function cria_reserva_seguinte_ao_publicar(): void
    {
        $autor = Utilizador::factory()
            ->create();

        $nomeado = Utilizador::factory()
            ->create();

        $edicao =
            $this->criarEdicao();

        $tipoSeccao = TipoSeccao::factory()
            ->semDetalhes()
            ->create();

        $reservaAtual = ReservaMetalThursday::factory()
            ->comData(
                CarbonImmutable::parse(
                    '2026-01-08',
                ),
            )
            ->comResponsavel(
                $autor,
            )
            ->create();

        $resultado = $this
            ->servico()
            ->criarComResultado([
                'edicao_id' => (int) $edicao->getKey(),

                'data' => '2026-01-08',

                'nome' => null,

                'autor_id' => (int) $autor->getKey(),

                'proximo_nomeado_id' => (int) $nomeado->getKey(),

                'seccoes' => [
                    $this->dadosSeccaoSimples(
                        $tipoSeccao,
                        'Descrição válida.',
                    ),
                ],
            ]);

        self::assertInstanceOf(
            MetalThursdayCriada::class,
            $resultado,
        );

        $metalThursday =
            $resultado->obterMetalThursday();

        $reservaSeguinte =
            $resultado->obterReservaSeguinte();

        self::assertInstanceOf(
            ReservaMetalThursday::class,
            $reservaSeguinte,
        );

        self::assertSame(
            '2026-01-15',
            $reservaSeguinte->data->toDateString(),
        );

        self::assertSame(
            $nomeado->getKey(),
            $reservaSeguinte->responsavel_id,
        );

        self::assertSame(
            $nomeado->getKey(),
            $metalThursday->proximo_nomeado_id,
        );

        self::assertSame(
            $metalThursday->getKey(),
            $reservaAtual
                ->refresh()
                ->metal_thursday_id,
        );

        $this->assertDatabaseHas(
            'reservas_metal_thursday',
            [
                'data' => '2026-01-15',

                'responsavel_id' => $nomeado->getKey(),

                'metal_thursday_id' => null,
            ],
        );

        $this->assertDatabaseCount(
            'reservas_metal_thursday',
            2,
        );
    }

    /**
     * Confirma que uma publicação tardia cumpre a reserva antiga sem substituir
     * a reserva seguinte que já tinha sido criada.
     *
     * @since 2.0.0
     */
    #[Test]
    public function preserva_reserva_seguinte_existente_em_publicacao_tardia(): void
    {
        $autor = Utilizador::factory()
            ->create();

        $responsavelSeguinte = Utilizador::factory()
            ->create();

        $propostaTardia = Utilizador::factory()
            ->indisponivelParaNomeacao()
            ->create();

        $edicao =
            $this->criarEdicao();

        $tipoSeccao = TipoSeccao::factory()
            ->semDetalhes()
            ->create();

        $reservaAtual = ReservaMetalThursday::factory()
            ->comData(
                CarbonImmutable::parse(
                    '2026-01-08',
                ),
            )
            ->comResponsavel(
                $autor,
            )
            ->create();

        $reservaSeguinte = ReservaMetalThursday::factory()
            ->comData(
                CarbonImmutable::parse(
                    '2026-01-15',
                ),
            )
            ->comResponsavel(
                $responsavelSeguinte,
            )
            ->create();

        $resultado = $this
            ->servico()
            ->criarComResultado([
                'edicao_id' => (int) $edicao->getKey(),

                'data' => '2026-01-08',

                'nome' => null,

                'autor_id' => (int) $autor->getKey(),

                'proximo_nomeado_id' => (int) $propostaTardia->getKey(),

                'seccoes' => [
                    $this->dadosSeccaoSimples(
                        $tipoSeccao,
                        'Descrição válida.',
                    ),
                ],
            ]);

        self::assertInstanceOf(
            MetalThursdayCriada::class,
            $resultado,
        );

        $metalThursday =
            $resultado->obterMetalThursday();

        self::assertNull(
            $resultado->obterReservaSeguinte(),
        );

        self::assertSame(
            $metalThursday->getKey(),
            $reservaAtual
                ->refresh()
                ->metal_thursday_id,
        );

        self::assertSame(
            $responsavelSeguinte->getKey(),
            $reservaSeguinte
                ->refresh()
                ->responsavel_id,
        );

        self::assertSame(
            $responsavelSeguinte->getKey(),
            $metalThursday->proximo_nomeado_id,
        );

        self::assertNotSame(
            $propostaTardia->getKey(),
            $metalThursday->proximo_nomeado_id,
        );

        $this->assertDatabaseMissing(
            'reservas_metal_thursday',
            [
                'responsavel_id' => $propostaTardia->getKey(),
            ],
        );

        $this->assertDatabaseCount(
            'reservas_metal_thursday',
            2,
        );
    }

    /**
     * Confirma que um slot seguinte sem responsável prevalece sobre uma
     * proposta tardia e mantém o campo legado sem nomeado.
     *
     * @since 2.0.0
     */
    #[Test]
    public function espelha_reserva_seguinte_sem_responsavel_na_publicacao(): void
    {
        $autor = Utilizador::factory()
            ->create();

        $propostaTardia = Utilizador::factory()
            ->create();

        $edicao =
            $this->criarEdicao();

        $tipoSeccao = TipoSeccao::factory()
            ->semDetalhes()
            ->create();

        ReservaMetalThursday::factory()
            ->comData(
                CarbonImmutable::parse(
                    '2026-01-08',
                ),
            )
            ->comResponsavel(
                $autor,
            )
            ->create();

        $reservaSeguinte = ReservaMetalThursday::factory()
            ->comData(
                CarbonImmutable::parse(
                    '2026-01-15',
                ),
            )
            ->semResponsavel()
            ->create();

        $resultado = $this
            ->servico()
            ->criarComResultado([
                'edicao_id' => (int) $edicao->getKey(),

                'data' => '2026-01-08',

                'nome' => null,

                'autor_id' => (int) $autor->getKey(),

                'proximo_nomeado_id' => (int) $propostaTardia->getKey(),

                'seccoes' => [
                    $this->dadosSeccaoSimples(
                        $tipoSeccao,
                        'Descrição válida.',
                    ),
                ],
            ]);

        self::assertNull(
            $resultado->obterReservaSeguinte(),
        );

        $metalThursday =
            $resultado->obterMetalThursday();

        self::assertNull(
            $metalThursday->proximo_nomeado_id,
        );

        self::assertNull(
            $reservaSeguinte
                ->refresh()
                ->responsavel_id,
        );

        $this->assertDatabaseMissing(
            'reservas_metal_thursday',
            [
                'responsavel_id' => $propostaTardia->getKey(),
            ],
        );
    }

    /**
     * Confirma que uma atualização não consegue alterar o espelho da reserva
     * seguinte já persistida.
     *
     * @since 2.0.0
     */
    #[Test]
    public function atualizacao_preserva_nomeado_efetivo_persistido(): void
    {
        $autor = Utilizador::factory()
            ->create();

        $nomeadoEfetivo = Utilizador::factory()
            ->create();

        $novaProposta = Utilizador::factory()
            ->create();

        $edicao =
            $this->criarEdicao();

        $tipoSeccao = TipoSeccao::factory()
            ->semDetalhes()
            ->create();

        $servico =
            $this->servico();

        $metalThursday = $servico->criar([
            'edicao_id' => (int) $edicao->getKey(),

            'data' => '2026-01-08',

            'nome' => null,

            'autor_id' => (int) $autor->getKey(),

            'proximo_nomeado_id' => (int) $nomeadoEfetivo->getKey(),

            'seccoes' => [
                $this->dadosSeccaoSimples(
                    $tipoSeccao,
                    'Descrição inicial.',
                ),
            ],
        ]);

        $seccao =
            $metalThursday->seccoes->first();

        self::assertNotNull(
            $seccao,
        );

        $metalThursdayAtualizada = $servico->atualizar(
            $metalThursday,
            [
                'edicao_id' => (int) $edicao->getKey(),

                'data' => '2026-01-08',

                'nome' => 'Nome atualizado',

                'autor_id' => (int) $autor->getKey(),

                'proximo_nomeado_id' => (int) $novaProposta->getKey(),

                'seccoes' => [
                    [
                        ...$this->dadosSeccaoSimples(
                            $tipoSeccao,
                            'Descrição atualizada.',
                        ),

                        'id' => (int) $seccao->getKey(),
                    ],
                ],
            ],
        );

        self::assertSame(
            $nomeadoEfetivo->getKey(),
            $metalThursdayAtualizada->proximo_nomeado_id,
        );

        $this->assertDatabaseHas(
            'reservas_metal_thursday',
            [
                'data' => '2026-01-15',

                'responsavel_id' => $nomeadoEfetivo->getKey(),

                'metal_thursday_id' => null,
            ],
        );

        $this->assertDatabaseMissing(
            'reservas_metal_thursday',
            [
                'responsavel_id' => $novaProposta->getKey(),
            ],
        );
    }

    /**
     * Confirma que o serviço permite conservar numa secção existente o artista
     * que tenha sido entretanto eliminado logicamente.
     *
     * @since 2.0.0
     */
    #[Test]
    public function atualizacao_preserva_artista_eliminado_logicamente_ja_associado(): void
    {
        $utilizador = Utilizador::factory()
            ->create();

        $edicao =
            $this->criarEdicao();

        $tipoSeccao = TipoSeccao::factory()
            ->comDetalhes()
            ->create();

        $artista = Artista::factory()
            ->create();

        $servico =
            $this->servico();

        $metalThursday = $servico->criar([
            'edicao_id' => (int) $edicao->getKey(),

            'data' => '2026-01-08',

            'nome' => null,

            'autor_id' => (int) $utilizador->getKey(),

            'proximo_nomeado_id' => null,

            'seccoes' => [
                [
                    'id' => null,

                    'tipo_seccao_id' => (int) $tipoSeccao->getKey(),

                    'titulo' => 'Álbum histórico',

                    'descricao' => 'Descrição histórica.',

                    'artista_id' => (int) $artista->getKey(),

                    'ligacoes' => [
                        [
                            'url' => 'https://example.com/album-historico',
                            'etiqueta' => 'Ouvir',
                            'incorporar' => false,
                        ],
                    ],

                    'ano' => 2020,
                ],
            ],
        ]);

        $seccao =
            $metalThursday->seccoes->first();

        self::assertNotNull(
            $seccao,
        );

        $artista->deleteOrFail();

        $servico->atualizar(
            $metalThursday,
            [
                'edicao_id' => (int) $edicao->getKey(),

                'data' => '2026-01-08',

                'nome' => null,

                'autor_id' => (int) $utilizador->getKey(),

                'proximo_nomeado_id' => null,

                'seccoes' => [
                    [
                        'id' => (int) $seccao->getKey(),

                        'tipo_seccao_id' => (int) $tipoSeccao->getKey(),

                        'titulo' => 'Álbum histórico atualizado',

                        'descricao' => 'Descrição histórica atualizada.',

                        'artista_id' => (int) $artista->getKey(),

                        'ligacoes' => [
                            [
                                'url' => 'https://example.com/album-historico',
                                'etiqueta' => 'Ouvir',
                                'incorporar' => false,
                            ],
                        ],

                        'ano' => 2020,
                    ],
                ],
            ],
        );

        $this->assertDatabaseHas(
            'seccoes_metal_thursday',
            [
                'id' => $seccao->getKey(),

                'artista_id' => $artista->getKey(),

                'titulo' => 'Álbum histórico atualizado',

                'deleted_at' => null,
            ],
        );
    }

    /**
     * Confirma que o serviço rejeita um artista eliminado logicamente numa nova
     * secção durante uma atualização.
     *
     * @since 2.0.0
     */
    #[Test]
    public function atualizacao_rejeita_artista_eliminado_logicamente_em_nova_seccao(): void
    {
        $utilizador = Utilizador::factory()
            ->create();

        $edicao =
            $this->criarEdicao();

        $tipoSeccao = TipoSeccao::factory()
            ->comDetalhes()
            ->create();

        $artistaAtivo = Artista::factory()
            ->create();

        $artistaEliminado = Artista::factory()
            ->create();

        $servico =
            $this->servico();

        $metalThursday = $servico->criar([
            'edicao_id' => (int) $edicao->getKey(),

            'data' => '2026-01-08',

            'nome' => null,

            'autor_id' => (int) $utilizador->getKey(),

            'proximo_nomeado_id' => null,

            'seccoes' => [
                [
                    'id' => null,

                    'tipo_seccao_id' => (int) $tipoSeccao->getKey(),

                    'titulo' => 'Secção existente',

                    'descricao' => 'Descrição existente.',

                    'artista_id' => (int) $artistaAtivo->getKey(),

                    'ligacoes' => [
                        [
                            'url' => 'https://example.com/seccao-existente',
                            'etiqueta' => 'Ouvir',
                            'incorporar' => false,
                        ],
                    ],

                    'ano' => 2020,
                ],
            ],
        ]);

        $seccaoExistente =
            $metalThursday->seccoes->first();

        self::assertNotNull(
            $seccaoExistente,
        );

        $artistaEliminado->deleteOrFail();

        try {
            $servico->atualizar(
                $metalThursday,
                [
                    'edicao_id' => (int) $edicao->getKey(),

                    'data' => '2026-01-08',

                    'nome' => null,

                    'autor_id' => (int) $utilizador->getKey(),

                    'proximo_nomeado_id' => null,

                    'seccoes' => [
                        [
                            'id' => (int) $seccaoExistente->getKey(),

                            'tipo_seccao_id' => (int) $tipoSeccao->getKey(),

                            'titulo' => 'Secção existente',

                            'descricao' => 'Descrição existente.',

                            'artista_id' => (int) $artistaAtivo->getKey(),

                            'ligacoes' => [
                                [
                                    'url' => 'https://example.com/seccao-existente',
                                    'etiqueta' => 'Ouvir',
                                    'incorporar' => false,
                                ],
                            ],

                            'ano' => 2020,
                        ],
                        [
                            'id' => null,

                            'tipo_seccao_id' => (int) $tipoSeccao->getKey(),

                            'titulo' => 'Nova secção inválida',

                            'descricao' => 'Descrição da nova secção.',

                            'artista_id' => (int) $artistaEliminado->getKey(),

                            'ligacoes' => [
                                [
                                    'url' => 'https://example.com/nova-seccao',
                                    'etiqueta' => 'Ouvir',
                                    'incorporar' => false,
                                ],
                            ],

                            'ano' => 2021,
                        ],
                    ],
                ],
            );

            self::fail(
                'Era esperada uma exceção para um artista eliminado numa nova secção.',
            );
        } catch (InvalidArgumentException $excecao) {
            self::assertSame(
                'Foi indicado um artista inexistente ou indisponível.',
                $excecao->getMessage(),
            );
        }

        $this->assertDatabaseHas(
            'seccoes_metal_thursday',
            [
                'id' => $seccaoExistente->getKey(),

                'artista_id' => $artistaAtivo->getKey(),

                'titulo' => 'Secção existente',

                'deleted_at' => null,
            ],
        );

        $this->assertDatabaseMissing(
            'seccoes_metal_thursday',
            [
                'metal_thursday_id' => $metalThursday->getKey(),

                'titulo' => 'Nova secção inválida',
            ],
        );
    }

    /**
     * Confirma que o serviço não permite transferir um artista eliminado
     * logicamente para outra secção, mesmo quando a associação histórica
     * original é preservada na mesma atualização.
     *
     * @since 2.0.0
     */
    #[Test]
    public function atualizacao_rejeita_transferencia_de_artista_eliminado_para_outra_seccao(): void
    {
        $utilizador = Utilizador::factory()
            ->create();

        $edicao =
            $this->criarEdicao();

        $tipoSeccao = TipoSeccao::factory()
            ->comDetalhes()
            ->create();

        $artistaHistorico = Artista::factory()
            ->create();

        $outroArtista = Artista::factory()
            ->create();

        $servico =
            $this->servico();

        $metalThursday = $servico->criar([
            'edicao_id' => (int) $edicao->getKey(),

            'data' => '2026-01-08',

            'nome' => null,

            'autor_id' => (int) $utilizador->getKey(),

            'proximo_nomeado_id' => null,

            'seccoes' => [
                [
                    'id' => null,

                    'tipo_seccao_id' => (int) $tipoSeccao->getKey(),

                    'titulo' => 'Secção histórica',

                    'descricao' => 'Descrição histórica.',

                    'artista_id' => (int) $artistaHistorico->getKey(),

                    'ligacoes' => [
                        [
                            'url' => 'https://example.com/historica',
                            'etiqueta' => 'Ouvir',
                            'incorporar' => false,
                        ],
                    ],

                    'ano' => 2020,
                ],
                [
                    'id' => null,

                    'tipo_seccao_id' => (int) $tipoSeccao->getKey(),

                    'titulo' => 'Outra secção',

                    'descricao' => 'Outra descrição.',

                    'artista_id' => (int) $outroArtista->getKey(),

                    'ligacoes' => [
                        [
                            'url' => 'https://example.com/outra',
                            'etiqueta' => 'Ouvir',
                            'incorporar' => false,
                        ],
                    ],

                    'ano' => 2021,
                ],
            ],
        ]);

        $seccoes =
            $metalThursday->seccoes->values();

        $seccaoHistorica =
            $seccoes->get(0);

        $outraSeccao =
            $seccoes->get(1);

        self::assertNotNull(
            $seccaoHistorica,
        );

        self::assertNotNull(
            $outraSeccao,
        );

        $artistaHistorico->deleteOrFail();

        try {
            $servico->atualizar(
                $metalThursday,
                [
                    'edicao_id' => (int) $edicao->getKey(),

                    'data' => '2026-01-08',

                    'nome' => null,

                    'autor_id' => (int) $utilizador->getKey(),

                    'proximo_nomeado_id' => null,

                    'seccoes' => [
                        [
                            'id' => (int) $seccaoHistorica->getKey(),

                            'tipo_seccao_id' => (int) $tipoSeccao->getKey(),

                            'titulo' => 'Secção histórica',

                            'descricao' => 'Descrição histórica.',

                            'artista_id' => (int) $artistaHistorico->getKey(),

                            'ligacoes' => [
                                [
                                    'url' => 'https://example.com/historica',
                                    'etiqueta' => 'Ouvir',
                                    'incorporar' => false,
                                ],
                            ],

                            'ano' => 2020,
                        ],
                        [
                            'id' => (int) $outraSeccao->getKey(),

                            'tipo_seccao_id' => (int) $tipoSeccao->getKey(),

                            'titulo' => 'Outra secção',

                            'descricao' => 'Outra descrição.',

                            'artista_id' => (int) $artistaHistorico->getKey(),

                            'ligacoes' => [
                                [
                                    'url' => 'https://example.com/outra',
                                    'etiqueta' => 'Ouvir',
                                    'incorporar' => false,
                                ],
                            ],

                            'ano' => 2021,
                        ],
                    ],
                ],
            );

            self::fail(
                'Era esperada uma exceção ao transferir um artista eliminado para outra secção.',
            );
        } catch (InvalidArgumentException $excecao) {
            self::assertSame(
                'Foi indicado um artista inexistente ou indisponível.',
                $excecao->getMessage(),
            );
        }

        $this->assertDatabaseHas(
            'seccoes_metal_thursday',
            [
                'id' => $seccaoHistorica->getKey(),

                'artista_id' => $artistaHistorico->getKey(),
            ],
        );

        $this->assertDatabaseHas(
            'seccoes_metal_thursday',
            [
                'id' => $outraSeccao->getKey(),

                'artista_id' => $outroArtista->getKey(),
            ],
        );
    }

    /**
     * Confirma que a atualização permite trocar a ordem de secções sem violar
     * a restrição única das posições ativas.
     *
     * @since 2.0.0
     */
    #[Test]
    public function atualiza_e_reordena_seccoes_existentes(): void
    {
        $utilizador = Utilizador::factory()
            ->create();

        $this->actingAs(
            $utilizador,
            'sessao',
        );

        $edicao =
            $this->criarEdicao();

        $tipoSeccao = TipoSeccao::factory()
            ->semDetalhes()
            ->create();

        $servico =
            $this->servico();

        $metalThursday = $servico->criar([
            'edicao_id' => (int) $edicao->getKey(),

            'data' => '2026-01-08',

            'nome' => null,

            'autor_id' => (int) $utilizador->getKey(),

            'proximo_nomeado_id' => null,

            'seccoes' => [
                $this->dadosSeccaoSimples(
                    $tipoSeccao,
                    'Primeira descrição.',
                ),

                $this->dadosSeccaoSimples(
                    $tipoSeccao,
                    'Segunda descrição.',
                ),
            ],
        ]);

        $seccoes = $metalThursday
            ->seccoes
            ->values();

        $primeira =
            $seccoes->get(0);

        $segunda =
            $seccoes->get(1);

        self::assertNotNull(
            $primeira,
        );

        self::assertNotNull(
            $segunda,
        );

        $servico->atualizar(
            $metalThursday,
            [
                'edicao_id' => (int) $edicao->getKey(),

                'data' => '2026-01-08',

                'nome' => null,

                'autor_id' => (int) $utilizador->getKey(),

                'proximo_nomeado_id' => null,

                'seccoes' => [
                    [
                        ...$this->dadosSeccaoSimples(
                            $tipoSeccao,
                            'Segunda descrição atualizada.',
                        ),

                        'id' => (int) $segunda->getKey(),
                    ],
                    [
                        ...$this->dadosSeccaoSimples(
                            $tipoSeccao,
                            'Primeira descrição atualizada.',
                        ),

                        'id' => (int) $primeira->getKey(),
                    ],
                ],
            ],
        );

        $this->assertDatabaseHas(
            'seccoes_metal_thursday',
            [
                'id' => $segunda->getKey(),

                'ordem' => 1,

                'descricao' => 'Segunda descrição atualizada.',

                'deleted_at' => null,
            ],
        );

        $this->assertDatabaseHas(
            'seccoes_metal_thursday',
            [
                'id' => $primeira->getKey(),

                'ordem' => 2,

                'descricao' => 'Primeira descrição atualizada.',

                'deleted_at' => null,
            ],
        );
    }

    /**
     * Confirma que o serviço protege o período da edição sem depender do
     * Form Request.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_data_fora_do_periodo_da_edicao(): void
    {
        $edicao =
            $this->criarEdicao();

        $tipoSeccao = TipoSeccao::factory()
            ->semDetalhes()
            ->create();

        try {
            $this
                ->servico()
                ->criar([
                    'edicao_id' => (int) $edicao->getKey(),

                    'data' => '2026-02-01',

                    'nome' => null,

                    'autor_id' => null,

                    'proximo_nomeado_id' => null,

                    'seccoes' => [
                        $this->dadosSeccaoSimples(
                            $tipoSeccao,
                            'Descrição válida.',
                        ),
                    ],
                ]);

            self::fail(
                'Era esperada uma exceção para uma data fora da edição.',
            );
        } catch (InvalidArgumentException $excecao) {
            self::assertSame(
                'A data da MetalThursday não pode ser posterior ao fim da edição.',
                $excecao->getMessage(),
            );
        }

        $this->assertDatabaseCount(
            'metal_thursdays',
            0,
        );
    }

    /**
     * Confirma que uma secção detalhada exige todos os respetivos campos.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_seccao_detalhada_incompleta(): void
    {
        $edicao =
            $this->criarEdicao();

        $tipoSeccao = TipoSeccao::factory()
            ->comDetalhes()
            ->create();

        try {
            $this
                ->servico()
                ->criar([
                    'edicao_id' => (int) $edicao->getKey(),

                    'data' => '2026-01-08',

                    'nome' => null,

                    'autor_id' => null,

                    'proximo_nomeado_id' => null,

                    'seccoes' => [
                        $this->dadosSeccaoSimples(
                            $tipoSeccao,
                            'Descrição válida.',
                        ),
                    ],
                ]);

            self::fail(
                'Era esperada uma exceção para uma secção incompleta.',
            );
        } catch (InvalidArgumentException $excecao) {
            self::assertSame(
                'O título é obrigatório numa secção detalhada.',
                $excecao->getMessage(),
            );
        }

        $this->assertDatabaseCount(
            'metal_thursdays',
            0,
        );
    }

    /**
     * Confirma que os tipos simples não aceitam detalhes musicais.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_detalhes_num_tipo_simples(): void
    {
        $edicao =
            $this->criarEdicao();

        $tipoSeccao = TipoSeccao::factory()
            ->semDetalhes()
            ->create();

        try {
            $this
                ->servico()
                ->criar([
                    'edicao_id' => (int) $edicao->getKey(),

                    'data' => '2026-01-08',

                    'nome' => null,

                    'autor_id' => null,

                    'proximo_nomeado_id' => null,

                    'seccoes' => [
                        [
                            ...$this->dadosSeccaoSimples(
                                $tipoSeccao,
                                'Descrição válida.',
                            ),

                            'titulo' => 'Título incompatível',
                        ],
                    ],
                ]);

            self::fail(
                'Era esperada uma exceção para detalhes incompatíveis.',
            );
        } catch (InvalidArgumentException $excecao) {
            self::assertSame(
                'Uma secção sem detalhes não pode conter informação musical detalhada.',
                $excecao->getMessage(),
            );
        }

        $this->assertDatabaseCount(
            'metal_thursdays',
            0,
        );
    }

    /**
     * Confirma que uma secção detalhada pode deixar de estar associada a um
     * lançamento.
     *
     * @since 2.0.0
     */
    #[Test]
    public function atualizacao_remove_lancamento_associado_a_seccao(): void
    {
        $utilizador =
            Utilizador::factory()
                ->create();

        $this->actingAs(
            $utilizador,
            'sessao',
        );

        $edicao =
            $this->criarEdicao();

        $tipoSeccao =
            TipoSeccao::factory()
                ->comDetalhes()
                ->create();

        $artista =
            Artista::factory()
                ->create();

        $lancamento =
            Lancamento::factory()
                ->create();

        $servico =
            $this->servico();

        $metalThursday = $servico->criar([
            'edicao_id' => (int) $edicao->getKey(),

            'data' => '2026-01-08',

            'nome' => null,

            'autor_id' => (int) $utilizador->getKey(),

            'proximo_nomeado_id' => null,

            'seccoes' => [
                [
                    'id' => null,

                    'tipo_seccao_id' => (int) $tipoSeccao->getKey(),

                    'titulo' => 'Lançamento inicial',

                    'descricao' => 'Descrição inicial.',

                    'artista_id' => (int) $artista->getKey(),

                    'lancamento_id' => (int) $lancamento->getKey(),

                    'ligacoes' => [
                        [
                            'url' => 'https://example.com/inicial',
                            'etiqueta' => 'Ouvir',
                            'incorporar' => false,
                        ],
                    ],

                    'ano' => 2026,
                ],
            ],
        ]);

        $seccao =
            $metalThursday->seccoes->first();

        self::assertNotNull(
            $seccao,
        );

        $servico->atualizar(
            $metalThursday,
            [
                'edicao_id' => (int) $edicao->getKey(),

                'data' => '2026-01-08',

                'nome' => null,

                'autor_id' => (int) $utilizador->getKey(),

                'proximo_nomeado_id' => null,

                'seccoes' => [
                    [
                        'id' => (int) $seccao->getKey(),

                        'tipo_seccao_id' => (int) $tipoSeccao->getKey(),

                        'titulo' => 'Secção sem lançamento associado',

                        'descricao' => 'Descrição atualizada.',

                        'artista_id' => (int) $artista->getKey(),

                        'lancamento_id' => null,

                        'ligacoes' => [
                            [
                                'url' => 'https://example.com/sem-lancamento',
                                'etiqueta' => 'Ouvir',
                                'incorporar' => false,
                            ],
                        ],

                        'ano' => 2026,
                    ],
                ],
            ],
        );

        $this->assertDatabaseHas(
            'seccoes_metal_thursday',
            [
                'id' => $seccao->getKey(),

                'lancamento_id' => null,

                'titulo' => 'Secção sem lançamento associado',

                'artista_id' => $artista->getKey(),

                'deleted_at' => null,
            ],
        );

        $this->assertDatabaseMissing(
            'seccoes_metal_thursday',
            [
                'id' => $seccao->getKey(),

                'lancamento_id' => $lancamento->getKey(),
            ],
        );
    }

    /**
     * Confirma que uma secção existente pode trocar de lançamento.
     *
     * @since 2.0.0
     */
    #[Test]
    public function atualizacao_altera_lancamento_associado_a_seccao(): void
    {
        $utilizador =
            Utilizador::factory()
                ->create();

        $this->actingAs(
            $utilizador,
            'sessao',
        );

        $edicao =
            $this->criarEdicao();

        $tipoSeccao =
            TipoSeccao::factory()
                ->comDetalhes()
                ->create();

        $artista =
            Artista::factory()
                ->create();

        $lancamentoInicial =
            Lancamento::factory()
                ->create();

        $novoLancamento =
            Lancamento::factory()
                ->create();

        $servico =
            $this->servico();

        $metalThursday = $servico->criar([
            'edicao_id' => (int) $edicao->getKey(),

            'data' => '2026-01-08',

            'nome' => null,

            'autor_id' => (int) $utilizador->getKey(),

            'proximo_nomeado_id' => null,

            'seccoes' => [
                [
                    'id' => null,

                    'tipo_seccao_id' => (int) $tipoSeccao->getKey(),

                    'titulo' => 'Lançamento inicial',

                    'descricao' => 'Descrição inicial.',

                    'artista_id' => (int) $artista->getKey(),

                    'lancamento_id' => (int) $lancamentoInicial->getKey(),

                    'ligacoes' => [
                        [
                            'url' => 'https://example.com/inicial',
                            'etiqueta' => 'Ouvir',
                            'incorporar' => false,
                        ],
                    ],

                    'ano' => 2026,
                ],
            ],
        ]);

        $seccao =
            $metalThursday->seccoes->first();

        self::assertNotNull(
            $seccao,
        );

        $servico->atualizar(
            $metalThursday,
            [
                'edicao_id' => (int) $edicao->getKey(),

                'data' => '2026-01-08',

                'nome' => null,

                'autor_id' => (int) $utilizador->getKey(),

                'proximo_nomeado_id' => null,

                'seccoes' => [
                    [
                        'id' => (int) $seccao->getKey(),

                        'tipo_seccao_id' => (int) $tipoSeccao->getKey(),

                        'titulo' => 'Novo lançamento',

                        'descricao' => 'Descrição atualizada.',

                        'artista_id' => (int) $artista->getKey(),

                        'lancamento_id' => (int) $novoLancamento->getKey(),

                        'ligacoes' => [
                            [
                                'url' => 'https://example.com/novo',
                                'etiqueta' => 'Ouvir',
                                'incorporar' => false,
                            ],
                        ],

                        'ano' => 2026,
                    ],
                ],
            ],
        );

        $this->assertDatabaseHas(
            'seccoes_metal_thursday',
            [
                'id' => $seccao->getKey(),

                'lancamento_id' => $novoLancamento->getKey(),

                'titulo' => 'Novo lançamento',

                'deleted_at' => null,
            ],
        );

        $this->assertDatabaseMissing(
            'seccoes_metal_thursday',
            [
                'id' => $seccao->getKey(),

                'lancamento_id' => $lancamentoInicial->getKey(),
            ],
        );
    }

    /**
     * Confirma que uma secção existente pode preservar o lançamento que tenha
     * sido eliminado logicamente depois da sua associação.
     *
     * @since 2.0.0
     */
    #[Test]
    public function atualizacao_preserva_lancamento_eliminado_logicamente_na_mesma_seccao(): void
    {
        $utilizador =
            Utilizador::factory()
                ->create();

        $this->actingAs(
            $utilizador,
            'sessao',
        );

        $edicao =
            $this->criarEdicao();

        $tipoSeccao =
            TipoSeccao::factory()
                ->comDetalhes()
                ->create();

        $artista =
            Artista::factory()
                ->create();

        $lancamento =
            Lancamento::factory()
                ->create();

        $servico =
            $this->servico();

        $metalThursday = $servico->criar([
            'edicao_id' => (int) $edicao->getKey(),

            'data' => '2026-01-08',

            'nome' => null,

            'autor_id' => (int) $utilizador->getKey(),

            'proximo_nomeado_id' => null,

            'seccoes' => [
                [
                    'id' => null,

                    'tipo_seccao_id' => (int) $tipoSeccao->getKey(),

                    'titulo' => 'Lançamento histórico',

                    'descricao' => 'Descrição histórica.',

                    'artista_id' => (int) $artista->getKey(),

                    'lancamento_id' => (int) $lancamento->getKey(),

                    'ligacoes' => [
                        [
                            'url' => 'https://example.com/historico',
                            'etiqueta' => 'Ouvir',
                            'incorporar' => false,
                        ],
                    ],

                    'ano' => 2026,
                ],
            ],
        ]);

        $seccao =
            $metalThursday->seccoes->first();

        self::assertNotNull(
            $seccao,
        );

        $identificadorLancamento =
            (int) $lancamento->getKey();

        $lancamento->deleteOrFail();

        $servico->atualizar(
            $metalThursday,
            [
                'edicao_id' => (int) $edicao->getKey(),

                'data' => '2026-01-08',

                'nome' => null,

                'autor_id' => (int) $utilizador->getKey(),

                'proximo_nomeado_id' => null,

                'seccoes' => [
                    [
                        'id' => (int) $seccao->getKey(),

                        'tipo_seccao_id' => (int) $tipoSeccao->getKey(),

                        'titulo' => 'Lançamento histórico atualizado',

                        'descricao' => 'Descrição histórica atualizada.',

                        'artista_id' => (int) $artista->getKey(),

                        'lancamento_id' => $identificadorLancamento,

                        'ligacoes' => [
                            [
                                'url' => 'https://example.com/historico',
                                'etiqueta' => 'Ouvir',
                                'incorporar' => false,
                            ],
                        ],

                        'ano' => 2026,
                    ],
                ],
            ],
        );

        $this->assertDatabaseHas(
            'seccoes_metal_thursday',
            [
                'id' => $seccao->getKey(),

                'lancamento_id' => $identificadorLancamento,

                'titulo' => 'Lançamento histórico atualizado',

                'deleted_at' => null,
            ],
        );
    }

    /**
     * Confirma que um lançamento eliminado logicamente não pode ser transferido
     * para outra secção.
     *
     * @since 2.0.0
     */
    #[Test]
    public function atualizacao_rejeita_transferencia_de_lancamento_eliminado_para_outra_seccao(): void
    {
        $utilizador =
            Utilizador::factory()
                ->create();

        $this->actingAs(
            $utilizador,
            'sessao',
        );

        $edicao =
            $this->criarEdicao();

        $tipoSeccao =
            TipoSeccao::factory()
                ->comDetalhes()
                ->create();

        $artista =
            Artista::factory()
                ->create();

        $lancamentoHistorico =
            Lancamento::factory()
                ->create();

        $servico =
            $this->servico();

        $metalThursday = $servico->criar([
            'edicao_id' => (int) $edicao->getKey(),

            'data' => '2026-01-08',

            'nome' => null,

            'autor_id' => (int) $utilizador->getKey(),

            'proximo_nomeado_id' => null,

            'seccoes' => [
                [
                    'id' => null,

                    'tipo_seccao_id' => (int) $tipoSeccao->getKey(),

                    'titulo' => 'Secção histórica',

                    'descricao' => 'Descrição histórica.',

                    'artista_id' => (int) $artista->getKey(),

                    'lancamento_id' => (int) $lancamentoHistorico->getKey(),

                    'ligacoes' => [
                        [
                            'url' => 'https://example.com/historica',
                            'etiqueta' => 'Ouvir',
                            'incorporar' => false,
                        ],
                    ],

                    'ano' => 2026,
                ],
                [
                    'id' => null,

                    'tipo_seccao_id' => (int) $tipoSeccao->getKey(),

                    'titulo' => 'Outra secção',

                    'descricao' => 'Outra descrição.',

                    'artista_id' => (int) $artista->getKey(),

                    'lancamento_id' => null,

                    'ligacoes' => [
                        [
                            'url' => 'https://example.com/outra',
                            'etiqueta' => 'Ouvir',
                            'incorporar' => false,
                        ],
                    ],

                    'ano' => 2026,
                ],
            ],
        ]);

        $seccoes =
            $metalThursday
                ->seccoes
                ->values();

        $seccaoHistorica =
            $seccoes->get(0);

        $outraSeccao =
            $seccoes->get(1);

        self::assertNotNull(
            $seccaoHistorica,
        );

        self::assertNotNull(
            $outraSeccao,
        );

        $identificadorLancamento =
            (int) $lancamentoHistorico->getKey();

        $lancamentoHistorico->deleteOrFail();

        try {
            $servico->atualizar(
                $metalThursday,
                [
                    'edicao_id' => (int) $edicao->getKey(),

                    'data' => '2026-01-08',

                    'nome' => null,

                    'autor_id' => (int) $utilizador->getKey(),

                    'proximo_nomeado_id' => null,

                    'seccoes' => [
                        [
                            'id' => (int) $seccaoHistorica->getKey(),

                            'tipo_seccao_id' => (int) $tipoSeccao->getKey(),

                            'titulo' => 'Secção histórica atualizada',

                            'descricao' => 'Descrição histórica atualizada.',

                            'artista_id' => (int) $artista->getKey(),

                            'lancamento_id' => $identificadorLancamento,

                            'ligacoes' => [
                                [
                                    'url' => 'https://example.com/historica',
                                    'etiqueta' => 'Ouvir',
                                    'incorporar' => false,
                                ],
                            ],

                            'ano' => 2026,
                        ],
                        [
                            'id' => (int) $outraSeccao->getKey(),

                            'tipo_seccao_id' => (int) $tipoSeccao->getKey(),

                            'titulo' => 'Outra secção atualizada',

                            'descricao' => 'Outra descrição atualizada.',

                            'artista_id' => (int) $artista->getKey(),

                            'lancamento_id' => $identificadorLancamento,

                            'ligacoes' => [
                                [
                                    'url' => 'https://example.com/outra',
                                    'etiqueta' => 'Ouvir',
                                    'incorporar' => false,
                                ],
                            ],

                            'ano' => 2026,
                        ],
                    ],
                ],
            );

            self::fail(
                'Era esperada uma exceção ao transferir um lançamento eliminado para outra secção.',
            );
        } catch (InvalidArgumentException $excecao) {
            self::assertSame(
                'Foi indicado um lançamento inexistente ou indisponível.',
                $excecao->getMessage(),
            );
        }

        $this->assertDatabaseHas(
            'seccoes_metal_thursday',
            [
                'id' => $seccaoHistorica->getKey(),

                'lancamento_id' => $identificadorLancamento,

                'titulo' => 'Secção histórica',

                'deleted_at' => null,
            ],
        );

        $this->assertDatabaseHas(
            'seccoes_metal_thursday',
            [
                'id' => $outraSeccao->getKey(),

                'lancamento_id' => null,

                'titulo' => 'Outra secção',

                'deleted_at' => null,
            ],
        );
    }

    /**
     * Confirma que um lançamento eliminado logicamente não pode ser associado
     * a uma nova secção.
     *
     * @since 2.0.0
     */
    #[Test]
    public function criacao_rejeita_lancamento_eliminado_logicamente(): void
    {
        $utilizador =
            Utilizador::factory()
                ->create();

        $this->actingAs(
            $utilizador,
            'sessao',
        );

        $edicao =
            $this->criarEdicao();

        $tipoSeccao =
            TipoSeccao::factory()
                ->comDetalhes()
                ->create();

        $artista =
            Artista::factory()
                ->create();

        $lancamento =
            Lancamento::factory()
                ->create();

        $identificadorLancamento =
            (int) $lancamento->getKey();

        $lancamento->deleteOrFail();

        try {
            $this
                ->servico()
                ->criar([
                    'edicao_id' => (int) $edicao->getKey(),

                    'data' => '2026-01-08',

                    'nome' => null,

                    'autor_id' => (int) $utilizador->getKey(),

                    'proximo_nomeado_id' => null,

                    'seccoes' => [
                        [
                            'id' => null,

                            'tipo_seccao_id' => (int) $tipoSeccao->getKey(),

                            'titulo' => 'Lançamento eliminado',

                            'descricao' => 'Descrição válida.',

                            'artista_id' => (int) $artista->getKey(),

                            'lancamento_id' => $identificadorLancamento,

                            'ligacoes' => [
                                [
                                    'url' => 'https://example.com/lancamento',
                                    'etiqueta' => 'Ouvir',
                                    'incorporar' => false,
                                ],
                            ],

                            'ano' => 2026,
                        ],
                    ],
                ]);

            self::fail(
                'Era esperada uma exceção para um lançamento eliminado numa nova secção.',
            );
        } catch (InvalidArgumentException $excecao) {
            self::assertSame(
                'Foi indicado um lançamento inexistente ou indisponível.',
                $excecao->getMessage(),
            );
        }

        $this->assertDatabaseMissing(
            'seccoes_metal_thursday',
            [
                'lancamento_id' => $identificadorLancamento,

                'deleted_at' => null,
            ],
        );
    }

    /**
     * Cria a edição utilizada nos testes.
     *
     * @return Edicao Edição criada.
     *
     * @since 2.0.0
     */
    private function criarEdicao(): Edicao
    {
        return Edicao::factory()
            ->comNome(
                'Edição de janeiro',
            )
            ->comPeriodo(
                CarbonImmutable::parse(
                    '2026-01-01',
                ),
                CarbonImmutable::parse(
                    '2026-01-31',
                ),
            )
            ->create();
    }

    /**
     * Obtém os dados de uma secção sem detalhes.
     *
     * @param  TipoSeccao  $tipoSeccao  Tipo relacionado.
     * @param  string  $descricao  Descrição da secção.
     * @return array<string, mixed> Dados da secção.
     *
     * @since 2.0.0
     */
    private function dadosSeccaoSimples(
        TipoSeccao $tipoSeccao,
        string $descricao,
    ): array {
        return [
            'id' => null,

            'tipo_seccao_id' => (int) $tipoSeccao->getKey(),

            'titulo' => null,

            'descricao' => $descricao,

            'artista_id' => null,

            'ligacoes' => [],

            'ano' => null,
        ];
    }

    /**
     * Cria o serviço testado.
     *
     * @return ServicoPersistenciaMetalThursday Serviço criado.
     *
     * @since 2.0.0
     */
    private function servico(): ServicoPersistenciaMetalThursday
    {
        return new ServicoPersistenciaMetalThursday(
            new ServicoReservasMetalThursday,
        );
    }
}
