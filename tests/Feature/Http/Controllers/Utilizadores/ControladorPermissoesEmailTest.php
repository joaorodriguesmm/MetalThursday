<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Utilizadores;

use App\Enumeracoes\PapelUtilizador;
use App\Models\Autenticacao\Utilizador;
use App\Models\Comunicacao\PermissaoEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa os pedidos HTTP associados às permissões de e-mail.
 *
 * @since 2.0.0
 */
final class ControladorPermissoesEmailTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Mensagem apresentada depois da atualização das permissões.
     *
     * @var string
     *
     * @since 2.0.0
     */
    private const MENSAGEM_SUCESSO =
        'Permissões de e-mail atualizadas com sucesso.';

    /**
     * Impede visitantes de atualizarem as permissões de e-mail.
     *
     * @since 2.0.0
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
            route(
                'login',
            ),
        );

        $this->assertGuest(
            'sessao',
        );
    }

    /**
     * Substitui as permissões atuais pelas permissões selecionadas.
     *
     * @since 2.0.0
     */
    #[Test]
    public function atualiza_permissoes_email_selecionadas(): void
    {
        $utilizador =
            $this->criarUtilizador();

        $permissaoAnterior =
            $this->criarPermissaoEmail(
                nome: 'Permissão anterior',
                identificador: 'permissao_anterior',
                ordem: 1,
            );

        $permissaoInteracoes =
            $this->criarPermissaoEmail(
                nome: 'Interações',
                identificador: 'interacoes',
                ordem: 2,
            );

        $permissaoMetalThursday =
            $this->criarPermissaoEmail(
                nome: 'Novas Metal Thursdays',
                identificador: 'novas_metal_thursdays',
                ordem: 3,
            );

        $identificadorPermissaoAnterior =
            (int) $permissaoAnterior->getKey();

        $identificadorPermissaoInteracoes =
            (int) $permissaoInteracoes->getKey();

        $identificadorPermissaoMetalThursday =
            (int) $permissaoMetalThursday->getKey();

        $utilizador
            ->permissoesEmail()
            ->sync([
                $identificadorPermissaoAnterior,
            ]);

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
            ->patch(
                route(
                    'perfil.permissoes-email.atualizar',
                ),
                [
                    'permissoes_email' => [
                        $identificadorPermissaoInteracoes,
                        $identificadorPermissaoMetalThursday,
                    ],
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

        self::assertSame(
            [
                $identificadorPermissaoInteracoes,
                $identificadorPermissaoMetalThursday,
            ],
            $this->obterIdentificadoresPermissoes(
                $utilizador,
            ),
        );

        $this->assertDatabaseMissing(
            'permissao_email_utilizador',
            [
                'utilizador_id' => $utilizador->getKey(),

                'permissao_email_id' => $identificadorPermissaoAnterior,
            ],
        );

        $this->assertDatabaseHas(
            'permissao_email_utilizador',
            [
                'utilizador_id' => $utilizador->getKey(),

                'permissao_email_id' => $identificadorPermissaoInteracoes,
            ],
        );

        $this->assertDatabaseHas(
            'permissao_email_utilizador',
            [
                'utilizador_id' => $utilizador->getKey(),

                'permissao_email_id' => $identificadorPermissaoMetalThursday,
            ],
        );
    }

    /**
     * Remove todas as permissões quando é enviada uma lista vazia.
     *
     * @since 2.0.0
     */
    #[Test]
    public function lista_vazia_remove_todas_as_permissoes(): void
    {
        $utilizador =
            $this->criarUtilizador();

        $primeiraPermissao =
            $this->criarPermissaoEmail(
                nome: 'Primeira permissão',
                identificador: 'primeira_permissao',
                ordem: 1,
            );

        $segundaPermissao =
            $this->criarPermissaoEmail(
                nome: 'Segunda permissão',
                identificador: 'segunda_permissao',
                ordem: 2,
            );

        $identificadorPrimeiraPermissao =
            (int) $primeiraPermissao->getKey();

        $identificadorSegundaPermissao =
            (int) $segundaPermissao->getKey();

        $utilizador
            ->permissoesEmail()
            ->sync([
                $identificadorPrimeiraPermissao,
                $identificadorSegundaPermissao,
            ]);

        self::assertSame(
            [
                $identificadorPrimeiraPermissao,
                $identificadorSegundaPermissao,
            ],
            $this->obterIdentificadoresPermissoes(
                $utilizador,
            ),
        );

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

        self::assertSame(
            [],
            $this->obterIdentificadoresPermissoes(
                $utilizador,
            ),
        );

        $this->assertDatabaseMissing(
            'permissao_email_utilizador',
            [
                'utilizador_id' => $utilizador->getKey(),
            ],
        );
    }

    /**
     * Remove todas as permissões quando o campo não é enviado.
     *
     * Este comportamento representa um formulário em que nenhum campo de
     * seleção está marcado.
     *
     * @since 2.0.0
     */
    #[Test]
    public function campo_ausente_remove_todas_as_permissoes(): void
    {
        $utilizador =
            $this->criarUtilizador();

        $permissaoExistente =
            $this->criarPermissaoEmail(
                nome: 'Permissão existente',
                identificador: 'permissao_existente',
                ordem: 1,
            );

        $identificadorPermissaoExistente =
            (int) $permissaoExistente->getKey();

        $utilizador
            ->permissoesEmail()
            ->sync([
                $identificadorPermissaoExistente,
            ]);

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
            ->patch(
                route(
                    'perfil.permissoes-email.atualizar',
                ),
                [],
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

        self::assertSame(
            [],
            $this->obterIdentificadoresPermissoes(
                $utilizador,
            ),
        );

        $this->assertDatabaseMissing(
            'permissao_email_utilizador',
            [
                'utilizador_id' => $utilizador->getKey(),
            ],
        );
    }

    /**
     * Rejeita uma permissão inexistente sem alterar as associações atuais.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_permissao_inexistente_sem_alterar_associacoes(): void
    {
        $utilizador =
            $this->criarUtilizador();

        $permissaoExistente =
            $this->criarPermissaoEmail(
                nome: 'Permissão existente',
                identificador: 'permissao_existente',
                ordem: 1,
            );

        $identificadorPermissaoExistente =
            (int) $permissaoExistente->getKey();

        $utilizador
            ->permissoesEmail()
            ->sync([
                $identificadorPermissaoExistente,
            ]);

        $identificadorInexistente =
            $identificadorPermissaoExistente + 999999;

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
                route(
                    'perfil.editar',
                ),
            )
            ->assertSessionHasErrors(
                [
                    'permissoes_email.0',
                ],
                null,
                'permissoesEmail',
            );

        $this->assertAuthenticatedAs(
            $utilizador,
            'sessao',
        );

        self::assertSame(
            [
                $identificadorPermissaoExistente,
            ],
            $this->obterIdentificadoresPermissoes(
                $utilizador,
            ),
        );

        $this->assertDatabaseHas(
            'permissao_email_utilizador',
            [
                'utilizador_id' => $utilizador->getKey(),

                'permissao_email_id' => $identificadorPermissaoExistente,
            ],
        );
    }

    /**
     * Rejeita valores que não sejam identificadores inteiros.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_identificador_com_formato_invalido(): void
    {
        $utilizador =
            $this->criarUtilizador();

        $permissaoExistente =
            $this->criarPermissaoEmail(
                nome: 'Permissão existente',
                identificador: 'permissao_existente',
                ordem: 1,
            );

        $identificadorPermissaoExistente =
            (int) $permissaoExistente->getKey();

        $utilizador
            ->permissoesEmail()
            ->sync([
                $identificadorPermissaoExistente,
            ]);

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
                route(
                    'perfil.editar',
                ),
            )
            ->assertSessionHasErrors(
                [
                    'permissoes_email.0',
                ],
                null,
                'permissoesEmail',
            );

        $this->assertAuthenticatedAs(
            $utilizador,
            'sessao',
        );

        self::assertSame(
            [
                $identificadorPermissaoExistente,
            ],
            $this->obterIdentificadoresPermissoes(
                $utilizador,
            ),
        );
    }

    /**
     * Rejeita um valor que não seja uma lista.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_permissoes_quando_nao_sao_uma_lista(): void
    {
        $utilizador =
            $this->criarUtilizador();

        $permissaoExistente =
            $this->criarPermissaoEmail(
                nome: 'Permissão existente',
                identificador: 'permissao_existente',
                ordem: 1,
            );

        $identificadorPermissaoExistente =
            (int) $permissaoExistente->getKey();

        $utilizador
            ->permissoesEmail()
            ->sync([
                $identificadorPermissaoExistente,
            ]);

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
                route(
                    'perfil.editar',
                ),
            )
            ->assertSessionHasErrors(
                [
                    'permissoes_email',
                ],
                null,
                'permissoesEmail',
            );

        $this->assertAuthenticatedAs(
            $utilizador,
            'sessao',
        );

        self::assertSame(
            [
                $identificadorPermissaoExistente,
            ],
            $this->obterIdentificadoresPermissoes(
                $utilizador,
            ),
        );
    }

    /**
     * Rejeita permissões repetidas.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_permissoes_repetidas(): void
    {
        $utilizador =
            $this->criarUtilizador();

        $permissaoExistente =
            $this->criarPermissaoEmail(
                nome: 'Permissão existente',
                identificador: 'permissao_existente',
                ordem: 1,
            );

        $identificadorPermissaoExistente =
            (int) $permissaoExistente->getKey();

        $utilizador
            ->permissoesEmail()
            ->sync([
                $identificadorPermissaoExistente,
            ]);

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
            ->patch(
                route(
                    'perfil.permissoes-email.atualizar',
                ),
                [
                    'permissoes_email' => [
                        $identificadorPermissaoExistente,
                        $identificadorPermissaoExistente,
                    ],
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
                    'permissoes_email.0',
                    'permissoes_email.1',
                ],
                null,
                'permissoesEmail',
            );

        $this->assertAuthenticatedAs(
            $utilizador,
            'sessao',
        );

        self::assertSame(
            [
                $identificadorPermissaoExistente,
            ],
            $this->obterIdentificadoresPermissoes(
                $utilizador,
            ),
        );
    }

    /**
     * Cria um utilizador persistido.
     *
     * @return Utilizador Utilizador criado.
     *
     * @since 2.0.0
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
            now()
                ->subDay()
                ->startOfSecond();

        $utilizador->saveOrFail();

        return $utilizador->refresh();
    }

    /**
     * Cria uma permissão de e-mail persistida.
     *
     * @param  string  $nome  Nome apresentado ao utilizador.
     * @param  string  $identificador  Identificador técnico da permissão.
     * @param  int  $ordem  Ordem de apresentação.
     * @return PermissaoEmail Permissão criada.
     *
     * @since 2.0.0
     */
    private function criarPermissaoEmail(
        string $nome,
        string $identificador,
        int $ordem,
    ): PermissaoEmail {
        $permissao = new PermissaoEmail;

        $permissao->nome =
            $nome;

        $permissao->identificador =
            $identificador;

        $permissao->descricao =
            sprintf(
                'Permissão de teste: %s.',
                $identificador,
            );

        $permissao->ordem =
            $ordem;

        $permissao->saveOrFail();

        return $permissao->refresh();
    }

    /**
     * Obtém os identificadores atuais das permissões do utilizador.
     *
     * @param  Utilizador  $utilizador  Utilizador consultado.
     * @return list<int> Identificadores ordenados.
     *
     * @since 2.0.0
     */
    private function obterIdentificadoresPermissoes(
        Utilizador $utilizador,
    ): array {
        return $utilizador
            ->permissoesEmail()
            ->pluck(
                'permissoes_email.id',
            )
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
