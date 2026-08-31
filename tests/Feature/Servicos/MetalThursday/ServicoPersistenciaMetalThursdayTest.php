<?php

declare(strict_types=1);

namespace Tests\Feature\Servicos\MetalThursday;

use App\Enumeracoes\TipoIncorporacao;
use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\Edicao;
use App\Models\MetalThursday\ReservaMetalThursday;
use App\Models\MetalThursday\TipoSeccao;
use App\Models\Musica\Artista;
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

                        'ligacao' => 'https://example.com/faixa',

                        'tipo_incorporacao' => TipoIncorporacao::Ligacao->value,

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

                'tipo_incorporacao' => TipoIncorporacao::Ligacao->value,

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

            'ligacao' => null,

            'tipo_incorporacao' => null,

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
