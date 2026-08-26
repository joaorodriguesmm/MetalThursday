<?php

declare(strict_types=1);

namespace Tests\Feature\Notifications;

use App\Enumeracoes\IdentificadorPermissaoEmail;
use App\Models\Autenticacao\Utilizador;
use App\Models\Comunicacao\PermissaoEmail;
use App\Models\MetalThursday\MetalThursday;
use App\Notifications\NotificacaoMetalThursdayCriada;
use Database\Seeders\PermissaoEmailSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa a política de canais da notificação de publicação de uma
 * MetalThursday.
 *
 * @since 2.0.0
 */
final class NotificacaoMetalThursdayCriadaCanaisTest extends TestCase
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
            $this->criarNotificacao();

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
     * publicação por e-mail.
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
            IdentificadorPermissaoEmail::AlertaNomeacao,
        );

        $notificacao =
            $this->criarNotificacao();

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
     * Confirma que a permissão específica de novas publicações autoriza o
     * envio por e-mail.
     *
     * @since 2.0.0
     */
    #[Test]
    public function permissao_novas_publicacoes_ativa_email(): void
    {
        $destinatario = Utilizador::factory()
            ->create();

        $this->atribuirPermissao(
            $destinatario,
            IdentificadorPermissaoEmail::NovasPublicacoes,
        );

        $notificacao =
            $this->criarNotificacao();

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
            $this->criarNotificacao();

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
     * Cria uma notificação de publicação válida.
     *
     * @return NotificacaoMetalThursdayCriada Notificação preparada.
     *
     * @since 2.0.0
     */
    private function criarNotificacao(): NotificacaoMetalThursdayCriada
    {
        $criador = Utilizador::factory()
            ->create();

        $autor = Utilizador::factory()
            ->create();

        $nomeado = Utilizador::factory()
            ->create();

        $this->actingAs(
            $criador,
            'sessao',
        );

        $metalThursday = MetalThursday::factory()
            ->comAutor(
                $autor,
            )
            ->comProximoNomeado(
                $nomeado,
            )
            ->create();

        return new NotificacaoMetalThursdayCriada(
            $metalThursday,
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
