<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Interacoes;

use App\Enumeracoes\Interacoes\TipoEntidadeInteracao;
use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\MetalThursday;
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
 *
 * @version 1.0.0
 */
final class ControladorAudicaoTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Confirma que a contagem é obtida dos mesmos registos usados para
     * construir o indicador, sem uma consulta adicional.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
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
}
