<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\MetalThursday;

use App\Enumeracoes\PapelUtilizador;
use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\Edicao;
use App\Models\MetalThursday\MetalThursday;
use App\Models\MetalThursday\ReservaMetalThursday;
use App\Models\MetalThursday\SeccaoMetalThursday;
use App\Models\MetalThursday\TipoSeccao;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa a aplicação da elegibilidade comum às nomeações de MetalThursday.
 *
 * @since 2.0.0
 */
final class ElegibilidadeNomeacaoMetalThursdayTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Prepara os testes das vistas sem depender dos ficheiros do Vite.
     *
     * @since 2.0.0
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    /**
     * Confirma que disponibilidade para nomeação não condiciona a seleção
     * como autor.
     *
     * @since 2.0.0
     */
    #[Test]
    public function separa_autores_de_utilizadores_elegiveis_para_nomeacao(): void
    {
        $utilizadorAutenticado = Utilizador::factory()
            ->create([
                'nome' => 'Utilizador Autenticado',
            ]);

        $elegivel = Utilizador::factory()
            ->create([
                'nome' => 'Utilizador Elegível',
            ]);

        $indisponivel = Utilizador::factory()
            ->indisponivelParaNomeacao()
            ->create([
                'nome' => 'Utilizador Indisponível',
            ]);

        $comReservaPendente = Utilizador::factory()
            ->create([
                'nome' => 'Utilizador com Reserva',
            ]);

        ReservaMetalThursday::factory()
            ->comData(
                CarbonImmutable::parse(
                    '2026-08-27',
                ),
            )
            ->comResponsavel(
                $comReservaPendente,
            )
            ->create();

        $resposta = $this
            ->actingAs(
                $utilizadorAutenticado,
                'sessao',
            )
            ->get(
                route(
                    'metal-thursday.criar',
                ),
            );

        $resposta
            ->assertOk()
            ->assertViewHas(
                'utilizadoresAutores',
                static fn (
                    mixed $valor,
                ): bool => (
                    $valor instanceof Collection
                    && $valor->contains(
                        'id',
                        $elegivel->getKey(),
                    )
                    && $valor->contains(
                        'id',
                        $indisponivel->getKey(),
                    )
                    && $valor->contains(
                        'id',
                        $comReservaPendente->getKey(),
                    )
                ),
            )
            ->assertViewHas(
                'utilizadoresElegiveisNomeacao',
                static fn (
                    mixed $valor,
                ): bool => (
                    $valor instanceof Collection
                    && $valor->contains(
                        'id',
                        $elegivel->getKey(),
                    )
                    && ! $valor->contains(
                        'id',
                        $indisponivel->getKey(),
                    )
                    && ! $valor->contains(
                        'id',
                        $comReservaPendente->getKey(),
                    )
                ),
            );
    }

    /**
     * Confirma que a sugestão por antiguidade ignora todos os utilizadores
     * atualmente inelegíveis.
     *
     * @since 2.0.0
     */
    #[Test]
    public function sugestao_mais_antiga_utiliza_elegibilidade_comum(): void
    {
        $utilizadorAutenticado = Utilizador::factory()
            ->create([
                'nome' => 'Zelda',
            ]);

        Utilizador::factory()
            ->indisponivelParaNomeacao()
            ->create([
                'nome' => 'Ana',
            ]);

        $comReservaPendente = Utilizador::factory()
            ->create([
                'nome' => 'Beatriz',
            ]);

        $elegivel = Utilizador::factory()
            ->create([
                'nome' => 'Carlos',
            ]);

        ReservaMetalThursday::factory()
            ->comData(
                CarbonImmutable::parse(
                    '2026-08-27',
                ),
            )
            ->comResponsavel(
                $comReservaPendente,
            )
            ->create();

        $this
            ->actingAs(
                $utilizadorAutenticado,
                'sessao',
            )
            ->getJson(
                route(
                    'utilizadores.ha-mais-tempo-sem-nomeacao',
                    [
                        'excluir_utilizador_id' => $utilizadorAutenticado->getKey(),
                    ],
                ),
            )
            ->assertOk()
            ->assertJsonPath(
                'identificador',
                $elegivel->getKey(),
            );
    }

    /**
     * Confirma que um pedido manipulado não consegue nomear um utilizador
     * indisponível ou com outra reserva pendente.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_nomeados_inelegiveis_em_pedidos_manipulados(): void
    {
        $administrador = Utilizador::factory()
            ->comPapel(
                PapelUtilizador::Administrador,
            )
            ->create();

        $indisponivel = Utilizador::factory()
            ->indisponivelParaNomeacao()
            ->create();

        $comReservaPendente = Utilizador::factory()
            ->create();

        ReservaMetalThursday::factory()
            ->comData(
                CarbonImmutable::parse(
                    '2026-09-03',
                ),
            )
            ->comResponsavel(
                $comReservaPendente,
            )
            ->create();

        $data = CarbonImmutable::parse(
            '2026-08-27',
        );

        $edicao = Edicao::factory()
            ->comPeriodo(
                $data->startOfMonth(),
                $data->endOfMonth(),
            )
            ->create();

        $tipoSeccao = TipoSeccao::factory()
            ->semDetalhes()
            ->create();

        $this->actingAs(
            $administrador,
            'sessao',
        );

        foreach (
            [
                $indisponivel,
                $comReservaPendente,
            ] as $nomeado
        ) {
            $this
                ->postJson(
                    route(
                        'metal-thursday.guardar',
                    ),
                    [
                        'edicao_id' => $edicao->getKey(),

                        'data' => $data->toDateString(),

                        'nome' => null,

                        'autor_id' => $administrador->getKey(),

                        'proximo_nomeado_id' => $nomeado->getKey(),

                        'seccoes' => [
                            [
                                'id' => null,

                                'tipo_seccao_id' => $tipoSeccao->getKey(),

                                'descricao' => 'Secção de teste.',
                            ],
                        ],
                    ],
                )
                ->assertUnprocessable()
                ->assertJsonValidationErrors([
                    'proximo_nomeado_id',
                ])
                ->assertJsonPath(
                    'errors.proximo_nomeado_id.0',
                    'O próximo nomeado selecionado não existe ou não está disponível.',
                );
        }

        $this->assertDatabaseMissing(
            'metal_thursdays',
            [
                'data' => $data->toDateString(),
            ],
        );
    }

    /**
     * Confirma que uma nomeação anteriormente válida pode ser conservada
     * depois de o utilizador ficar indisponível.
     *
     * @since 2.0.0
     */
    #[Test]
    public function permite_conservar_nomeado_atual_que_ficou_indisponivel(): void
    {
        $administrador = Utilizador::factory()
            ->comPapel(
                PapelUtilizador::Administrador,
            )
            ->create();

        $nomeado = Utilizador::factory()
            ->indisponivelParaNomeacao()
            ->create();

        $data = CarbonImmutable::parse(
            '2026-08-27',
        );

        $edicao = Edicao::factory()
            ->comPeriodo(
                $data->startOfMonth(),
                $data->endOfMonth(),
            )
            ->create();

        $metalThursday = MetalThursday::factory()
            ->comData(
                $data,
            )
            ->comEdicao(
                $edicao,
            )
            ->comAutor(
                $administrador,
            )
            ->comProximoNomeado(
                $nomeado,
            )
            ->create();

        $tipoSeccao = TipoSeccao::factory()
            ->semDetalhes()
            ->create();

        $seccao = SeccaoMetalThursday::factory()
            ->paraMetalThursday(
                $metalThursday,
            )
            ->doTipo(
                $tipoSeccao,
            )
            ->comConteudo(
                'Secção existente.',
            )
            ->create();

        $this
            ->actingAs(
                $administrador,
                'sessao',
            )
            ->get(
                route(
                    'metal-thursday.editar',
                    $metalThursday,
                ),
            )
            ->assertOk()
            ->assertViewHas(
                'utilizadoresElegiveisNomeacao',
                static fn (
                    mixed $valor,
                ): bool => (
                    $valor instanceof Collection
                    && $valor->contains(
                        'id',
                        $nomeado->getKey(),
                    )
                ),
            );

        $this
            ->patchJson(
                route(
                    'metal-thursday.atualizar',
                    $metalThursday,
                ),
                [
                    'edicao_id' => $edicao->getKey(),

                    'data' => $data->toDateString(),

                    'nome' => null,

                    'autor_id' => $administrador->getKey(),

                    'proximo_nomeado_id' => $nomeado->getKey(),

                    'seccoes' => [
                        [
                            'id' => $seccao->getKey(),

                            'tipo_seccao_id' => $tipoSeccao->getKey(),

                            'descricao' => 'Secção existente.',
                        ],
                    ],
                ],
            )
            ->assertOk();

        $this->assertDatabaseHas(
            'metal_thursdays',
            [
                'id' => $metalThursday->getKey(),

                'proximo_nomeado_id' => $nomeado->getKey(),
            ],
        );
    }
}
