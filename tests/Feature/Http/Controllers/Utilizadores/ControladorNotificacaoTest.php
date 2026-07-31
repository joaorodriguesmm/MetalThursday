<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Utilizadores;

use App\Models\Autenticacao\Utilizador;
use Carbon\CarbonImmutable;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa a leitura das notificações do utilizador autenticado.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
final class ControladorNotificacaoTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Confirma que uma notificação por ler é marcada através de uma única
     * atualização condicional.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function marca_notificacao_com_atualizacao_condicional_unica(): void
    {
        $utilizador = Utilizador::factory()
            ->create();

        $identificadorNotificacao =
            $this->criarNotificacao(
                $utilizador,
            );

        $consultas = [];

        DB::listen(
            static function (
                QueryExecuted $consulta,
            ) use (&$consultas): void {
                $sql =
                    mb_strtolower(
                        $consulta->sql,
                    );

                if (str_contains($sql, 'notificacoes')) {
                    $consultas[] =
                        $sql;
                }
            },
        );

        $this
            ->actingAs(
                $utilizador,
                'sessao',
            )
            ->post(
                route(
                    'notificacoes.marcar-como-lida',
                    [
                        'identificadorNotificacao' => $identificadorNotificacao,
                    ],
                ),
            )
            ->assertRedirect(
                route(
                    'notificacoes.indice',
                ),
            )
            ->assertSessionHas(
                'sucesso',
                'Notificação marcada como lida.',
            );

        self::assertCount(
            1,
            $consultas,
        );

        self::assertStringStartsWith(
            'update',
            ltrim(
                $consultas[0],
            ),
        );

        self::assertStringContainsString(
            'read_at',
            $consultas[0],
        );
    }

    /**
     * Confirma que uma segunda marcação não substitui a primeira data de
     * leitura.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function notificacao_lida_preserva_primeira_data_de_leitura(): void
    {
        $utilizador = Utilizador::factory()
            ->create();

        $dataLeitura =
            CarbonImmutable::create(
                2026,
                7,
                31,
                15,
                30,
            );

        $identificadorNotificacao =
            $this->criarNotificacao(
                $utilizador,
                $dataLeitura,
            );

        $this
            ->actingAs(
                $utilizador,
                'sessao',
            )
            ->post(
                route(
                    'notificacoes.marcar-como-lida',
                    [
                        'identificadorNotificacao' => $identificadorNotificacao,
                    ],
                ),
            )
            ->assertRedirect(
                route(
                    'notificacoes.indice',
                ),
            )
            ->assertSessionHas(
                'informacao',
                'A notificação já estava marcada como lida.',
            );

        $dataPersistida =
            DB::table(
                'notificacoes',
            )
                ->where(
                    'id',
                    $identificadorNotificacao,
                )
                ->value(
                    'read_at',
                );

        self::assertSame(
            $dataLeitura->format(
                'Y-m-d H:i:s',
            ),
            $dataPersistida,
        );
    }

    /**
     * Confirma que uma notificação pertencente a outro utilizador permanece
     * inacessível.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function nao_marca_notificacao_de_outro_utilizador(): void
    {
        $proprietario = Utilizador::factory()
            ->create();

        $outroUtilizador = Utilizador::factory()
            ->create();

        $identificadorNotificacao =
            $this->criarNotificacao(
                $proprietario,
            );

        $this
            ->actingAs(
                $outroUtilizador,
                'sessao',
            )
            ->post(
                route(
                    'notificacoes.marcar-como-lida',
                    [
                        'identificadorNotificacao' => $identificadorNotificacao,
                    ],
                ),
            )
            ->assertNotFound();

        $this->assertDatabaseHas(
            'notificacoes',
            [
                'id' => $identificadorNotificacao,

                'read_at' => null,
            ],
        );
    }

    /**
     * Cria uma notificação persistida com dados conhecidos.
     *
     * @param  Utilizador  $utilizador  Proprietário da notificação.
     * @param  CarbonImmutable|null  $dataLeitura  Data inicial de leitura.
     * @return string Identificador UUID da notificação.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function criarNotificacao(
        Utilizador $utilizador,
        ?CarbonImmutable $dataLeitura = null,
    ): string {
        $identificador =
            (string) Str::uuid();

        $agora =
            CarbonImmutable::create(
                2026,
                7,
                31,
                15,
                0,
            );

        DB::table(
            'notificacoes',
        )->insert([
            'id' => $identificador,

            'type' => 'NotificacaoTeste',

            'notifiable_type' => $utilizador->getMorphClass(),

            'notifiable_id' => $utilizador->getKey(),

            'data' => json_encode(
                [
                    'titulo' => 'Notificação de teste',

                    'mensagem' => 'Mensagem de teste.',
                ],
                JSON_THROW_ON_ERROR,
            ),

            'read_at' => $dataLeitura,

            'created_at' => $agora,

            'updated_at' => $agora,
        ]);

        return $identificador;
    }
}
