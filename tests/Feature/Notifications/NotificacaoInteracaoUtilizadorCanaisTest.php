<?php

declare(strict_types=1);

namespace Tests\Feature\Notifications;

use App\Enumeracoes\IdentificadorPermissaoEmail;
use App\Models\Autenticacao\Utilizador;
use App\Models\Comunicacao\PermissaoEmail;
use App\Models\MetalThursday\MetalThursday;
use App\Notifications\NotificacaoInteracaoUtilizador;
use Database\Seeders\PermissaoEmailSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa a política de canais das notificações de interações.
 *
 * @since 2.0.0
 */
final class NotificacaoInteracaoUtilizadorCanaisTest extends TestCase
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
        $autor = Utilizador::factory()
            ->create();

        $destinatario = Utilizador::factory()
            ->create();

        $notificacao =
            $this->criarNotificacao(
                $autor,
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
     * interação por e-mail.
     *
     * @since 2.0.0
     */
    #[Test]
    public function permissao_nao_relacionada_nao_ativa_email(): void
    {
        $autor = Utilizador::factory()
            ->create();

        $destinatario = Utilizador::factory()
            ->create();

        $this->atribuirPermissao(
            $destinatario,
            IdentificadorPermissaoEmail::NovasPublicacoes,
        );

        $notificacao =
            $this->criarNotificacao(
                $autor,
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
     * Confirma que a permissão para todas as interações autoriza o envio por
     * e-mail independentemente da autoria da publicação.
     *
     * @since 2.0.0
     */
    #[Test]
    public function permissao_todas_interacoes_ativa_email(): void
    {
        $autor = Utilizador::factory()
            ->create();

        $destinatario = Utilizador::factory()
            ->create();

        $this->atribuirPermissao(
            $destinatario,
            IdentificadorPermissaoEmail::TodasInteracoes,
        );

        $notificacao =
            $this->criarNotificacao(
                $autor,
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
     * Confirma que a permissão relativa às próprias publicações autoriza o
     * e-mail quando o destinatário é o autor da MetalThursday.
     *
     * @since 2.0.0
     */
    #[Test]
    public function permissao_interacoes_nas_minhas_publicacoes_ativa_email_para_autor(): void
    {
        $autor = Utilizador::factory()
            ->create();

        $this->atribuirPermissao(
            $autor,
            IdentificadorPermissaoEmail::InteracoesNasMinhasPublicacoes,
        );

        $notificacao =
            $this->criarNotificacao(
                $autor,
            );

        self::assertSame(
            [
                'database',
                'mail',
            ],
            $notificacao->via(
                $autor,
            ),
        );
    }

    /**
     * Confirma que a permissão relativa às próprias publicações não autoriza o
     * e-mail quando o destinatário não é o autor da MetalThursday.
     *
     * @since 2.0.0
     */
    #[Test]
    public function permissao_interacoes_nas_minhas_publicacoes_nao_ativa_email_para_outro_utilizador(): void
    {
        $autor = Utilizador::factory()
            ->create();

        $destinatario = Utilizador::factory()
            ->create();

        $this->atribuirPermissao(
            $destinatario,
            IdentificadorPermissaoEmail::InteracoesNasMinhasPublicacoes,
        );

        $notificacao =
            $this->criarNotificacao(
                $autor,
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
     * Confirma que a permissão global autoriza o envio da interação por
     * e-mail.
     *
     * @since 2.0.0
     */
    #[Test]
    public function permissao_global_ativa_email(): void
    {
        $autor = Utilizador::factory()
            ->create();

        $destinatario = Utilizador::factory()
            ->create();

        $this->atribuirPermissao(
            $destinatario,
            IdentificadorPermissaoEmail::TodasNotificacoes,
        );

        $notificacao =
            $this->criarNotificacao(
                $autor,
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
     * Cria uma notificação válida relativa a uma MetalThursday.
     *
     * @param  Utilizador  $autor  Autor da publicação.
     * @return NotificacaoInteracaoUtilizador Notificação preparada.
     *
     * @since 2.0.0
     */
    private function criarNotificacao(
        Utilizador $autor,
    ): NotificacaoInteracaoUtilizador {
        $causador = Utilizador::factory()
            ->create();

        $metalThursday = MetalThursday::factory()
            ->comAutor(
                $autor,
            )
            ->create();

        return new NotificacaoInteracaoUtilizador(
            $metalThursday,
            $causador,
            'gostou',
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
