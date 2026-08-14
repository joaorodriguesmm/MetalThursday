<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Autenticacao;

use App\Models\Autenticacao\Utilizador;
use Database\Factories\Autenticacao\UtilizadorFactory;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route as RotaLaravel;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa as rotas e os formulários públicos de recuperação da palavra-passe.
 *
 * @since 2.0.0
 */
final class RecuperacaoPalavraPasseTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Confirma o contrato português das rotas de recuperação.
     *
     * @since 2.0.0
     */
    #[Test]
    public function regista_rotas_portuguesas_de_recuperacao(): void
    {
        $rotaApresentar =
            $this->obterRota(
                'autenticacao.recuperar-palavra-passe',
            );

        self::assertSame(
            'palavra-passe/esquecida',
            $rotaApresentar->uri(),
        );

        self::assertSame(
            [
                'GET',
                'HEAD',
            ],
            $rotaApresentar->methods(),
        );

        $rotaEnviar =
            $this->obterRota(
                'autenticacao.enviar-ligacao-redefinicao',
            );

        self::assertSame(
            'palavra-passe/esquecida',
            $rotaEnviar->uri(),
        );

        self::assertSame(
            [
                'POST',
            ],
            $rotaEnviar->methods(),
        );
    }

    /**
     * Confirma o contrato português das rotas de redefinição.
     *
     * @since 2.0.0
     */
    #[Test]
    public function regista_rotas_portuguesas_de_redefinicao(): void
    {
        $rotaApresentar =
            $this->obterRota(
                'autenticacao.redefinir-palavra-passe',
            );

        self::assertSame(
            'palavra-passe/redefinir/{token}',
            $rotaApresentar->uri(),
        );

        self::assertSame(
            [
                'GET',
                'HEAD',
            ],
            $rotaApresentar->methods(),
        );

        $rotaAtualizar =
            $this->obterRota(
                'autenticacao.atualizar-palavra-passe',
            );

        self::assertSame(
            'palavra-passe/redefinir',
            $rotaAtualizar->uri(),
        );

        self::assertSame(
            [
                'POST',
            ],
            $rotaAtualizar->methods(),
        );
    }

    /**
     * Garante que os nomes antigos não permanecem registados.
     *
     * @since 2.0.0
     */
    #[Test]
    public function nao_regista_nomes_antigos_das_rotas(): void
    {
        foreach (
            [
                'password.request',
                'password.email',
                'password.reset',
                'password.store',
            ] as $nomeRota
        ) {
            self::assertFalse(
                Route::has(
                    $nomeRota,
                ),
            );
        }
    }

    /**
     * Apresenta os formulários públicos sem referências a rotas inexistentes.
     *
     * Este teste cobre a falha original, na qual a vista de recuperação
     * lançava uma exceção ao tentar gerar o endereço do formulário.
     *
     * @since 2.0.0
     */
    #[Test]
    public function apresenta_formularios_publicos_sem_erros(): void
    {
        $enderecoRecuperacao =
            route(
                'autenticacao.recuperar-palavra-passe',
            );

        $enderecoEnvio =
            route(
                'autenticacao.enviar-ligacao-redefinicao',
            );

        $enderecoAtualizacao =
            route(
                'autenticacao.atualizar-palavra-passe',
            );

        $this
            ->get(
                route(
                    'login',
                ),
            )
            ->assertOk()
            ->assertSee(
                $enderecoRecuperacao,
                false,
            );

        $this
            ->get(
                $enderecoRecuperacao,
            )
            ->assertOk()
            ->assertViewIs(
                'autenticacao.recuperar-palavra-passe',
            )
            ->assertSee(
                $enderecoEnvio,
                false,
            );

        $this
            ->get(
                route(
                    'autenticacao.redefinir-palavra-passe',
                    [
                        'token' => 'codigo-redefinicao-teste',

                        'email' => 'utilizador@example.com',
                    ],
                ),
            )
            ->assertOk()
            ->assertViewIs(
                'autenticacao.redefinir-palavra-passe',
            )
            ->assertSee(
                $enderecoAtualizacao,
                false,
            )
            ->assertSee(
                $enderecoRecuperacao,
                false,
            );
    }

    /**
     * Confirma que o pedido não revela se existe uma conta.
     *
     * @since 2.0.0
     */
    #[Test]
    public function pedido_de_recuperacao_nao_revela_existencia_da_conta(): void
    {
        Notification::fake();

        Utilizador::factory()
            ->create([
                'email' => 'existente@example.com',
            ]);

        $mensagem =
            'Se existir uma conta associada ao endereço indicado, será enviada uma ligação para redefinir a palavra-passe.';

        $enderecoRecuperacao =
            route(
                'autenticacao.recuperar-palavra-passe',
            );

        $this
            ->post(
                route(
                    'autenticacao.enviar-ligacao-redefinicao',
                ),
                [
                    'email' => 'existente@example.com',
                ],
            )
            ->assertRedirect(
                $enderecoRecuperacao,
            )
            ->assertSessionDoesntHaveErrors()
            ->assertSessionHas(
                'informacao',
                $mensagem,
            );

        $this
            ->post(
                route(
                    'autenticacao.enviar-ligacao-redefinicao',
                ),
                [
                    'email' => 'inexistente@example.com',
                ],
            )
            ->assertRedirect(
                $enderecoRecuperacao,
            )
            ->assertSessionDoesntHaveErrors()
            ->assertSessionHas(
                'informacao',
                $mensagem,
            );
    }

    /**
     * Confirma a redefinição válida da palavra-passe.
     *
     * @since 2.0.0
     */
    #[Test]
    public function redefine_a_palavra_passe_com_uma_ligacao_valida(): void
    {
        $tokenPersistenteAnterior =
            'token-persistente-anterior';

        $novaPalavraPasse =
            'NovaPalavraPasse#2026';

        $utilizador =
            Utilizador::factory()
                ->create([
                    'remember_token' => $tokenPersistenteAnterior,
                ]);

        $codigoRedefinicao =
            Password::createToken(
                $utilizador,
            );

        Event::fake([
            PasswordReset::class,
        ]);

        $this
            ->post(
                route(
                    'autenticacao.atualizar-palavra-passe',
                ),
                [
                    'codigo_redefinicao' => $codigoRedefinicao,

                    'email' => $utilizador->email,

                    'palavra_passe' => $novaPalavraPasse,

                    'confirmacao_palavra_passe' => $novaPalavraPasse,
                ],
            )
            ->assertRedirect(
                route(
                    'login',
                ),
            )
            ->assertSessionHas(
                'sucesso',
                'A palavra-passe foi redefinida com sucesso.',
            );

        $utilizador->refresh();

        self::assertTrue(
            Hash::check(
                $novaPalavraPasse,
                $utilizador->password,
            ),
        );

        self::assertNotSame(
            $tokenPersistenteAnterior,
            $utilizador->getRememberToken(),
        );

        Event::assertDispatched(
            PasswordReset::class,
        );
    }

    /**
     * Confirma a resposta segura perante uma ligação inválida.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_ligacao_de_redefinicao_invalida(): void
    {
        $utilizador =
            Utilizador::factory()
                ->create();

        $novaPalavraPasse =
            'NovaPalavraPasse#2026';

        $enderecoFormulario =
            route(
                'autenticacao.redefinir-palavra-passe',
                [
                    'token' => 'codigo-invalido',

                    'email' => $utilizador->email,
                ],
            );

        $this
            ->from(
                $enderecoFormulario,
            )
            ->post(
                route(
                    'autenticacao.atualizar-palavra-passe',
                ),
                [
                    'codigo_redefinicao' => 'codigo-invalido',

                    'email' => $utilizador->email,

                    'palavra_passe' => $novaPalavraPasse,

                    'confirmacao_palavra_passe' => $novaPalavraPasse,
                ],
            )
            ->assertRedirect(
                $enderecoFormulario,
            )
            ->assertSessionHasErrors([
                'ligacao_redefinicao' => 'A ligação de redefinição é inválida ou já não está disponível. Solicita uma nova ligação.',
            ]);

        self::assertTrue(
            Hash::check(
                UtilizadorFactory::PALAVRA_PASSE_PREDEFINIDA,
                $utilizador
                    ->refresh()
                    ->password,
            ),
        );
    }

    /**
     * Obtém uma rota registada pelo respetivo nome.
     *
     * @param  string  $nome  Nome da rota.
     * @return RotaLaravel Rota registada.
     *
     * @since 2.0.0
     */
    private function obterRota(
        string $nome,
    ): RotaLaravel {
        $rota =
            Route::getRoutes()
                ->getByName(
                    $nome,
                );

        self::assertInstanceOf(
            RotaLaravel::class,
            $rota,
        );

        return $rota;
    }
}
