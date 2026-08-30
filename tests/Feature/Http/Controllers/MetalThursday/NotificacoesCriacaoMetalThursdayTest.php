<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\MetalThursday;

use App\Enumeracoes\PapelUtilizador;
use App\Http\Controllers\MetalThursday\ControladorMetalThursday;
use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\MetalThursday;
use App\Models\MetalThursday\ReservaMetalThursday;
use App\Notifications\NotificacaoAplicacao;
use App\Notifications\NotificacaoMetalThursdayCriada;
use App\Notifications\NotificacaoUtilizadorNomeado;
use Carbon\CarbonImmutable;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Testa o carregamento dos destinatários das notificações após a criação.
 *
 * @since 2.0.0
 */
final class NotificacoesCriacaoMetalThursdayTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Prepara cada teste com uma referência temporal determinística.
     *
     * @since 2.0.0
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(
            CarbonImmutable::parse(
                '2026-08-27 12:00:00',
                'Europe/Lisbon',
            ),
        );
    }

    /**
     * Confirma que as permissões são carregadas por conjunto e que a
     * determinação dos canais não executa consultas por destinatário.
     *
     * A nomeação da reserva criada e a notificação geral da publicação são
     * responsabilidades distintas. Cada fluxo carrega antecipadamente as
     * permissões necessárias, independentemente da quantidade de destinatários.
     *
     * @since 2.0.0
     */
    #[Test]
    public function carrega_permissoes_sem_consultas_por_destinatario(): void
    {
        Notification::fake();

        $criador =
            $this->criarUtilizadorSelecionavel();

        $nomeadoEfetivo =
            $this->criarUtilizadorSelecionavel();

        $primeiroDestinatario =
            $this->criarUtilizadorSelecionavel();

        $segundoDestinatario =
            $this->criarUtilizadorSelecionavel();

        $metalThursday = MetalThursday::factory()
            ->comData(
                CarbonImmutable::parse(
                    '2026-08-27',
                ),
            )
            ->comAutor(
                $criador,
            )
            ->comProximoNomeado(
                $nomeadoEfetivo,
            )
            ->create([
                'criado_por_id' => $criador->getKey(),
            ]);

        $reservaSeguinte = ReservaMetalThursday::factory()
            ->comData(
                CarbonImmutable::parse(
                    '2026-09-03',
                ),
            )
            ->comResponsavel(
                $nomeadoEfetivo,
            )
            ->create();

        $consultas = [];

        DB::listen(
            static function (
                QueryExecuted $consulta,
            ) use (
                &$consultas,
            ): void {
                $consultas[] =
                    mb_strtolower(
                        $consulta->sql,
                    );
            },
        );

        $controlador =
            app(
                ControladorMetalThursday::class,
            );

        $metodoNomeacao =
            new ReflectionMethod(
                $controlador,
                'notificarNomeacaoSeguinte',
            );

        $metodoPublicacao =
            new ReflectionMethod(
                $controlador,
                'notificarPublicacao',
            );

        $metodoNomeacao->invoke(
            $controlador,
            $metalThursday,
            $reservaSeguinte,
        );

        $metodoPublicacao->invoke(
            $controlador,
            $metalThursday,
        );

        $this->confirmarNotificacaoComPermissoesCarregadas(
            $nomeadoEfetivo,
            NotificacaoUtilizadorNomeado::class,
        );

        $this->confirmarNotificacaoComPermissoesCarregadas(
            $primeiroDestinatario,
            NotificacaoMetalThursdayCriada::class,
        );

        $this->confirmarNotificacaoComPermissoesCarregadas(
            $segundoDestinatario,
            NotificacaoMetalThursdayCriada::class,
        );

        Notification::assertNotSentTo(
            $criador,
            NotificacaoUtilizadorNomeado::class,
        );

        Notification::assertNotSentTo(
            $criador,
            NotificacaoMetalThursdayCriada::class,
        );

        Notification::assertNotSentTo(
            $nomeadoEfetivo,
            NotificacaoMetalThursdayCriada::class,
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
            2,
            $consultasPermissoes,
        );
    }

    /**
     * Confirma que a ausência de uma nova reserva não origina uma notificação
     * de nomeação nem impede a notificação geral da publicação.
     *
     * @since 2.0.0
     */
    #[Test]
    public function sem_nova_reserva_nao_notifica_nomeacao_e_mantem_publicacao_geral(): void
    {
        Notification::fake();

        $criador =
            $this->criarUtilizadorSelecionavel();

        $primeiroDestinatario =
            $this->criarUtilizadorSelecionavel();

        $segundoDestinatario =
            $this->criarUtilizadorSelecionavel();

        $metalThursday = MetalThursday::factory()
            ->comData(
                CarbonImmutable::parse(
                    '2026-08-27',
                ),
            )
            ->comAutor(
                $criador,
            )
            ->create([
                'criado_por_id' => $criador->getKey(),

                'proximo_nomeado_id' => null,
            ]);

        $controlador =
            app(
                ControladorMetalThursday::class,
            );

        $metodoNomeacao =
            new ReflectionMethod(
                $controlador,
                'notificarNomeacaoSeguinte',
            );

        $metodoPublicacao =
            new ReflectionMethod(
                $controlador,
                'notificarPublicacao',
            );

        $metodoNomeacao->invoke(
            $controlador,
            $metalThursday,
            null,
        );

        $metodoPublicacao->invoke(
            $controlador,
            $metalThursday,
        );

        Notification::assertNotSentTo(
            $criador,
            NotificacaoUtilizadorNomeado::class,
        );

        Notification::assertNotSentTo(
            $primeiroDestinatario,
            NotificacaoUtilizadorNomeado::class,
        );

        Notification::assertNotSentTo(
            $segundoDestinatario,
            NotificacaoUtilizadorNomeado::class,
        );

        Notification::assertSentTo(
            $primeiroDestinatario,
            NotificacaoMetalThursdayCriada::class,
        );

        Notification::assertSentTo(
            $segundoDestinatario,
            NotificacaoMetalThursdayCriada::class,
        );

        Notification::assertNotSentTo(
            $criador,
            NotificacaoMetalThursdayCriada::class,
        );
    }

    /**
     * Confirma que uma notificação foi enviada ao utilizador com a relação
     * das permissões de e-mail previamente carregada.
     *
     * A chamada explícita a `via()` comprova que a determinação dos canais
     * utiliza a coleção já carregada e não executa uma consulta individual.
     *
     * @param  Utilizador  $utilizador  Destinatário esperado.
     * @param  class-string<NotificacaoAplicacao>  $classeNotificacao  Classe
     *                                                                 esperada.
     *
     * @since 2.0.0
     */
    private function confirmarNotificacaoComPermissoesCarregadas(
        Utilizador $utilizador,
        string $classeNotificacao,
    ): void {
        Notification::assertSentTo(
            $utilizador,
            $classeNotificacao,
            static function (
                mixed $notificacao,
                array $canais,
                mixed $destinatario,
            ): bool {
                self::assertInstanceOf(
                    NotificacaoAplicacao::class,
                    $notificacao,
                );

                self::assertInstanceOf(
                    Utilizador::class,
                    $destinatario,
                );

                self::assertTrue(
                    $destinatario->relationLoaded(
                        'permissoesEmail',
                    ),
                );

                $canaisDeterminados =
                    $notificacao->via(
                        $destinatario,
                    );

                self::assertTrue(
                    in_array(
                        'database',
                        $canaisDeterminados,
                        true,
                    ),
                    'A notificação deve utilizar o canal de base de dados.',
                );

                return true;
            },
        );
    }

    /**
     * Cria um utilizador elegível para seleção e notificação.
     *
     * O papel é definido explicitamente para o teste não depender dos valores
     * predefinidos da factory.
     *
     * @return Utilizador Utilizador persistido.
     *
     * @since 2.0.0
     */
    private function criarUtilizadorSelecionavel(): Utilizador
    {
        return Utilizador::factory()
            ->create([
                'papel' => PapelUtilizador::Utilizador->value,
            ]);
    }
}
