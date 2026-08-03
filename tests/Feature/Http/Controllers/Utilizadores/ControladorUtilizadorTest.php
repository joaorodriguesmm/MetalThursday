<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Utilizadores;

use App\Enumeracoes\PapelUtilizador;
use App\Models\Autenticacao\Utilizador;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa a listagem administrativa dos utilizadores.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
final class ControladorUtilizadorTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Prepara cada teste sem depender dos ficheiros produzidos pelo Vite.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    /**
     * Confirma que um visitante é encaminhado para o início de sessão.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function visitante_nao_pode_consultar_os_utilizadores(): void
    {
        $this
            ->get(
                route(
                    'utilizadores.indice',
                ),
            )
            ->assertRedirect(
                route(
                    'login',
                ),
            );
    }

    /**
     * Confirma que um utilizador comum não pode consultar a área.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function utilizador_comum_nao_pode_consultar_os_utilizadores(): void
    {
        $utilizador = Utilizador::factory()
            ->create();

        $this
            ->actingAs(
                $utilizador,
                'sessao',
            )
            ->get(
                route(
                    'utilizadores.indice',
                ),
            )
            ->assertForbidden();
    }

    /**
     * Confirma que um administrador não pode consultar a área reservada ao
     * superadministrador.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function administrador_nao_pode_consultar_os_utilizadores(): void
    {
        $administrador = Utilizador::factory()
            ->comPapel(
                PapelUtilizador::Administrador,
            )
            ->create();

        $this
            ->actingAs(
                $administrador,
                'sessao',
            )
            ->get(
                route(
                    'utilizadores.indice',
                ),
            )
            ->assertForbidden();
    }

    /**
     * Confirma que um superadministrador suspenso perde o acesso à área.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function superadministrador_suspenso_nao_pode_consultar_os_utilizadores(): void
    {
        $responsavel =
            $this->criarSuperAdministrador();

        $superAdministradorSuspenso = Utilizador::factory()
            ->comPapel(
                PapelUtilizador::SuperAdministrador,
            )
            ->suspensoPor(
                $responsavel,
                'Suspensão administrativa.',
            )
            ->create();

        $this
            ->actingAs(
                $superAdministradorSuspenso,
                'sessao',
            )
            ->get(
                route(
                    'utilizadores.indice',
                ),
            )
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
    }

    /**
     * Confirma que um superadministrador consulta a listagem.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function superadministrador_consulta_a_listagem(): void
    {
        $superAdministrador =
            $this->criarSuperAdministrador();

        $utilizador = Utilizador::factory()
            ->create([
                'nome' => 'Ana Metal',

                'email' => 'ana.metal@example.test',
            ]);

        $this
            ->actingAs(
                $superAdministrador,
                'sessao',
            )
            ->get(
                route(
                    'utilizadores.indice',
                ),
            )
            ->assertOk()
            ->assertViewIs(
                'utilizadores.indice',
            )
            ->assertSeeText(
                $utilizador->nome,
            )
            ->assertSeeText(
                $utilizador->email,
            )
            ->assertSeeText(
                PapelUtilizador::Utilizador->etiqueta(),
            )
            ->assertSeeText(
                'Ativo',
            )
            ->assertSeeText(
                'Verificado',
            );
    }

    /**
     * Confirma a pesquisa pelo nome.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function pesquisa_utilizadores_pelo_nome(): void
    {
        $superAdministrador =
            $this->criarSuperAdministrador();

        $encontrado = Utilizador::factory()
            ->create([
                'nome' => 'Beatriz Doom',

                'email' => 'beatriz.doom@example.test',
            ]);

        $excluido = Utilizador::factory()
            ->create([
                'nome' => 'Carlos Thrash',

                'email' => 'carlos.thrash@example.test',
            ]);

        $this
            ->actingAs(
                $superAdministrador,
                'sessao',
            )
            ->get(
                route(
                    'utilizadores.indice',
                    [
                        'pesquisa' => '  Beatriz   Doom  ',
                    ],
                ),
            )
            ->assertOk()
            ->assertSeeText(
                $encontrado->email,
            )
            ->assertDontSeeText(
                $excluido->email,
            );
    }

    /**
     * Confirma a pesquisa pelo endereço de e-mail.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function pesquisa_utilizadores_pelo_email(): void
    {
        $superAdministrador =
            $this->criarSuperAdministrador();

        $encontrado = Utilizador::factory()
            ->create([
                'nome' => 'Daniel Black',

                'email' => 'daniel.black@example.test',
            ]);

        $excluido = Utilizador::factory()
            ->create([
                'nome' => 'Eduardo Death',

                'email' => 'eduardo.death@example.test',
            ]);

        $this
            ->actingAs(
                $superAdministrador,
                'sessao',
            )
            ->get(
                route(
                    'utilizadores.indice',
                    [
                        'pesquisa' => 'daniel.black@example.test',
                    ],
                ),
            )
            ->assertOk()
            ->assertSeeText(
                $encontrado->nome,
            )
            ->assertDontSeeText(
                $excluido->email,
            );
    }

    /**
     * Confirma o filtro pelo papel.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function filtra_utilizadores_pelo_papel(): void
    {
        $superAdministrador =
            $this->criarSuperAdministrador();

        $administrador = Utilizador::factory()
            ->comPapel(
                PapelUtilizador::Administrador,
            )
            ->create([
                'email' => 'administrador@example.test',
            ]);

        $utilizador = Utilizador::factory()
            ->create([
                'email' => 'utilizador@example.test',
            ]);

        $this
            ->actingAs(
                $superAdministrador,
                'sessao',
            )
            ->get(
                route(
                    'utilizadores.indice',
                    [
                        'papel' => PapelUtilizador::Administrador->value,
                    ],
                ),
            )
            ->assertOk()
            ->assertSeeText(
                $administrador->email,
            )
            ->assertDontSeeText(
                $utilizador->email,
            )
            ->assertDontSeeText(
                $superAdministrador->email,
            );
    }

    /**
     * Confirma o filtro pelo estado de suspensão.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function filtra_utilizadores_pelo_estado_do_acesso(): void
    {
        $superAdministrador =
            $this->criarSuperAdministrador();

        $suspenso = Utilizador::factory()
            ->suspensoPor(
                $superAdministrador,
                'Suspensão administrativa.',
            )
            ->create([
                'email' => 'suspenso@example.test',
            ]);

        $ativo = Utilizador::factory()
            ->create([
                'email' => 'ativo@example.test',
            ]);

        $this
            ->actingAs(
                $superAdministrador,
                'sessao',
            )
            ->get(
                route(
                    'utilizadores.indice',
                    [
                        'estado' => 'suspenso',
                    ],
                ),
            )
            ->assertOk()
            ->assertSeeText(
                $suspenso->email,
            )
            ->assertDontSeeText(
                $ativo->email,
            )
            ->assertDontSeeText(
                $superAdministrador->email,
            )
            ->assertSeeText(
                'Suspenso',
            );
    }

    /**
     * Cria um superadministrador com acesso ativo.
     *
     * @return Utilizador Superadministrador criado.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
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
