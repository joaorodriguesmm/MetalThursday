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
use Illuminate\Contracts\Notifications\Dispatcher;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use RuntimeException;
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
     * Impede a exposição de mensagens sensíveis quando falha
     * o despacho da notificação do próximo nomeado.
     *
     * @since 2.0.0
     */
    #[Test]
    public function nao_expoe_mensagem_sensivel_ao_falhar_notificacao_do_proximo_nomeado(): void
    {
        $segredoFicticio =
            'AUDITORIA_SEGREDO_FICTICIO_B19_17_NOMEACAO';

        $criador =
            $this->criarUtilizadorSelecionavel();

        $nomeado =
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
                $nomeado,
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
                $nomeado,
            )
            ->create();

        /*
     * Simula uma falha durante o despacho inicial.
     * Não são enviados e-mails nem executados trabalhos de fila.
     */
        $this->mock(
            Dispatcher::class,
        )
            ->shouldReceive(
                'send',
            )
            ->once()
            ->withArgs(
                static function (
                    mixed $destinatario,
                    mixed $notificacao,
                ) use (
                    $nomeado,
                ): bool {
                    return $destinatario instanceof Utilizador
                        && $destinatario->is($nomeado)
                        && $notificacao instanceof NotificacaoUtilizadorNomeado;
                },
            )
            ->andThrow(
                new RuntimeException(
                    'Falha simulada no despacho com segredo: '.$segredoFicticio,
                ),
            );

        /*
     * Intercepta os logs para impedir a escrita do segredo
     * fictício nos ficheiros reais.
     */
        Log::spy();

        $controlador =
            app(
                ControladorMetalThursday::class,
            );

        $metodoNomeacao =
            new ReflectionMethod(
                $controlador,
                'notificarNomeacaoSeguinte',
            );

        /*
     * O método deve capturar a exceção e terminar normalmente.
     */
        $metodoNomeacao->invoke(
            $controlador,
            $metalThursday,
            $reservaSeguinte,
        );

        $mensagemRegistada = null;
        $contextoRegistado = null;

        Log::shouldHaveReceived(
            'error',
        )
            ->once()
            ->withArgs(
                static function (
                    string $mensagem,
                    array $contexto,
                ) use (
                    &$mensagemRegistada,
                    &$contextoRegistado,
                ): bool {
                    $mensagemRegistada = $mensagem;
                    $contextoRegistado = $contexto;

                    return true;
                },
            );

        self::assertIsString(
            $mensagemRegistada,
        );

        self::assertIsArray(
            $contextoRegistado,
        );

        self::assertStringNotContainsString(
            $segredoFicticio,
            $mensagemRegistada,
        );

        self::assertArrayNotHasKey(
            'exception',
            $contextoRegistado,
        );
    }

    /**
     * Impede a exposição de mensagens sensíveis quando falha
     * o despacho da notificação geral de publicação.
     *
     * A publicação deve permanecer pendente de notificação,
     * permitindo uma tentativa posterior.
     *
     * @since 2.0.0
     */
    #[Test]
    public function nao_expoe_mensagem_sensivel_ao_falhar_notificacao_geral(): void
    {
        $segredoFicticio =
            'AUDITORIA_SEGREDO_FICTICIO_B19_17_PUBLICACAO';

        $criador =
            $this->criarUtilizadorSelecionavel();

        $nomeado =
            $this->criarUtilizadorSelecionavel();

        $destinatario =
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
                $nomeado,
            )
            ->create([
                'criado_por_id' => $criador->getKey(),
            ]);

        /*
     * Garante que existe um destinatário elegível e que o
     * serviço chega ao despacho da notificação geral.
     */
        self::assertTrue(
            $destinatario->exists,
        );

        Notification::shouldReceive(
            'send',
        )
            ->once()
            ->withArgs(
                static function (
                    mixed $destinatarios,
                    mixed $notificacao,
                ): bool {
                    return $notificacao instanceof NotificacaoMetalThursdayCriada
                        && $destinatarios instanceof Collection
                        && $destinatarios->isNotEmpty();
                },
            )
            ->andThrow(
                new RuntimeException(
                    'Falha simulada na publicação com segredo: '.$segredoFicticio,
                ),
            );

        /*
     * Intercepta os logs sem escrever o segredo fictício
     * nos ficheiros reais.
     */
        Log::spy();

        $controlador =
            app(
                ControladorMetalThursday::class,
            );

        $metodoPublicacao =
            new ReflectionMethod(
                $controlador,
                'notificarPublicacao',
            );

        /*
     * A falha deve ser capturada pelo controlador.
     */
        $metodoPublicacao->invoke(
            $controlador,
            $metalThursday,
        );

        /*
     * A publicação não pode ficar marcada como notificada.
     */
        self::assertNull(
            $metalThursday
                ->refresh()
                ->publicacao_notificada_em,
        );

        self::assertTrue(
            MetalThursday::query()
                ->publicadasPorNotificar()
                ->whereKey(
                    $metalThursday->getKey(),
                )
                ->exists(),
        );

        $mensagemRegistada = null;
        $contextoRegistado = null;

        Log::shouldHaveReceived(
            'error',
        )
            ->once()
            ->withArgs(
                static function (
                    string $mensagem,
                    array $contexto,
                ) use (
                    &$mensagemRegistada,
                    &$contextoRegistado,
                ): bool {
                    $mensagemRegistada = $mensagem;
                    $contextoRegistado = $contexto;

                    return true;
                },
            );

        self::assertIsString(
            $mensagemRegistada,
        );

        self::assertIsArray(
            $contextoRegistado,
        );

        self::assertStringNotContainsString(
            $segredoFicticio,
            $mensagemRegistada,
        );

        self::assertArrayNotHasKey(
            'exception',
            $contextoRegistado,
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
