<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Utilizadores;

use App\Enumeracoes\PapelUtilizador;
use App\Models\Autenticacao\Utilizador;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa os pedidos HTTP associados às permissões de e-mail.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
final class ControladorPermissoesEmailTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Impede visitantes de atualizarem as permissões de e-mail.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function visitante_nao_pode_atualizar_permissoes_email(): void
    {
        $resposta = $this->patch(
            route(
                'perfil.permissoes-email.atualizar',
            ),
            [
                'permissoes_email' => [],
            ],
        );

        $resposta->assertRedirect(
            route('login'),
        );
    }

    /**
     * Substitui as permissões atuais pelas permissões selecionadas.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function atualiza_permissoes_email_selecionadas(): void
    {
        $utilizador = $this->criarUtilizador();

        $permissaoAnterior =
            $this->criarPermissaoEmail(
                nome: 'Permissão anterior',
                slug: 'permissao-anterior',
            );

        $permissaoInteracoes =
            $this->criarPermissaoEmail(
                nome: 'Interações',
                slug: 'interacoes',
            );

        $permissaoMetalThursday =
            $this->criarPermissaoEmail(
                nome: 'Novas Metal Thursdays',
                slug: 'novas-metal-thursdays',
            );

        $utilizador
            ->permissoesEmail()
            ->sync([
                $permissaoAnterior,
            ]);

        $resposta = $this
            ->actingAs($utilizador)
            ->from(
                route('perfil.editar'),
            )
            ->patch(
                route(
                    'perfil.permissoes-email.atualizar',
                ),
                [
                    'permissoes_email' => [
                        $permissaoInteracoes,
                        $permissaoMetalThursday,
                    ],
                ],
            );

        $resposta
            ->assertRedirect(
                route('perfil.editar'),
            )
            ->assertSessionHas(
                'estado',
                'permissoes-email-atualizadas',
            )
            ->assertSessionHasNoErrors();

        $this->assertAuthenticatedAs(
            $utilizador,
            'web',
        );

        self::assertSame(
            [
                $permissaoInteracoes,
                $permissaoMetalThursday,
            ],
            $this->obterIdentificadoresPermissoes(
                $utilizador,
            ),
        );

        $this->assertDatabaseMissing(
            'email_permission_user',
            [
                'user_id' => $utilizador->getKey(),

                'email_permission_id' => $permissaoAnterior,
            ],
        );

        $this->assertDatabaseHas(
            'email_permission_user',
            [
                'user_id' => $utilizador->getKey(),

                'email_permission_id' => $permissaoInteracoes,
            ],
        );

        $this->assertDatabaseHas(
            'email_permission_user',
            [
                'user_id' => $utilizador->getKey(),

                'email_permission_id' => $permissaoMetalThursday,
            ],
        );
    }

    /**
     * Remove todas as permissões quando é enviada uma lista vazia.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function lista_vazia_remove_todas_as_permissoes(): void
    {
        $utilizador = $this->criarUtilizador();

        $primeiraPermissao =
            $this->criarPermissaoEmail(
                nome: 'Primeira permissão',
                slug: 'primeira-permissao',
            );

        $segundaPermissao =
            $this->criarPermissaoEmail(
                nome: 'Segunda permissão',
                slug: 'segunda-permissao',
            );

        $utilizador
            ->permissoesEmail()
            ->sync([
                $primeiraPermissao,
                $segundaPermissao,
            ]);

        self::assertSame(
            [
                $primeiraPermissao,
                $segundaPermissao,
            ],
            $this->obterIdentificadoresPermissoes(
                $utilizador,
            ),
        );

        $resposta = $this
            ->actingAs($utilizador)
            ->from(
                route('perfil.editar'),
            )
            ->patch(
                route(
                    'perfil.permissoes-email.atualizar',
                ),
                [
                    'permissoes_email' => [],
                ],
            );

        $resposta
            ->assertRedirect(
                route('perfil.editar'),
            )
            ->assertSessionHas(
                'estado',
                'permissoes-email-atualizadas',
            )
            ->assertSessionHasNoErrors();

        self::assertSame(
            [],
            $this->obterIdentificadoresPermissoes(
                $utilizador,
            ),
        );

        $this->assertDatabaseMissing(
            'email_permission_user',
            [
                'user_id' => $utilizador->getKey(),
            ],
        );
    }

    /**
     * Remove todas as permissões quando o campo não é enviado.
     *
     * Este comportamento representa um formulário em que nenhum checkbox
     * está selecionado.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function campo_ausente_remove_todas_as_permissoes(): void
    {
        $utilizador = $this->criarUtilizador();

        $permissaoExistente =
            $this->criarPermissaoEmail(
                nome: 'Permissão existente',
                slug: 'permissao-existente',
            );

        $utilizador
            ->permissoesEmail()
            ->sync([
                $permissaoExistente,
            ]);

        $resposta = $this
            ->actingAs($utilizador)
            ->from(
                route('perfil.editar'),
            )
            ->patch(
                route(
                    'perfil.permissoes-email.atualizar',
                ),
                [],
            );

        $resposta
            ->assertRedirect(
                route('perfil.editar'),
            )
            ->assertSessionHas(
                'estado',
                'permissoes-email-atualizadas',
            )
            ->assertSessionHasNoErrors();

        self::assertSame(
            [],
            $this->obterIdentificadoresPermissoes(
                $utilizador,
            ),
        );
    }

    /**
     * Rejeita uma permissão inexistente sem alterar as associações atuais.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function rejeita_permissao_inexistente_sem_alterar_associacoes(): void
    {
        $utilizador = $this->criarUtilizador();

        $permissaoExistente =
            $this->criarPermissaoEmail(
                nome: 'Permissão existente',
                slug: 'permissao-existente',
            );

        $utilizador
            ->permissoesEmail()
            ->sync([
                $permissaoExistente,
            ]);

        $identificadorInexistente =
            $permissaoExistente + 999999;

        $resposta = $this
            ->actingAs($utilizador)
            ->from(
                route('perfil.editar'),
            )
            ->patch(
                route(
                    'perfil.permissoes-email.atualizar',
                ),
                [
                    'permissoes_email' => [
                        $identificadorInexistente,
                    ],
                ],
            );

        $resposta
            ->assertRedirect(
                route('perfil.editar'),
            )
            ->assertSessionHasErrors(
                [
                    'permissoes_email.0',
                ],
                null,
                'permissoesEmail',
            );

        self::assertSame(
            [
                $permissaoExistente,
            ],
            $this->obterIdentificadoresPermissoes(
                $utilizador,
            ),
        );
    }

    /**
     * Rejeita valores que não sejam identificadores inteiros.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function rejeita_identificador_com_formato_invalido(): void
    {
        $utilizador = $this->criarUtilizador();

        $permissaoExistente =
            $this->criarPermissaoEmail(
                nome: 'Permissão existente',
                slug: 'permissao-existente',
            );

        $utilizador
            ->permissoesEmail()
            ->sync([
                $permissaoExistente,
            ]);

        $resposta = $this
            ->actingAs($utilizador)
            ->from(
                route('perfil.editar'),
            )
            ->patch(
                route(
                    'perfil.permissoes-email.atualizar',
                ),
                [
                    'permissoes_email' => [
                        'identificador-invalido',
                    ],
                ],
            );

        $resposta
            ->assertRedirect(
                route('perfil.editar'),
            )
            ->assertSessionHasErrors(
                [
                    'permissoes_email.0',
                ],
                null,
                'permissoesEmail',
            );

        self::assertSame(
            [
                $permissaoExistente,
            ],
            $this->obterIdentificadoresPermissoes(
                $utilizador,
            ),
        );
    }

    /**
     * Rejeita um valor que não seja uma lista.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function rejeita_permissoes_quando_nao_sao_uma_lista(): void
    {
        $utilizador = $this->criarUtilizador();

        $permissaoExistente =
            $this->criarPermissaoEmail(
                nome: 'Permissão existente',
                slug: 'permissao-existente',
            );

        $utilizador
            ->permissoesEmail()
            ->sync([
                $permissaoExistente,
            ]);

        $resposta = $this
            ->actingAs($utilizador)
            ->from(
                route('perfil.editar'),
            )
            ->patch(
                route(
                    'perfil.permissoes-email.atualizar',
                ),
                [
                    'permissoes_email' => 'valor-invalido',
                ],
            );

        $resposta
            ->assertRedirect(
                route('perfil.editar'),
            )
            ->assertSessionHasErrors(
                [
                    'permissoes_email',
                ],
                null,
                'permissoesEmail',
            );

        self::assertSame(
            [
                $permissaoExistente,
            ],
            $this->obterIdentificadoresPermissoes(
                $utilizador,
            ),
        );
    }

    /**
     * Rejeita permissões repetidas.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function rejeita_permissoes_repetidas(): void
    {
        $utilizador = $this->criarUtilizador();

        $permissaoExistente =
            $this->criarPermissaoEmail(
                nome: 'Permissão existente',
                slug: 'permissao-existente',
            );

        $utilizador
            ->permissoesEmail()
            ->sync([
                $permissaoExistente,
            ]);

        $resposta = $this
            ->actingAs($utilizador)
            ->from(
                route('perfil.editar'),
            )
            ->patch(
                route(
                    'perfil.permissoes-email.atualizar',
                ),
                [
                    'permissoes_email' => [
                        $permissaoExistente,
                        $permissaoExistente,
                    ],
                ],
            );

        $resposta
            ->assertRedirect(
                route('perfil.editar'),
            )
            ->assertSessionHasErrors(
                [
                    'permissoes_email.0',
                    'permissoes_email.1',
                ],
                null,
                'permissoesEmail',
            );

        self::assertSame(
            [
                $permissaoExistente,
            ],
            $this->obterIdentificadoresPermissoes(
                $utilizador,
            ),
        );
    }

    /**
     * Cria um utilizador persistido.
     *
     * @return Utilizador - Utilizador criado.
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
            'PalavraPasse#Segura2026';

        $utilizador->papel =
            PapelUtilizador::Utilizador;

        $utilizador->email_verified_at =
            now()->subDay()->startOfSecond();

        $utilizador->saveOrFail();

        return $utilizador->refresh();
    }

    /**
     * Cria uma permissão de e-mail.
     *
     * @param  string  $nome  Nome da permissão.
     * @param  string  $slug  Identificador textual.
     * @return int - Identificador criado.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function criarPermissaoEmail(
        string $nome,
        string $slug,
    ): int {
        return (int) DB::table(
            'email_permissions',
        )->insertGetId([
            'name' => $nome,
            'slug' => $slug,
            'description' => sprintf(
                'Permissão de teste: %s.',
                $slug,
            ),
        ]);
    }

    /**
     * Obtém os identificadores atuais das permissões do utilizador.
     *
     * @param  Utilizador  $utilizador  Utilizador consultado.
     * @return array<int, int> - Identificadores ordenados.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function obterIdentificadoresPermissoes(
        Utilizador $utilizador,
    ): array {
        return $utilizador
            ->permissoesEmail()
            ->pluck('email_permissions.id')
            ->map(
                static fn (
                    mixed $identificador,
                ): int => (int) $identificador,
            )
            ->sort()
            ->values()
            ->all();
    }
}
