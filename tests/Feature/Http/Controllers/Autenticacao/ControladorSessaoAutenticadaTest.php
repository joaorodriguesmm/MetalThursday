<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Autenticacao;

use App\Enumeracoes\PapelUtilizador;
use App\Models\Autenticacao\Utilizador;
use Database\Factories\Autenticacao\UtilizadorFactory;
use Illuminate\Auth\SessionGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa o início e o encerramento da sessão autenticada.
 *
 * @since 2.0.0
 */
final class ControladorSessaoAutenticadaTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Confirma que o formulário de início de sessão está disponível.
     *
     * @since 2.0.0
     */
    #[Test]
    public function apresenta_o_formulario_de_inicio_de_sessao(): void
    {
        $this
            ->get(
                route(
                    'login',
                ),
            )
            ->assertOk()
            ->assertViewIs(
                'autenticacao.iniciar-sessao',
            );
    }

    /**
     * Confirma a autenticação de um utilizador com acesso ativo.
     *
     * @since 2.0.0
     */
    #[Test]
    public function autentica_um_utilizador_com_acesso_ativo(): void
    {
        $utilizador = Utilizador::factory()
            ->create();

        $resposta = $this->post(
            route(
                'autenticacao.iniciar',
            ),
            [
                'email' => $utilizador->email,

                'palavra_passe' => UtilizadorFactory::PALAVRA_PASSE_PREDEFINIDA,
            ],
        );

        $resposta->assertRedirect(
            route(
                'inicio',
            ),
        );

        $this->assertAuthenticatedAs(
            $utilizador,
            'sessao',
        );
    }

    /**
     * Confirma que a opção de manter a sessão iniciada cria o cookie
     * persistente.
     *
     * @since 2.0.0
     */
    #[Test]
    public function cria_autenticacao_persistente_quando_solicitada(): void
    {
        $utilizador = Utilizador::factory()
            ->create();

        $resposta = $this->post(
            route(
                'autenticacao.iniciar',
            ),
            [
                'email' => $utilizador->email,

                'palavra_passe' => UtilizadorFactory::PALAVRA_PASSE_PREDEFINIDA,

                'manter_sessao_iniciada' => true,
            ],
        );

        $resposta->assertRedirect(
            route(
                'inicio',
            ),
        );

        $resposta->assertCookie(
            $this
                ->obterGuardaSessao()
                ->getRecallerName(),
        );

        $this->assertAuthenticatedAs(
            $utilizador,
            'sessao',
        );
    }

    /**
     * Confirma o encerramento da sessão autenticada.
     *
     * @since 2.0.0
     */
    #[Test]
    public function termina_a_sessao_autenticada(): void
    {
        $utilizador = Utilizador::factory()
            ->create();

        $this->actingAs(
            $utilizador,
            'sessao',
        );

        $this
            ->post(
                route(
                    'logout',
                ),
            )
            ->assertRedirect(
                route(
                    'login',
                ),
            );

        $this->assertGuest(
            'sessao',
        );
    }

    /**
     * Confirma que uma conta suspensa não pode iniciar sessão.
     *
     * Uma tentativa com credenciais corretas não é contabilizada como uma
     * falha de autenticação.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_credenciais_validas_de_um_utilizador_suspenso(): void
    {
        $responsavel =
            $this->criarSuperAdministrador();

        $utilizador = Utilizador::factory()
            ->suspensoPor(
                $responsavel,
                'Suspensão administrativa.',
            )
            ->create();

        $chaveLimitacao =
            $this->criarChaveLimitacao(
                $utilizador->email,
            );

        RateLimiter::clear(
            $chaveLimitacao,
        );

        $resposta = $this->from(
            route(
                'login',
            ),
        )->post(
            route(
                'autenticacao.iniciar',
            ),
            [
                'email' => $utilizador->email,

                'palavra_passe' => UtilizadorFactory::PALAVRA_PASSE_PREDEFINIDA,

                'manter_sessao_iniciada' => true,
            ],
        );

        $resposta
            ->assertRedirect(
                route(
                    'login',
                ),
            )
            ->assertSessionHasErrors([
                'email' => 'A tua conta encontra-se suspensa.',
            ]);

        $this->assertGuest(
            'sessao',
        );

        self::assertSame(
            0,
            RateLimiter::attempts(
                $chaveLimitacao,
            ),
        );

        $resposta->assertCookieMissing(
            $this
                ->obterGuardaSessao()
                ->getRecallerName(),
        );
    }

    /**
     * Confirma que uma palavra-passe incorreta não revela que a conta está
     * suspensa.
     *
     * @since 2.0.0
     */
    #[Test]
    public function nao_revela_a_suspensao_quando_a_palavra_passe_e_incorreta(): void
    {
        $responsavel =
            $this->criarSuperAdministrador();

        $utilizador = Utilizador::factory()
            ->suspensoPor(
                $responsavel,
                'Suspensão administrativa.',
            )
            ->create();

        $chaveLimitacao =
            $this->criarChaveLimitacao(
                $utilizador->email,
            );

        RateLimiter::clear(
            $chaveLimitacao,
        );

        $resposta = $this->from(
            route(
                'login',
            ),
        )->post(
            route(
                'autenticacao.iniciar',
            ),
            [
                'email' => $utilizador->email,

                'palavra_passe' => 'PalavraPasseIncorreta!123',
            ],
        );

        $resposta
            ->assertRedirect(
                route(
                    'login',
                ),
            )
            ->assertSessionHasErrors([
                'email' => trans(
                    'auth.failed',
                ),
            ]);

        $this->assertGuest(
            'sessao',
        );

        self::assertSame(
            1,
            RateLimiter::attempts(
                $chaveLimitacao,
            ),
        );
    }

    /**
     * Confirma que um utilizador sem o endereço verificado não mantém a
     * sessão autenticada.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_um_utilizador_com_email_nao_verificado(): void
    {
        $utilizador = Utilizador::factory()
            ->naoVerificado()
            ->create();

        $resposta = $this->from(
            route(
                'login',
            ),
        )->post(
            route(
                'autenticacao.iniciar',
            ),
            [
                'email' => $utilizador->email,

                'palavra_passe' => UtilizadorFactory::PALAVRA_PASSE_PREDEFINIDA,
            ],
        );

        $resposta
            ->assertRedirect(
                route(
                    'login',
                ),
            )
            ->assertSessionHasErrors([
                'email' => 'Verifica o teu endereço de e-mail antes de iniciares sessão.',
            ])
            ->assertSessionHasInput(
                'email',
                $utilizador->email,
            );

        $this->assertGuest(
            'sessao',
        );
    }

    /**
     * Obtém o guard de autenticação baseado em sessões.
     *
     * @return SessionGuard Guard utilizado pela aplicação.
     *
     * @since 2.0.0
     */
    private function obterGuardaSessao(): SessionGuard
    {
        $guarda =
            Auth::guard(
                'sessao',
            );

        self::assertInstanceOf(
            SessionGuard::class,
            $guarda,
        );

        return $guarda;
    }

    /**
     * Cria a chave utilizada pelo limitador de autenticação.
     *
     * @param  string  $email  Endereço normalizado.
     * @return string Chave do limitador.
     *
     * @since 2.0.0
     */
    private function criarChaveLimitacao(
        string $email,
    ): string {
        return 'autenticacao:'.hash(
            'sha256',
            mb_strtolower(
                trim(
                    $email,
                ),
            )
                .'|127.0.0.1',
        );
    }

    /**
     * Cria um superadministrador com acesso ativo.
     *
     * @return Utilizador Superadministrador criado.
     *
     * @since 2.0.0
     */
    private function criarSuperAdministrador(): Utilizador
    {
        return Utilizador::factory()
            ->comPapel(
                PapelUtilizador::SuperAdministrador,
            )
            ->create();
    }
}
