<?php

declare(strict_types=1);

namespace Tests\Feature\Notifications;

use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\Edicao;
use App\Models\MetalThursday\MetalThursday;
use App\Notifications\NotificacaoMetalThursdayCriada;
use App\Notifications\NotificacaoUtilizadorNomeado;
use Carbon\CarbonImmutable;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa os retratos escalares utilizados pelas notificações de
 * MetalThursday.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
final class NotificacoesMetalThursdayTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Confirma que a notificação de publicação conserva os valores do momento
     * da criação e não consulta a base de dados depois de ser serializada.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function publicacao_preserva_retrato_sem_consultas_posteriores(): void
    {
        $criador = Utilizador::factory()
            ->create([
                'nome' => 'Criador original',
            ]);

        $autor = Utilizador::factory()
            ->create([
                'nome' => 'Autor original',
            ]);

        $nomeado = Utilizador::factory()
            ->create();

        $this->actingAs(
            $criador,
            'sessao',
        );

        $metalThursday = MetalThursday::factory()
            ->comNome(
                'Publicação original',
            )
            ->comAutor(
                $autor,
            )
            ->comProximoNomeado(
                $nomeado,
            )
            ->create();

        $identificadorMetalThursday =
            (int) $metalThursday->getKey();

        $notificacao = unserialize(
            serialize(
                new NotificacaoMetalThursdayCriada(
                    $metalThursday,
                ),
            ),
            [
                'allowed_classes' => true,
            ],
        );

        self::assertInstanceOf(
            NotificacaoMetalThursdayCriada::class,
            $notificacao,
        );

        $metalThursday->updateOrFail([
            'nome' => 'Publicação alterada',
        ]);

        $autor->updateOrFail([
            'nome' => 'Autor alterado',
        ]);

        $criador->updateOrFail([
            'nome' => 'Criador alterado',
        ]);

        $destinatario = Utilizador::factory()
            ->create();

        $consultas = [];

        DB::listen(
            static function (
                QueryExecuted $consulta,
            ) use (&$consultas): void {
                $consultas[] = $consulta->sql;
            },
        );

        $dados = $notificacao->toArray(
            $destinatario,
        );

        $notificacao->toMail(
            $destinatario,
        );

        self::assertSame(
            $identificadorMetalThursday,
            $dados['identificador_metal_thursday'],
        );

        self::assertSame(
            'Uma nova MetalThursday da autoria de Autor original foi publicada por Criador original: Publicação original',
            $dados['mensagem'],
        );

        self::assertSame(
            route(
                'metal-thursday.detalhes',
                [
                    'metalThursday' => $identificadorMetalThursday,
                ],
            ),
            $dados['ligacao'],
        );

        self::assertSame(
            [],
            $consultas,
        );
    }

    /**
     * Confirma que a notificação de nomeação conserva o autor e o prazo do
     * momento da criação sem consultas posteriores.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function nomeacao_preserva_retrato_sem_consultas_posteriores(): void
    {
        $autor = Utilizador::factory()
            ->create([
                'nome' => 'Autor da nomeação',
            ]);

        $nomeado = Utilizador::factory()
            ->create();

        $data = CarbonImmutable::create(
            2026,
            7,
            30,
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
                $autor,
            )
            ->comProximoNomeado(
                $nomeado,
            )
            ->create();

        $identificadorMetalThursday =
            (int) $metalThursday->getKey();

        $notificacao = unserialize(
            serialize(
                new NotificacaoUtilizadorNomeado(
                    $metalThursday,
                ),
            ),
            [
                'allowed_classes' => true,
            ],
        );

        self::assertInstanceOf(
            NotificacaoUtilizadorNomeado::class,
            $notificacao,
        );

        $autor->updateOrFail([
            'nome' => 'Autor alterado',
        ]);

        $metalThursday->updateOrFail([
            'data' => CarbonImmutable::create(
                2026,
                8,
                6,
            ),
        ]);

        $consultas = [];

        DB::listen(
            static function (
                QueryExecuted $consulta,
            ) use (&$consultas): void {
                $consultas[] = $consulta->sql;
            },
        );

        $dados = $notificacao->toArray(
            $nomeado,
        );

        $notificacao->toMail(
            $nomeado,
        );

        self::assertSame(
            $identificadorMetalThursday,
            $dados['identificador_metal_thursday'],
        );

        self::assertSame(
            'Foste nomeado por Autor da nomeação! Prepara e publica a tua MetalThursday até quinta-feira, dia 06/08/2026.',
            $dados['mensagem'],
        );

        self::assertSame(
            [],
            $consultas,
        );
    }
}
