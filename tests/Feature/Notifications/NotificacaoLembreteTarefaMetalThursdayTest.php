<?php

declare(strict_types=1);

namespace Tests\Feature\Notifications;

use App\Enumeracoes\IdentificadorPermissaoEmail;
use App\Models\Autenticacao\Utilizador;
use App\Models\Comunicacao\PermissaoEmail;
use App\Models\MetalThursday\ReservaMetalThursday;
use App\Notifications\NotificacaoLembreteTarefaMetalThursday;
use Carbon\CarbonImmutable;
use Database\Seeders\PermissaoEmailSeeder;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa o lembrete de uma MetalThursday pendente no próprio dia.
 *
 * @since 2.0.0
 */
final class NotificacaoLembreteTarefaMetalThursdayTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Prepara o catálogo real das permissões de e-mail.
     *
     * @since 2.0.0
     */
    protected function setUp(): void
    {
        parent::setUp();

        app(
            PermissaoEmailSeeder::class,
        )->run();
    }

    /**
     * Confirma que o lembrete conserva o retrato da reserva e não executa
     * consultas durante a construção posterior das mensagens.
     *
     * @since 2.0.0
     */
    #[Test]
    public function preserva_retrato_da_reserva_sem_consultas_posteriores(): void
    {
        $responsavel = Utilizador::factory()
            ->create();

        $reserva = $this->criarReserva(
            $responsavel,
        );

        $identificadorReserva =
            (int) $reserva->getKey();

        $notificacao = unserialize(
            serialize(
                new NotificacaoLembreteTarefaMetalThursday(
                    $reserva,
                ),
            ),
            [
                'allowed_classes' => true,
            ],
        );

        self::assertInstanceOf(
            NotificacaoLembreteTarefaMetalThursday::class,
            $notificacao,
        );

        $reserva->updateOrFail([
            'data' => CarbonImmutable::parse(
                '2026-09-03',
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
            $responsavel,
        );

        $notificacao->toMail(
            $responsavel,
        );

        self::assertSame(
            $identificadorReserva,
            $dados['identificador_reserva'],
        );

        self::assertSame(
            'Hoje, dia 27/08/2026, tens uma MetalThursday por preparar e publicar.',
            $dados['mensagem'],
        );

        self::assertSame(
            route(
                'metal-thursday.reservas.preparar',
                [
                    'reservaMetalThursday' => $identificadorReserva,
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
     * Confirma que, sem permissões de e-mail, o lembrete permanece apenas na
     * aplicação.
     *
     * @since 2.0.0
     */
    #[Test]
    public function sem_permissao_utiliza_apenas_base_de_dados(): void
    {
        $responsavel = Utilizador::factory()
            ->create();

        $notificacao =
            $this->criarNotificacao(
                $responsavel,
            );

        self::assertSame(
            [
                'database',
            ],
            $notificacao->via(
                $responsavel,
            ),
        );
    }

    /**
     * Confirma que uma permissão não relacionada não autoriza o envio do
     * lembrete por e-mail.
     *
     * @since 2.0.0
     */
    #[Test]
    public function permissao_nao_relacionada_nao_ativa_email(): void
    {
        $responsavel = Utilizador::factory()
            ->create();

        $this->atribuirPermissao(
            $responsavel,
            IdentificadorPermissaoEmail::NovasPublicacoes,
        );

        $notificacao =
            $this->criarNotificacao(
                $responsavel,
            );

        self::assertSame(
            [
                'database',
            ],
            $notificacao->via(
                $responsavel,
            ),
        );
    }

    /**
     * Confirma que a permissão específica do lembrete diário de tarefas
     * autoriza o envio por e-mail.
     *
     * @since 2.0.0
     */
    #[Test]
    public function permissao_lembrete_diario_tarefas_ativa_email(): void
    {
        $responsavel = Utilizador::factory()
            ->create();

        $this->atribuirPermissao(
            $responsavel,
            IdentificadorPermissaoEmail::LembreteDiarioTarefas,
        );

        $notificacao =
            $this->criarNotificacao(
                $responsavel,
            );

        self::assertSame(
            [
                'database',
                'mail',
            ],
            $notificacao->via(
                $responsavel,
            ),
        );
    }

    /**
     * Confirma que a permissão global também autoriza o envio do lembrete por
     * e-mail.
     *
     * @since 2.0.0
     */
    #[Test]
    public function permissao_global_ativa_email(): void
    {
        $responsavel = Utilizador::factory()
            ->create();

        $this->atribuirPermissao(
            $responsavel,
            IdentificadorPermissaoEmail::TodasNotificacoes,
        );

        $notificacao =
            $this->criarNotificacao(
                $responsavel,
            );

        self::assertSame(
            [
                'database',
                'mail',
            ],
            $notificacao->via(
                $responsavel,
            ),
        );
    }

    /**
     * Cria uma notificação válida para o responsável indicado.
     *
     * @param  Utilizador  $responsavel  Utilizador responsável.
     * @return NotificacaoLembreteTarefaMetalThursday Notificação preparada.
     *
     * @since 2.0.0
     */
    private function criarNotificacao(
        Utilizador $responsavel,
    ): NotificacaoLembreteTarefaMetalThursday {
        return new NotificacaoLembreteTarefaMetalThursday(
            $this->criarReserva(
                $responsavel,
            ),
        );
    }

    /**
     * Cria uma reserva pendente com prazo conhecido.
     *
     * @param  Utilizador  $responsavel  Utilizador responsável.
     * @return ReservaMetalThursday Reserva criada.
     *
     * @since 2.0.0
     */
    private function criarReserva(
        Utilizador $responsavel,
    ): ReservaMetalThursday {
        return ReservaMetalThursday::factory()
            ->comData(
                CarbonImmutable::parse(
                    '2026-08-27',
                ),
            )
            ->comResponsavel(
                $responsavel,
            )
            ->create();
    }

    /**
     * Atribui exclusivamente uma permissão de e-mail ao utilizador.
     *
     * @param  Utilizador  $utilizador  Utilizador a configurar.
     * @param  IdentificadorPermissaoEmail  $identificador  Permissão
     *                                                      pretendida.
     *
     * @since 2.0.0
     */
    private function atribuirPermissao(
        Utilizador $utilizador,
        IdentificadorPermissaoEmail $identificador,
    ): void {
        $permissao = PermissaoEmail::query()
            ->where(
                'identificador',
                $identificador->value,
            )
            ->sole();

        $utilizador
            ->permissoesEmail()
            ->sync([
                $permissao->getKey(),
            ]);

        $utilizador->unsetRelation(
            'permissoesEmail',
        );
    }
}
