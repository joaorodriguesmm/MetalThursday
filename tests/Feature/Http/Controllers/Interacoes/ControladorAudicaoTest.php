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
 * Testa a alternância e a apresentação das audições.
 *
 * @since 2.0.0
 */
final class ControladorAudicaoTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Confirma que a contagem é obtida dos mesmos registos usados para
     * construir o indicador, sem uma consulta adicional.
     *
     * @since 2.0.0
     */
    #[Test]
    public function adiciona_audicao_sem_consulta_de_contagem_redundante(): void
    {
        Notification::fake();

        $primeiroUtilizador = Utilizador::factory()
            ->create([
                'nome' => 'Ana',
            ]);

        $segundoUtilizador = Utilizador::factory()
            ->create([
                'nome' => 'Bruno',
            ]);

        $metalThursday = MetalThursday::factory()
            ->create();

        $metalThursday
            ->audicoes()
            ->create([
                'utilizador_id' => $primeiroUtilizador->getKey(),
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
                    'audicoes.alternar',
                    [
                        'tipoAudivel' => TipoEntidadeInteracao::MetalThursday->value,

                        'identificadorAudivel' => $metalThursday->getKey(),
                    ],
                ),
            )
            ->assertOk()
            ->assertJsonPath(
                'marcado_como_ouvido',
                true,
            )
            ->assertJsonPath(
                'numero_audicoes',
                2,
            )
            ->assertJsonPath(
                'conteudo_indicador_html',
                'Ana<br>Bruno',
            )
            ->assertJsonPath(
                'mensagem',
                'MetalThursday marcada como ouvida.',
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
                        'audicoes',
                    )
                        && str_contains(
                            $consulta,
                            'count(*)',
                        ),
                ),
            ),
        );

        $this->assertDatabaseHas(
            'audicoes',
            [
                'utilizador_id' => $segundoUtilizador->getKey(),

                'tipo_audivel' => $metalThursday->getMorphClass(),

                'audivel_id' => $metalThursday->getKey(),
            ],
        );
    }

    /**
     * Confirma a mensagem devolvida ao marcar uma secção como ouvida.
     *
     * @since 2.0.0
     */
    #[Test]
    public function adiciona_audicao_a_uma_seccao_com_mensagem_de_sucesso(): void
    {
        Notification::fake();

        $utilizador = Utilizador::factory()
            ->create();

        $seccao = SeccaoMetalThursday::factory()
            ->create();

        $this
            ->actingAs(
                $utilizador,
                'sessao',
            )
            ->postJson(
                route(
                    'audicoes.alternar',
                    [
                        'tipoAudivel' => TipoEntidadeInteracao::SeccaoMetalThursday->value,

                        'identificadorAudivel' => $seccao->getKey(),
                    ],
                ),
            )
            ->assertOk()
            ->assertJsonPath(
                'marcado_como_ouvido',
                true,
            )
            ->assertJsonPath(
                'mensagem',
                'Secção marcada como ouvida.',
            );

        $this->assertDatabaseHas(
            'audicoes',
            [
                'utilizador_id' => $utilizador->getKey(),

                'tipo_audivel' => $seccao->getMorphClass(),

                'audivel_id' => $seccao->getKey(),
            ],
        );
    }

    /**
     * Confirma a mensagem devolvida ao remover a audição de uma
     * MetalThursday.
     *
     * @since 2.0.0
     */
    #[Test]
    public function remove_audicao_de_uma_metal_thursday_com_mensagem_de_sucesso(): void
    {
        Notification::fake();

        $utilizador = Utilizador::factory()
            ->create();

        $metalThursday = MetalThursday::factory()
            ->create();

        $metalThursday
            ->audicoes()
            ->create([
                'utilizador_id' => $utilizador->getKey(),
            ]);

        $this
            ->actingAs(
                $utilizador,
                'sessao',
            )
            ->postJson(
                route(
                    'audicoes.alternar',
                    [
                        'tipoAudivel' => TipoEntidadeInteracao::MetalThursday->value,

                        'identificadorAudivel' => $metalThursday->getKey(),
                    ],
                ),
            )
            ->assertOk()
            ->assertJsonPath(
                'marcado_como_ouvido',
                false,
            )
            ->assertJsonPath(
                'mensagem',
                'MetalThursday marcada como não ouvida.',
            );

        $this->assertDatabaseMissing(
            'audicoes',
            [
                'utilizador_id' => $utilizador->getKey(),

                'tipo_audivel' => $metalThursday->getMorphClass(),

                'audivel_id' => $metalThursday->getKey(),
            ],
        );
    }

    /**
     * Confirma a remoção da audição de uma secção.
     *
     * Quando é removida a última audição, o indicador deve regressar ao estado
     * vazio apresentado pela interface.
     *
     * @since 2.0.0
     */
    #[Test]
    public function remove_audicao_de_uma_seccao(): void
    {
        Notification::fake();

        $utilizador = Utilizador::factory()
            ->create();

        $seccao = SeccaoMetalThursday::factory()
            ->create();

        $seccao
            ->audicoes()
            ->create([
                'utilizador_id' => $utilizador->getKey(),
            ]);

        $this
            ->actingAs(
                $utilizador,
                'sessao',
            )
            ->postJson(
                route(
                    'audicoes.alternar',
                    [
                        'tipoAudivel' => TipoEntidadeInteracao::SeccaoMetalThursday->value,

                        'identificadorAudivel' => $seccao->getKey(),
                    ],
                ),
            )
            ->assertOk()
            ->assertJsonPath(
                'marcado_como_ouvido',
                false,
            )
            ->assertJsonPath(
                'numero_audicoes',
                0,
            )
            ->assertJsonPath(
                'conteudo_indicador_html',
                'Ninguém marcou como ouvido.',
            )
            ->assertJsonPath(
                'mensagem',
                'Secção marcada como não ouvida.',
            );

        $this->assertDatabaseMissing(
            'audicoes',
            [
                'utilizador_id' => $utilizador->getKey(),

                'tipo_audivel' => $seccao->getMorphClass(),

                'audivel_id' => $seccao->getKey(),
            ],
        );
    }
}
