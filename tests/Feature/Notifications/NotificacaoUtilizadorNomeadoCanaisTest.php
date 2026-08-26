<?php

declare(strict_types=1);

namespace Tests\Feature\Notifications;

use App\Enumeracoes\IdentificadorPermissaoEmail;
use App\Models\Autenticacao\Utilizador;
use App\Models\Comunicacao\PermissaoEmail;
use App\Models\MetalThursday\ReservaMetalThursday;
use App\Notifications\NotificacaoUtilizadorNomeado;
use Carbon\CarbonImmutable;
use Database\Seeders\PermissaoEmailSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa a política de canais da notificação de nomeação para uma
 * MetalThursday.
 *
 * @since 2.0.0
 */
final class NotificacaoUtilizadorNomeadoCanaisTest extends TestCase
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
     * Confirma que, sem permissões de e-mail, a notificação permanece apenas
     * na aplicação.
     *
     * @since 2.0.0
     */
    #[Test]
    public function sem_permissao_utiliza_apenas_base_de_dados(): void
    {
        $destinatario = Utilizador::factory()
            ->create();

        $notificacao =
            $this->criarNotificacao(
                $destinatario,
            );

        self::assertSame(
            [
                'database',
            ],
            $notificacao->via(
                $destinatario,
            ),
        );
    }

    /**
     * Confirma que uma permissão não relacionada não autoriza o envio da
     * nomeação por e-mail.
     *
     * @since 2.0.0
     */
    #[Test]
    public function permissao_nao_relacionada_nao_ativa_email(): void
    {
        $destinatario = Utilizador::factory()
            ->create();

        $this->atribuirPermissao(
            $destinatario,
            IdentificadorPermissaoEmail::NovasPublicacoes,
        );

        $notificacao =
            $this->criarNotificacao(
                $destinatario,
            );

        self::assertSame(
            [
                'database',
            ],
            $notificacao->via(
                $destinatario,
            ),
        );
    }

    /**
     * Confirma que a permissão específica de nomeação autoriza o envio por
     * e-mail.
     *
     * @since 2.0.0
     */
    #[Test]
    public function permissao_alerta_nomeacao_ativa_email(): void
    {
        $destinatario = Utilizador::factory()
            ->create();

        $this->atribuirPermissao(
            $destinatario,
            IdentificadorPermissaoEmail::AlertaNomeacao,
        );

        $notificacao =
            $this->criarNotificacao(
                $destinatario,
            );

        self::assertSame(
            [
                'database',
                'mail',
            ],
            $notificacao->via(
                $destinatario,
            ),
        );
    }

    /**
     * Confirma que a permissão global também autoriza o envio por e-mail.
     *
     * @since 2.0.0
     */
    #[Test]
    public function permissao_global_ativa_email(): void
    {
        $destinatario = Utilizador::factory()
            ->create();

        $this->atribuirPermissao(
            $destinatario,
            IdentificadorPermissaoEmail::TodasNotificacoes,
        );

        $notificacao =
            $this->criarNotificacao(
                $destinatario,
            );

        self::assertSame(
            [
                'database',
                'mail',
            ],
            $notificacao->via(
                $destinatario,
            ),
        );
    }

    /**
     * Cria uma notificação de nomeação automática válida.
     *
     * @param  Utilizador  $destinatario  Utilizador nomeado.
     * @return NotificacaoUtilizadorNomeado Notificação preparada.
     *
     * @since 2.0.0
     */
    private function criarNotificacao(
        Utilizador $destinatario,
    ): NotificacaoUtilizadorNomeado {
        $reserva = ReservaMetalThursday::factory()
            ->comData(
                CarbonImmutable::create(
                    2026,
                    8,
                    27,
                ),
            )
            ->comResponsavel(
                $destinatario,
            )
            ->create();

        return new NotificacaoUtilizadorNomeado(
            $reserva,
        );
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
