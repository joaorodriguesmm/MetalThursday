<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\MetalThursday;

use App\Enumeracoes\PapelUtilizador;
use App\Http\Controllers\MetalThursday\ControladorMetalThursday;
use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\MetalThursday;
use App\Notifications\NotificacaoAplicacao;
use App\Notifications\NotificacaoMetalThursdayCriada;
use App\Notifications\NotificacaoUtilizadorNomeado;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Testa o carregamento dos destinatários das notificações de criação.
 *
 * @since 2.0.0
 *
 * @version 1.1.1
 */
final class NotificacoesCriacaoMetalThursdayTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Confirma que as permissões são carregadas por conjunto e que a
     * determinação dos canais não executa consultas por destinatário.
     *
     * O utilizador nomeado origina uma consulta de carregamento antecipado.
     * Os restantes destinatários do bloco originam uma segunda consulta,
     * independentemente da quantidade de utilizadores notificados.
     *
     * A facade de notificações é substituída pelo fake oficial do Laravel,
     * garantindo a interceção tanto de `notify()` como de
     * `Notification::send()`.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    #[Test]
    public function carrega_permissoes_sem_consultas_por_destinatario(): void
    {
        Notification::fake();

        $criador =
            $this->criarUtilizadorSelecionavel();

        $nomeado =
            $this->criarUtilizadorSelecionavel();

        $primeiroDestinatario =
            $this->criarUtilizadorSelecionavel();

        $segundoDestinatario =
            $this->criarUtilizadorSelecionavel();

        $metalThursday = MetalThursday::factory()
            ->comAutor(
                $criador,
            )
            ->comProximoNomeado(
                $nomeado,
            )
            ->create([
                'criado_por_id' => $criador->getKey(),
            ]);

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

        $metodo =
            new ReflectionMethod(
                $controlador,
                'notificarCriacao',
            );

        $metodo->setAccessible(
            true,
        );

        $metodo->invoke(
            $controlador,
            $metalThursday,
            (int) $criador->getKey(),
        );

        $this->confirmarNotificacaoComPermissoesCarregadas(
            $nomeado,
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
            $nomeado,
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
     *
     * @version 1.0.1
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
     *
     * @version 1.0.0
     */
    private function criarUtilizadorSelecionavel(): Utilizador
    {
        return Utilizador::factory()
            ->create([
                'papel' => PapelUtilizador::Utilizador->value,
            ]);
    }
}
