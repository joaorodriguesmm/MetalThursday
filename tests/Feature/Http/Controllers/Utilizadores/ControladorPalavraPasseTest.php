<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Utilizadores;

use App\Enumeracoes\PapelUtilizador;
use App\Models\Autenticacao\Utilizador;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa os pedidos HTTP associados à alteração da palavra-passe.
 *
 * @since 2.0.0
 *
 * @version 2.0.0
 */
final class ControladorPalavraPasseTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Palavra-passe inicial utilizada nos testes.
     *
     * @var string
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const PALAVRA_PASSE_ATUAL =
        'PalavraPasse#Atual2026';

    /**
     * Nova palavra-passe válida utilizada nos testes.
     *
     * @var string
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const NOVA_PALAVRA_PASSE =
        'NovaPalavraPasse#2026';

    /**
     * Mensagem apresentada depois da atualização bem-sucedida.
     *
     * @var string
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const MENSAGEM_SUCESSO =
        'Palavra-passe atualizada com sucesso.';

    /**
     * Impede visitantes de alterarem a palavra-passe.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    #[Test]
    public function visitante_nao_pode_alterar_palavra_passe(): void
    {
        $resposta = $this->put(
            route(
                'perfil.palavra-passe.atualizar',
            ),
            [
                'palavra_passe_atual' => self::PALAVRA_PASSE_ATUAL,

                'nova_palavra_passe' => self::NOVA_PALAVRA_PASSE,

                'confirmacao_nova_palavra_passe' => self::NOVA_PALAVRA_PASSE,
            ],
        );

        $resposta->assertRedirect(
            route(
                'login',
            ),
        );

        $this->assertGuest(
            'sessao',
        );
    }

    /**
     * Altera a palavra-passe e mantém a sessão autenticada.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    #[Test]
    public function altera_palavra_passe_com_sucesso(): void
    {
        $utilizador = $this->criarUtilizador();

        $hashAnterior =
            $utilizador->getAuthPassword();

        $resposta = $this
            ->actingAs(
                $utilizador,
                'sessao',
            )
            ->from(
                route(
                    'perfil.editar',
                ),
            )
            ->put(
                route(
                    'perfil.palavra-passe.atualizar',
                ),
                [
                    'palavra_passe_atual' => self::PALAVRA_PASSE_ATUAL,

                    'nova_palavra_passe' => self::NOVA_PALAVRA_PASSE,

                    'confirmacao_nova_palavra_passe' => self::NOVA_PALAVRA_PASSE,
                ],
            );

        $resposta
            ->assertRedirect(
                route(
                    'perfil.editar',
                ),
            )
            ->assertSessionHas(
                'sucesso',
                self::MENSAGEM_SUCESSO,
            )
            ->assertSessionHasNoErrors();

        $this->assertAuthenticatedAs(
            $utilizador,
            'sessao',
        );

        $utilizador->refresh();

        $hashAtualizado =
            $utilizador->getAuthPassword();

        self::assertIsString(
            $hashAtualizado,
        );

        self::assertNotSame(
            $hashAnterior,
            $hashAtualizado,
        );

        self::assertTrue(
            Hash::check(
                self::NOVA_PALAVRA_PASSE,
                $hashAtualizado,
            ),
        );

        self::assertFalse(
            Hash::check(
                self::PALAVRA_PASSE_ATUAL,
                $hashAtualizado,
            ),
        );
    }

    /**
     * Rejeita uma palavra-passe atual incorreta.
     *
     * A palavra-passe persistida deve permanecer inalterada.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    #[Test]
    public function rejeita_palavra_passe_atual_incorreta(): void
    {
        $utilizador = $this->criarUtilizador();

        $hashAnterior =
            $utilizador->getAuthPassword();

        $resposta = $this
            ->actingAs(
                $utilizador,
                'sessao',
            )
            ->from(
                route(
                    'perfil.editar',
                ),
            )
            ->put(
                route(
                    'perfil.palavra-passe.atualizar',
                ),
                [
                    'palavra_passe_atual' => 'PalavraPasse#Incorreta2026',

                    'nova_palavra_passe' => self::NOVA_PALAVRA_PASSE,

                    'confirmacao_nova_palavra_passe' => self::NOVA_PALAVRA_PASSE,
                ],
            );

        $resposta
            ->assertRedirect(
                route(
                    'perfil.editar',
                ),
            )
            ->assertSessionHasErrors(
                [
                    'palavra_passe_atual',
                ],
                null,
                'palavraPasse',
            );

        $this->assertAuthenticatedAs(
            $utilizador,
            'sessao',
        );

        $utilizador->refresh();

        self::assertSame(
            $hashAnterior,
            $utilizador->getAuthPassword(),
        );

        self::assertTrue(
            Hash::check(
                self::PALAVRA_PASSE_ATUAL,
                $utilizador->getAuthPassword(),
            ),
        );
    }

    /**
     * Impede a reutilização da palavra-passe atual.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    #[Test]
    public function rejeita_nova_palavra_passe_igual_a_atual(): void
    {
        $utilizador = $this->criarUtilizador();

        $hashAnterior =
            $utilizador->getAuthPassword();

        $resposta = $this
            ->actingAs(
                $utilizador,
                'sessao',
            )
            ->from(
                route(
                    'perfil.editar',
                ),
            )
            ->put(
                route(
                    'perfil.palavra-passe.atualizar',
                ),
                [
                    'palavra_passe_atual' => self::PALAVRA_PASSE_ATUAL,

                    'nova_palavra_passe' => self::PALAVRA_PASSE_ATUAL,

                    'confirmacao_nova_palavra_passe' => self::PALAVRA_PASSE_ATUAL,
                ],
            );

        $resposta
            ->assertRedirect(
                route(
                    'perfil.editar',
                ),
            )
            ->assertSessionHasErrors(
                [
                    'nova_palavra_passe',
                ],
                null,
                'palavraPasse',
            );

        $this->assertAuthenticatedAs(
            $utilizador,
            'sessao',
        );

        $utilizador->refresh();

        self::assertSame(
            $hashAnterior,
            $utilizador->getAuthPassword(),
        );

        self::assertTrue(
            Hash::check(
                self::PALAVRA_PASSE_ATUAL,
                $utilizador->getAuthPassword(),
            ),
        );
    }

    /**
     * Rejeita uma nova palavra-passe que não cumpra os requisitos.
     *
     * Os erros devem ser colocados no saco exclusivo do formulário.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    #[Test]
    public function rejeita_nova_palavra_passe_insegura(): void
    {
        $utilizador = $this->criarUtilizador();

        $hashAnterior =
            $utilizador->getAuthPassword();

        $resposta = $this
            ->actingAs(
                $utilizador,
                'sessao',
            )
            ->from(
                route(
                    'perfil.editar',
                ),
            )
            ->put(
                route(
                    'perfil.palavra-passe.atualizar',
                ),
                [
                    'palavra_passe_atual' => self::PALAVRA_PASSE_ATUAL,

                    'nova_palavra_passe' => 'fraca',

                    'confirmacao_nova_palavra_passe' => 'fraca',
                ],
            );

        $resposta
            ->assertRedirect(
                route(
                    'perfil.editar',
                ),
            )
            ->assertSessionHasErrors(
                [
                    'nova_palavra_passe',
                ],
                null,
                'palavraPasse',
            );

        $this->assertAuthenticatedAs(
            $utilizador,
            'sessao',
        );

        $utilizador->refresh();

        self::assertSame(
            $hashAnterior,
            $utilizador->getAuthPassword(),
        );
    }

    /**
     * Rejeita uma confirmação diferente da nova palavra-passe.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    #[Test]
    public function rejeita_confirmacao_diferente(): void
    {
        $utilizador = $this->criarUtilizador();

        $hashAnterior =
            $utilizador->getAuthPassword();

        $resposta = $this
            ->actingAs(
                $utilizador,
                'sessao',
            )
            ->from(
                route(
                    'perfil.editar',
                ),
            )
            ->put(
                route(
                    'perfil.palavra-passe.atualizar',
                ),
                [
                    'palavra_passe_atual' => self::PALAVRA_PASSE_ATUAL,

                    'nova_palavra_passe' => self::NOVA_PALAVRA_PASSE,

                    'confirmacao_nova_palavra_passe' => 'OutraPalavraPasse#2026',
                ],
            );

        $resposta
            ->assertRedirect(
                route(
                    'perfil.editar',
                ),
            )
            ->assertSessionHasErrors(
                [
                    'confirmacao_nova_palavra_passe',
                ],
                null,
                'palavraPasse',
            );

        $this->assertAuthenticatedAs(
            $utilizador,
            'sessao',
        );

        $utilizador->refresh();

        self::assertSame(
            $hashAnterior,
            $utilizador->getAuthPassword(),
        );
    }

    /**
     * Rejeita o pedido quando os campos obrigatórios estão vazios.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    #[Test]
    public function rejeita_campos_obrigatorios_vazios(): void
    {
        $utilizador = $this->criarUtilizador();

        $hashAnterior =
            $utilizador->getAuthPassword();

        $resposta = $this
            ->actingAs(
                $utilizador,
                'sessao',
            )
            ->from(
                route(
                    'perfil.editar',
                ),
            )
            ->put(
                route(
                    'perfil.palavra-passe.atualizar',
                ),
                [
                    'palavra_passe_atual' => '',
                    'nova_palavra_passe' => '',
                    'confirmacao_nova_palavra_passe' => '',
                ],
            );

        $resposta
            ->assertRedirect(
                route(
                    'perfil.editar',
                ),
            )
            ->assertSessionHasErrors(
                [
                    'palavra_passe_atual',
                    'nova_palavra_passe',
                    'confirmacao_nova_palavra_passe',
                ],
                null,
                'palavraPasse',
            );

        $this->assertAuthenticatedAs(
            $utilizador,
            'sessao',
        );

        $utilizador->refresh();

        self::assertSame(
            $hashAnterior,
            $utilizador->getAuthPassword(),
        );
    }

    /**
     * Cria um utilizador persistido.
     *
     * @return Utilizador Utilizador criado.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function criarUtilizador(): Utilizador
    {
        $utilizador = new Utilizador;

        $utilizador->nome =
            'Utilizador Teste';

        $utilizador->email =
            'utilizador@exemplo.pt';

        $utilizador->password =
            self::PALAVRA_PASSE_ATUAL;

        $utilizador->papel =
            PapelUtilizador::Utilizador;

        $utilizador->email_verified_at =
            now()
                ->subDay()
                ->startOfSecond();

        $utilizador->saveOrFail();

        return $utilizador->refresh();
    }
}
