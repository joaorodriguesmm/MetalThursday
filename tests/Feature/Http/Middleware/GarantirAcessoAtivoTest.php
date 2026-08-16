<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Middleware;

use App\Enumeracoes\PapelUtilizador;
use App\Models\Autenticacao\Utilizador;
use Carbon\CarbonImmutable;
use Database\Factories\Autenticacao\UtilizadorFactory;
use Illuminate\Auth\SessionGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use LogicException;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Cookie;
use Tests\TestCase;

/**
 * Testa a interrupção da autenticação de utilizadores suspensos.
 *
 * @since 2.0.0
 */
final class GarantirAcessoAtivoTest extends TestCase
{
    use RefreshDatabase;

    /**
     * URI da rota isolada utilizada pelos testes.
     *
     * @since 2.0.0
     */
    private const URI_TESTE =
        '__testes/acesso-ativo';

    /**
     * Caminho HTTP da rota isolada utilizada pelos testes.
     *
     * @since 2.0.0
     */
    private const CAMINHO_TESTE =
        '/__testes/acesso-ativo';

    /**
     * Regista a rota isolada antes de cada teste.
     *
     * O nome permanece em inglês por corresponder ao ciclo de vida do
     * PHPUnit.
     *
     * @since 2.0.0
     */
    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(
            'web',
        )->get(
            self::URI_TESTE,
            static fn (): Response => response(
                'Acesso permitido.',
            ),
        );
    }

    /**
     * Confirma que um visitante pode prosseguir normalmente.
     *
     * @since 2.0.0
     */
    #[Test]
    public function permite_o_pedido_de_um_visitante(): void
    {
        $this
            ->get(
                self::CAMINHO_TESTE,
            )
            ->assertOk()
            ->assertSeeText(
                'Acesso permitido.',
            );

        $this->assertGuest(
            'sessao',
        );
    }

    /**
     * Confirma que um utilizador com acesso ativo pode prosseguir.
     *
     * @since 2.0.0
     */
    #[Test]
    public function permite_o_pedido_de_um_utilizador_com_acesso_ativo(): void
    {
        $utilizador = Utilizador::factory()
            ->create();

        $this
            ->actingAs(
                $utilizador,
                'sessao',
            )
            ->get(
                self::CAMINHO_TESTE,
            )
            ->assertOk()
            ->assertSeeText(
                'Acesso permitido.',
            );

        $this->assertAuthenticatedAs(
            $utilizador,
            'sessao',
        );
    }

    /**
     * Confirma que uma sessão autenticada é terminada quando o utilizador
     * está suspenso.
     *
     * @since 2.0.0
     */
    #[Test]
    public function termina_a_sessao_de_um_utilizador_suspenso(): void
    {
        $responsavel =
            $this->criarSuperAdministrador();

        $utilizador = Utilizador::factory()
            ->suspensoPor(
                $responsavel,
                'Suspensão administrativa.',
            )
            ->create();

        $tokenCsrfAnterior =
            'token-csrf-anterior';

        $resposta = $this
            ->actingAs(
                $utilizador,
                'sessao',
            )
            ->withSession([
                '_token' => $tokenCsrfAnterior,
            ])
            ->get(
                self::CAMINHO_TESTE,
            );

        $resposta
            ->assertRedirect(
                route(
                    'login',
                ),
            )
            ->assertSessionHasErrors([
                'email' => 'A tua conta encontra-se suspensa.',
            ])
            ->assertSessionHas(
                '_token',
                static fn (
                    mixed $token,
                ): bool => is_string(
                    $token,
                )
                    && $token !== ''
                    && $token !== $tokenCsrfAnterior,
            );

        $this->assertGuest(
            'sessao',
        );
    }

    /**
     * Confirma a resposta proibida para pedidos que esperam JSON.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_em_json_um_utilizador_suspenso(): void
    {
        $responsavel =
            $this->criarSuperAdministrador();

        $utilizador = Utilizador::factory()
            ->suspensoPor(
                $responsavel,
                'Suspensão administrativa.',
            )
            ->create();

        $this
            ->actingAs(
                $utilizador,
                'sessao',
            )
            ->getJson(
                self::CAMINHO_TESTE,
            )
            ->assertForbidden()
            ->assertExactJson([
                'mensagem' => 'A tua conta encontra-se suspensa.',
            ]);

        $this->assertGuest(
            'sessao',
        );
    }

    /**
     * Confirma que um cookie persistente não permite recuperar o acesso de
     * uma conta entretanto suspensa.
     *
     * A suspensão é persistida diretamente neste teste sem renovar o
     * `remember_token`. Desta forma, confirma-se que o middleware também
     * protege a aplicação quando o cookie ainda é tecnicamente válido.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_a_autenticacao_recuperada_por_cookie_persistente(): void
    {
        $utilizador = Utilizador::factory()
            ->create();

        $respostaAutenticacao = $this->post(
            route(
                'autenticacao.iniciar',
            ),
            [
                'email' => $utilizador->email,

                'palavra_passe' => UtilizadorFactory::PALAVRA_PASSE_PREDEFINIDA,

                'manter_sessao_iniciada' => true,
            ],
        );

        $respostaAutenticacao->assertRedirect(
            route(
                'inicio',
            ),
        );

        $guarda =
            $this->obterGuardaSessao();

        $nomeCookiePersistente =
            $guarda->getRecallerName();

        $cookiePersistente =
            $this->obterCookieDaResposta(
                $respostaAutenticacao,
                $nomeCookiePersistente,
            );

        $tokenPersistenteAnterior =
            $utilizador
                ->fresh()
                ?->getRememberToken();

        self::assertIsString(
            $tokenPersistenteAnterior,
        );

        $this->limparAutenticacaoAtual(
            $guarda,
        );

        $responsavel =
            $this->criarSuperAdministrador();

        $utilizador->refresh();

        $utilizador->suspenso_em =
            CarbonImmutable::now();

        $utilizador->motivo_suspensao =
            'Suspensão posterior à emissão do cookie.';

        $utilizador
            ->responsavelSuspensao()
            ->associate(
                $responsavel,
            );

        $utilizador->saveOrFail();

        self::assertSame(
            $tokenPersistenteAnterior,
            $utilizador
                ->fresh()
                ?->getRememberToken(),
        );

        $resposta = $this
            ->withUnencryptedCookie(
                $nomeCookiePersistente,
                $cookiePersistente->getValue(),
            )
            ->get(
                self::CAMINHO_TESTE,
            );

        $resposta
            ->assertRedirect(
                route(
                    'login',
                ),
            )
            ->assertSessionHasErrors([
                'email' => 'A tua conta encontra-se suspensa.',
            ])
            ->assertCookieExpired(
                $nomeCookiePersistente,
            );

        $this->assertGuest(
            'sessao',
        );

        $utilizador->refresh();

        self::assertNotSame(
            $tokenPersistenteAnterior,
            $utilizador->getRememberToken(),
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
     * Obtém um cookie pelo respetivo nome.
     *
     * @param  TestResponse  $resposta  Resposta HTTP.
     * @param  string  $nome  Nome do cookie.
     * @return Cookie Cookie encontrado.
     *
     * @throws LogicException Quando o cookie não está presente.
     *
     * @since 2.0.0
     */
    private function obterCookieDaResposta(
        TestResponse $resposta,
        string $nome,
    ): Cookie {
        foreach (
            $resposta
                ->headers
                ->getCookies() as $cookie
        ) {
            if ($cookie->getName() === $nome) {
                return $cookie;
            }
        }

        throw new LogicException(
            sprintf(
                'A resposta não contém o cookie "%s".',
                $nome,
            ),
        );
    }

    /**
     * Remove o utilizador autenticado atualmente sem invalidar o cookie
     * persistente capturado pelo teste.
     *
     * @param  SessionGuard  $guarda  Guard utilizado.
     *
     * @throws LogicException Quando o armazenamento da sessão possui um tipo
     *                        inesperado.
     *
     * @since 2.0.0
     */
    private function limparAutenticacaoAtual(
        SessionGuard $guarda,
    ): void {
        $armazenamentoSessao =
            $this->app->make(
                'session.store',
            );

        if (! $armazenamentoSessao instanceof Store) {
            throw new LogicException(
                'O armazenamento da sessão possui um tipo inesperado.',
            );
        }

        $armazenamentoSessao->forget(
            $guarda->getName(),
        );

        $guarda->forgetUser();
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
