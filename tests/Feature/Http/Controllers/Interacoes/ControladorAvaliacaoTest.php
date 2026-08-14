<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Interacoes;

use App\Enumeracoes\Interacoes\TipoEntidadeInteracao;
use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\MetalThursday;
use App\Models\MetalThursday\SeccaoMetalThursday;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa a atualização e a apresentação das avaliações.
 *
 * @since 2.0.0
 */
final class ControladorAvaliacaoTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Confirma que a média, a contagem e o indicador são construídos sem uma
     * consulta agregada adicional.
     *
     * @since 2.0.0
     */
    #[Test]
    public function guarda_avaliacao_sem_consulta_agregada_redundante(): void
    {
        Notification::fake();

        $primeiroUtilizador = Utilizador::factory()
            ->create([
                'nome' => 'Primeiro avaliador',
            ]);

        $segundoUtilizador = Utilizador::factory()
            ->create([
                'nome' => 'Segundo avaliador',
            ]);

        $metalThursday = MetalThursday::factory()
            ->create();

        $metalThursday
            ->avaliacoes()
            ->create([
                'utilizador_id' => $primeiroUtilizador->getKey(),

                'pontuacao' => 6.5,
            ]);

        $consultas = [];

        DB::listen(
            static function (
                QueryExecuted $consulta,
            ) use (&$consultas): void {
                $consultas[] =
                    mb_strtolower(
                        $consulta->sql,
                    );
            },
        );

        $this
            ->actingAs(
                $segundoUtilizador,
                'sessao',
            )
            ->postJson(
                route(
                    'avaliacoes.guardar',
                    [
                        'tipoAvaliavel' => TipoEntidadeInteracao::MetalThursday->value,

                        'identificadorAvaliavel' => $metalThursday->getKey(),
                    ],
                ),
                [
                    'pontuacao' => '8,5',
                ],
            )
            ->assertOk()
            ->assertJsonPath(
                'media_avaliacoes',
                7.5,
            )
            ->assertJsonPath(
                'numero_avaliacoes',
                2,
            )
            ->assertJsonPath(
                'pontuacao_utilizador',
                8.5,
            )
            ->assertJsonPath(
                'conteudo_indicador_html',
                'Segundo avaliador: 8,5<br>Primeiro avaliador: 6,5',
            );

        self::assertSame(
            [],
            array_values(
                array_filter(
                    $consultas,
                    static fn (
                        string $consulta,
                    ): bool => str_contains(
                        $consulta,
                        'avaliacoes',
                    )
                        && (
                            str_contains(
                                $consulta,
                                'count(*)',
                            )
                            || str_contains(
                                $consulta,
                                'avg(',
                            )
                        ),
                ),
            ),
        );

        $this->assertDatabaseHas(
            'avaliacoes',
            [
                'utilizador_id' => $segundoUtilizador->getKey(),

                'tipo_avaliavel' => $metalThursday->getMorphClass(),

                'avaliavel_id' => $metalThursday->getKey(),

                'pontuacao' => 8.5,
            ],
        );
    }

    /**
     * Confirma a atualização de uma avaliação já existente numa secção.
     *
     * @since 2.0.0
     */
    #[Test]
    public function atualiza_avaliacao_existente_de_uma_seccao(): void
    {
        Notification::fake();

        $utilizador = Utilizador::factory()
            ->create([
                'nome' => 'Avaliador',
            ]);

        $seccao = SeccaoMetalThursday::factory()
            ->create();

        $seccao
            ->avaliacoes()
            ->create([
                'utilizador_id' => $utilizador->getKey(),

                'pontuacao' => 4.0,
            ]);

        $this
            ->actingAs(
                $utilizador,
                'sessao',
            )
            ->postJson(
                route(
                    'avaliacoes.guardar',
                    [
                        'tipoAvaliavel' => TipoEntidadeInteracao::SeccaoMetalThursday->value,

                        'identificadorAvaliavel' => $seccao->getKey(),
                    ],
                ),
                [
                    'pontuacao' => '9,5',
                ],
            )
            ->assertOk()
            ->assertJsonPath(
                'media_avaliacoes',
                9.5,
            )
            ->assertJsonPath(
                'numero_avaliacoes',
                1,
            )
            ->assertJsonPath(
                'pontuacao_utilizador',
                9.5,
            )
            ->assertJsonPath(
                'conteudo_indicador_html',
                'Avaliador: 9,5',
            );

        $this->assertDatabaseCount(
            'avaliacoes',
            1,
        );

        $this->assertDatabaseHas(
            'avaliacoes',
            [
                'utilizador_id' => $utilizador->getKey(),

                'tipo_avaliavel' => $seccao->getMorphClass(),

                'avaliavel_id' => $seccao->getKey(),

                'pontuacao' => 9.5,
            ],
        );
    }

    /**
     * Confirma que reenviar a mesma pontuação não gera nova interação.
     *
     * @since 2.0.0
     */
    #[Test]
    public function mantem_avaliacao_quando_a_pontuacao_nao_muda(): void
    {
        $utilizador = Utilizador::factory()
            ->create([
                'nome' => 'Avaliador',
            ]);

        Utilizador::factory()
            ->create();

        $metalThursday = MetalThursday::factory()
            ->create();

        $metalThursday
            ->avaliacoes()
            ->create([
                'utilizador_id' => $utilizador->getKey(),

                'pontuacao' => 7.5,
            ]);

        Notification::fake();

        $this
            ->actingAs(
                $utilizador,
                'sessao',
            )
            ->postJson(
                route(
                    'avaliacoes.guardar',
                    [
                        'tipoAvaliavel' => TipoEntidadeInteracao::MetalThursday->value,

                        'identificadorAvaliavel' => $metalThursday->getKey(),
                    ],
                ),
                [
                    'pontuacao' => '7,5',
                ],
            )
            ->assertOk()
            ->assertJsonPath(
                'media_avaliacoes',
                7.5,
            )
            ->assertJsonPath(
                'numero_avaliacoes',
                1,
            )
            ->assertJsonPath(
                'pontuacao_utilizador',
                7.5,
            )
            ->assertJsonPath(
                'conteudo_indicador_html',
                'Avaliador: 7,5',
            );

        $this->assertDatabaseCount(
            'avaliacoes',
            1,
        );

        Notification::assertNothingSent();
    }
}
