<?php

declare(strict_types=1);

namespace Tests\Feature\Notifications;

use App\Enumeracoes\IdentificadorPermissaoEmail;
use App\Models\Autenticacao\Utilizador;
use App\Models\Comunicacao\PermissaoEmail;
use App\Models\MetalThursday\ReservaMetalThursday;
use App\Notifications\NotificacaoLembreteAtrasoMetalThursday;
use Carbon\CarbonImmutable;
use Database\Seeders\PermissaoEmailSeeder;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa o lembrete diário de uma MetalThursday em atraso.
 *
 * @since 2.0.0
 */
final class NotificacaoLembreteAtrasoMetalThursdayTest extends TestCase
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
     * Confirma que o lembrete conserva os valores da reserva sem depender de
     * consultas posteriores.
     *
     * @since 2.0.0
     */
    #[Test]
    public function preserva_retrato_da_reserva_sem_consultas_posteriores(): void
    {
        $responsavel = Utilizador::factory()
            ->create();

        $reserva =
            $this->criarReservaAtrasada(
                $responsavel,
            );

        $identificadorReserva =
            (int) $reserva->getKey();

        $notificacao = unserialize(
            serialize(
                new NotificacaoLembreteAtrasoMetalThursday(
                    $reserva,
                    $this->dataReferencia(),
                ),
            ),
            [
                'allowed_classes' => true,
            ],
        );

        self::assertInstanceOf(
            NotificacaoLembreteAtrasoMetalThursday::class,
            $notificacao,
        );

        $reserva->updateOrFail([
            'data' => CarbonImmutable::parse(
                '2026-08-13',
            ),
        ]);

        $consultas = [];

        DB::listen(
            static function (
                QueryExecuted $consulta,
            ) use (&$consultas): void {
                $consultas[] =
                    $consulta->sql;
            },
        );

        $mensagem =
            $notificacao->toMail(
                $responsavel,
            );

        self::assertSame(
            'Lembrete: tens uma MetalThursday em atraso',
            $mensagem->subject,
        );

        self::assertSame(
            [
                'A MetalThursday prevista para 20/08/2026 continua por preparar e publicar.',
            ],
            $mensagem->introLines,
        );

        self::assertSame(
            'Preparar MetalThursday',
            $mensagem->actionText,
        );

        self::assertSame(
            route(
                'metal-thursday.reservas.preparar',
                [
                    'reservaMetalThursday' => $identificadorReserva,
                ],
            ),
            $mensagem->actionUrl,
        );

        self::assertSame(
            [],
            $consultas,
        );
    }

    /**
     * Confirma que, sem autorização de e-mail, o lembrete não utiliza qualquer
     * canal e também não é guardado internamente.
     *
     * @since 2.0.0
     */
    #[Test]
    public function sem_permissao_nao_utiliza_qualquer_canal(): void
    {
        $responsavel = Utilizador::factory()
            ->create();

        $notificacao =
            $this->criarNotificacao(
                $responsavel,
            );

        self::assertSame(
            [],
            $notificacao->via(
                $responsavel,
            ),
        );
    }

    /**
     * Confirma que uma preferência não relacionada não autoriza este lembrete.
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
            IdentificadorPermissaoEmail::LembreteDiarioTarefas,
        );

        $notificacao =
            $this->criarNotificacao(
                $responsavel,
            );

        self::assertSame(
            [],
            $notificacao->via(
                $responsavel,
            ),
        );
    }

    /**
     * Confirma que a preferência específica de atrasos ativa exclusivamente o
     * canal de e-mail.
     *
     * @since 2.0.0
     */
    #[Test]
    public function permissao_lembrete_diario_atrasos_ativa_apenas_email(): void
    {
        $responsavel = Utilizador::factory()
            ->create();

        $this->atribuirPermissao(
            $responsavel,
            IdentificadorPermissaoEmail::LembreteDiarioAtrasos,
        );

        $notificacao =
            $this->criarNotificacao(
                $responsavel,
            );

        self::assertSame(
            [
                'mail',
            ],
            $notificacao->via(
                $responsavel,
            ),
        );
    }

    /**
     * Confirma que a preferência global também ativa exclusivamente o canal de
     * e-mail.
     *
     * @since 2.0.0
     */
    #[Test]
    public function permissao_global_ativa_apenas_email(): void
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
                'mail',
            ],
            $notificacao->via(
                $responsavel,
            ),
        );
    }

    /**
     * Confirma que a própria data reservada ainda não representa atraso.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_reserva_com_data_igual_ao_dia_de_referencia(): void
    {
        $responsavel = Utilizador::factory()
            ->create();

        $reserva = ReservaMetalThursday::factory()
            ->comData(
                $this->dataReferencia(),
            )
            ->comResponsavel(
                $responsavel,
            )
            ->create();

        $this->expectException(
            InvalidArgumentException::class,
        );

        $this->expectExceptionMessage(
            'A reserva do lembrete deve estar em atraso.',
        );

        new NotificacaoLembreteAtrasoMetalThursday(
            $reserva,
            $this->dataReferencia(),
        );
    }

    /**
     * Cria uma notificação válida de atraso.
     *
     * @param  Utilizador  $responsavel  Utilizador responsável.
     * @return NotificacaoLembreteAtrasoMetalThursday Notificação preparada.
     *
     * @since 2.0.0
     */
    private function criarNotificacao(
        Utilizador $responsavel,
    ): NotificacaoLembreteAtrasoMetalThursday {
        return new NotificacaoLembreteAtrasoMetalThursday(
            $this->criarReservaAtrasada(
                $responsavel,
            ),
            $this->dataReferencia(),
        );
    }

    /**
     * Cria uma reserva pendente anterior ao dia de referência.
     *
     * @param  Utilizador  $responsavel  Utilizador responsável.
     * @return ReservaMetalThursday Reserva criada.
     *
     * @since 2.0.0
     */
    private function criarReservaAtrasada(
        Utilizador $responsavel,
    ): ReservaMetalThursday {
        return ReservaMetalThursday::factory()
            ->comData(
                CarbonImmutable::parse(
                    '2026-08-20',
                    'Europe/Lisbon',
                ),
            )
            ->comResponsavel(
                $responsavel,
            )
            ->create();
    }

    /**
     * Atribui exclusivamente uma preferência de e-mail ao utilizador.
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

    /**
     * Obtém o dia utilizado para determinar o atraso.
     *
     * @return CarbonImmutable Dia de referência.
     *
     * @since 2.0.0
     */
    private function dataReferencia(): CarbonImmutable
    {
        return CarbonImmutable::parse(
            '2026-08-27 08:00:00',
            'Europe/Lisbon',
        );
    }
}
