<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Utilizadores;

use App\Enumeracoes\PapelUtilizador;
use App\Models\Autenticacao\Convite;
use App\Models\Autenticacao\RegistoAcessoUtilizador;
use App\Models\Autenticacao\Utilizador;
use App\Servicos\Utilizadores\ServicoAcessoUtilizadores;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa a consulta administrativa dos utilizadores.
 *
 * @since 2.0.0
 *
 * @version 2.0.0
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
     * @version 2.0.0
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
            )
            ->assertSee(
                route(
                    'utilizadores.detalhes',
                    $utilizador,
                ),
                false,
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
     * Confirma que um visitante não pode consultar os detalhes.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function visitante_nao_pode_consultar_os_detalhes(): void
    {
        $utilizador = Utilizador::factory()
            ->create();

        $this
            ->get(
                route(
                    'utilizadores.detalhes',
                    $utilizador,
                ),
            )
            ->assertRedirect(
                route(
                    'login',
                ),
            );
    }

    /**
     * Confirma que um utilizador comum não pode consultar os detalhes de
     * outro utilizador.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function utilizador_comum_nao_pode_consultar_os_detalhes(): void
    {
        $utilizadorAutenticado = Utilizador::factory()
            ->create();

        $utilizadorConsultado = Utilizador::factory()
            ->create();

        $this
            ->actingAs(
                $utilizadorAutenticado,
                'sessao',
            )
            ->get(
                route(
                    'utilizadores.detalhes',
                    $utilizadorConsultado,
                ),
            )
            ->assertForbidden();
    }

    /**
     * Confirma os detalhes de um utilizador sem convite nem histórico.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function superadministrador_consulta_utilizador_ativo_sem_historico(): void
    {
        $superAdministrador =
            $this->criarSuperAdministrador();

        $utilizador = Utilizador::factory()
            ->create([
                'nome' => 'Fátima Progressive',

                'email' => 'fatima.progressive@example.test',
            ]);

        $this
            ->actingAs(
                $superAdministrador,
                'sessao',
            )
            ->get(
                route(
                    'utilizadores.detalhes',
                    $utilizador,
                ),
            )
            ->assertOk()
            ->assertViewIs(
                'utilizadores.detalhes',
            )
            ->assertSeeText(
                'Fátima Progressive',
            )
            ->assertSeeText(
                'fatima.progressive@example.test',
            )
            ->assertSeeText(
                'O utilizador possui acesso ativo à aplicação.',
            )
            ->assertSeeText(
                'Não existe um convite associado a este utilizador.',
            )
            ->assertSeeText(
                'Ainda não existem alterações do acesso.',
            );
    }

    /**
     * Confirma a apresentação da suspensão atual, do convite utilizado e do
     * histórico ordenado.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function apresenta_suspensao_convite_e_historico_ordenado(): void
    {
        $superAdministrador =
            $this->criarSuperAdministrador();

        $utilizador = Utilizador::factory()
            ->naoVerificado()
            ->create([
                'nome' => 'Gonçalo Sludge',

                'email' => 'goncalo.sludge@example.test',
            ]);

        $this->criarConviteUtilizado(
            $utilizador,
            $superAdministrador,
        );

        $servicoAcesso =
            $this->app->make(
                ServicoAcessoUtilizadores::class,
            );

        $utilizador = $servicoAcesso->suspender(
            $utilizador,
            $superAdministrador,
            'Primeira suspensão administrativa.',
            CarbonImmutable::parse(
                '2026-06-01 10:00:00',
            ),
        );

        $utilizador = $servicoAcesso->reativar(
            $utilizador,
            $superAdministrador,
            CarbonImmutable::parse(
                '2026-06-02 11:00:00',
            ),
        );

        $utilizador = $servicoAcesso->suspender(
            $utilizador,
            $superAdministrador,
            'Suspensão administrativa atual.',
            CarbonImmutable::parse(
                '2026-06-03 12:00:00',
            ),
        );

        $this
            ->actingAs(
                $superAdministrador,
                'sessao',
            )
            ->get(
                route(
                    'utilizadores.detalhes',
                    $utilizador,
                ),
            )
            ->assertOk()
            ->assertSeeText(
                'Por verificar',
            )
            ->assertSeeText(
                'O acesso deste utilizador encontra-se suspenso.',
            )
            ->assertSeeText(
                'Suspensão administrativa atual.',
            )
            ->assertSeeText(
                'Nome original do convite',
            )
            ->assertSeeText(
                'goncalo.sludge@example.test',
            )
            ->assertSeeText(
                $superAdministrador->nome,
            )
            ->assertSeeTextInOrder([
                'Suspensão administrativa atual.',
                'Sem motivo aplicável',
                'Primeira suspensão administrativa.',
            ]);
    }

    /**
     * Confirma que todas as relações administrativas necessárias são
     * carregadas antes da apresentação.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function carrega_explicitamente_as_relacoes_dos_detalhes(): void
    {
        $superAdministrador =
            $this->criarSuperAdministrador();

        $utilizador = Utilizador::factory()
            ->create();

        $this->criarConviteUtilizado(
            $utilizador,
            $superAdministrador,
        );

        $servicoAcesso =
            $this->app->make(
                ServicoAcessoUtilizadores::class,
            );

        $utilizador = $servicoAcesso->suspender(
            $utilizador,
            $superAdministrador,
            'Suspensão para testar as relações.',
        );

        $this
            ->actingAs(
                $superAdministrador,
                'sessao',
            )
            ->get(
                route(
                    'utilizadores.detalhes',
                    $utilizador,
                ),
            )
            ->assertOk()
            ->assertViewHas(
                'utilizador',
                static function (
                    mixed $valor,
                ): bool {
                    if (! $valor instanceof Utilizador) {
                        return false;
                    }

                    if (
                        ! $valor->relationLoaded(
                            'responsavelSuspensao',
                        )
                        || ! $valor->relationLoaded(
                            'registosAcesso',
                        )
                        || ! $valor->relationLoaded(
                            'conviteUtilizado',
                        )
                    ) {
                        return false;
                    }

                    $convite =
                        $valor->conviteUtilizado;

                    if (
                        ! $convite instanceof Convite
                        || ! $convite->relationLoaded(
                            'criador',
                        )
                    ) {
                        return false;
                    }

                    return $valor
                        ->registosAcesso
                        ->every(
                            static fn (
                                RegistoAcessoUtilizador $registo,
                            ): bool => $registo->relationLoaded(
                                'responsavel',
                            ),
                        );
                },
            );
    }

    /**
     * Confirma que um identificador inexistente devolve uma resposta 404.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function detalhes_de_utilizador_inexistente_devolvem_nao_encontrado(): void
    {
        $superAdministrador =
            $this->criarSuperAdministrador();

        $this
            ->actingAs(
                $superAdministrador,
                'sessao',
            )
            ->get(
                route(
                    'utilizadores.detalhes',
                    [
                        'utilizador' => 999999,
                    ],
                ),
            )
            ->assertNotFound();
    }

    /**
     * Cria um convite utilizado pelo utilizador indicado.
     *
     * @param  Utilizador  $utilizador  Utilizador associado ao convite.
     * @param  Utilizador  $criador  Criador do convite.
     * @return Convite Convite persistido.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function criarConviteUtilizado(
        Utilizador $utilizador,
        Utilizador $criador,
    ): Convite {
        $convite = new Convite([
            'nome_convidado' => 'Nome original do convite',

            'email_destino' => $utilizador->email,

            'expira_em' => CarbonImmutable::parse(
                '2026-12-31 23:59:59',
            ),
        ]);

        $convite->definirCodigo(
            'ConviteDetalhes2026',
        );

        $convite
            ->criador()
            ->associate(
                $criador,
            );

        $convite->utilizar(
            $utilizador,
            CarbonImmutable::parse(
                '2026-05-01 09:00:00',
            ),
        );

        $convite->saveOrFail();

        return $convite;
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
