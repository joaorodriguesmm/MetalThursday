<?php

declare(strict_types=1);

namespace Tests\Feature\Servicos\Notificacoes;

use App\Enumeracoes\IdentificadorPermissaoEmail;
use App\Models\Autenticacao\Utilizador;
use App\Models\Comunicacao\PermissaoEmail;
use App\Models\MetalThursday\MetalThursday;
use App\Notifications\NotificacaoInteracaoUtilizador;
use App\Servicos\Notificacoes\NotificadorInteracoes;
use Database\Seeders\PermissaoEmailSeeder;
use Illuminate\Contracts\Notifications\Dispatcher as DespachanteNotificacoes;
use Illuminate\Database\Eloquent\Collection as ColecaoEloquent;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa o carregamento dos destinatários das notificações de interações.
 *
 * @since 2.0.0
 */
final class NotificadorInteracoesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Confirma que as permissões são carregadas por lote e que a determinação
     * dos canais não executa consultas adicionais por destinatário.
     *
     * @since 2.0.0
     */
    #[Test]
    public function carrega_permissoes_sem_consultas_por_destinatario(): void
    {
        app(
            PermissaoEmailSeeder::class,
        )->run();

        $causador = Utilizador::factory()
            ->create();

        $destinatarioComEmail = Utilizador::factory()
            ->create();

        $destinatarioSemEmail = Utilizador::factory()
            ->create();

        $permissaoTodas = PermissaoEmail::query()
            ->where(
                'identificador',
                IdentificadorPermissaoEmail::TodasNotificacoes->value,
            )
            ->sole();

        $destinatarioComEmail
            ->permissoesEmail()
            ->attach(
                $permissaoTodas->getKey(),
            );

        $metalThursday = MetalThursday::factory()
            ->comAutor($destinatarioSemEmail)
            ->comProximoNomeado($causador)
            ->create();

        $comentario = $metalThursday->comentarios()->create([
            'utilizador_id' => $destinatarioComEmail->getKey(),
            'conteudo' => 'Comentário a responder.',
            'comentario_pai_id' => null,
        ]);

        $utilizadorSemRelacao = Utilizador::factory()->create();

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

        $despachante =
            Mockery::mock(
                DespachanteNotificacoes::class,
            );

        $despachante
            ->shouldReceive(
                'send',
            )
            ->once()
            ->andReturnUsing(
                function (
                    mixed $destinatarios,
                    mixed $notificacao,
                ) use (
                    &$consultas,
                    $destinatarioComEmail,
                    $destinatarioSemEmail,
                    $utilizadorSemRelacao,
                ): void {
                    self::assertInstanceOf(
                        ColecaoEloquent::class,
                        $destinatarios,
                    );

                    self::assertInstanceOf(
                        NotificacaoInteracaoUtilizador::class,
                        $notificacao,
                    );

                    self::assertCount(1, $destinatarios);

                    self::assertFalse(
                        $destinatarios->contains(
                            'id',
                            $utilizadorSemRelacao->getKey(),
                        ),
                    );

                    self::assertFalse(
                        $destinatarios->contains(
                            'id',
                            $destinatarioSemEmail->getKey(),
                        ),
                    );

                    foreach ($destinatarios as $destinatario) {
                        self::assertInstanceOf(
                            Utilizador::class,
                            $destinatario,
                        );

                        self::assertTrue(
                            $destinatario->relationLoaded(
                                'permissoesEmail',
                            ),
                        );
                    }

                    $numeroConsultasAntesDosCanais =
                        count(
                            $consultas,
                        );

                    $destinatariosPorIdentificador =
                        $destinatarios->keyBy(
                            static fn (
                                Utilizador $utilizador,
                            ): int => (int) $utilizador->getKey(),
                        );

                    $utilizadorComEmail =
                        $destinatariosPorIdentificador->get(
                            $destinatarioComEmail->getKey(),
                        );

                    self::assertInstanceOf(
                        Utilizador::class,
                        $utilizadorComEmail,
                    );

                    self::assertSame(
                        [
                            'database',
                            'mail',
                        ],
                        $notificacao->via(
                            $utilizadorComEmail,
                        ),
                    );

                    self::assertCount(
                        $numeroConsultasAntesDosCanais,
                        $consultas,
                    );
                },
            );

        $servico =
            new NotificadorInteracoes(
                $despachante,
            );

        $servico->notificarOutrosUtilizadores(
            sujeito: $comentario,
            causador: $causador,
            acao: 'respondeu',
        );

        $consultasPermissoes =
            array_values(
                array_filter(
                    $consultas,
                    static fn (
                        string $consulta,
                    ): bool => str_contains(
                        $consulta,
                        'permissoes_email',
                    ),
                ),
            );

        self::assertCount(
            1,
            $consultasPermissoes,
        );
    }

    /**
     * Uma interação do autor na sua publicação não gera avisos globais.
     */
    #[Test]
    public function nao_notifica_atividade_sem_destinatarios_pessoais(): void
    {
        $autor = Utilizador::factory()->create();
        Utilizador::factory()->count(3)->create();

        $metalThursday = MetalThursday::factory()
            ->comAutor($autor)
            ->create();

        $despachante = Mockery::mock(DespachanteNotificacoes::class);
        $despachante->shouldNotReceive('send');

        (new NotificadorInteracoes($despachante))->notificarOutrosUtilizadores(
            sujeito: $metalThursday,
            causador: $autor,
            acao: 'ouviu',
        );
    }

    /**
     * Os utilizadores alheios à publicação não recebem avisos.
     */
    #[Test]
    public function notifica_apenas_autor_da_publicacao(): void
    {
        $autor = Utilizador::factory()->create();
        $causador = Utilizador::factory()->create();
        $alheio = Utilizador::factory()->create();

        $metalThursday = MetalThursday::factory()
            ->comAutor($autor)
            ->create();

        $despachante = Mockery::mock(DespachanteNotificacoes::class);
        $despachante->shouldReceive('send')
            ->once()
            ->withArgs(static function ($destinatarios) use ($autor, $alheio): bool {
                return $destinatarios instanceof ColecaoEloquent
                    && $destinatarios->count() === 1
                    && $destinatarios->first()?->getKey() === $autor->getKey()
                    && ! $destinatarios->contains('id', $alheio->getKey());
            });

        (new NotificadorInteracoes($despachante))->notificarOutrosUtilizadores(
            sujeito: $metalThursday,
            causador: $causador,
            acao: 'avaliou',
        );
    }
}
