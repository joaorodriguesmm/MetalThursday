<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Autenticacao;

use App\Enumeracoes\PapelUtilizador;
use App\Models\Autenticacao\Convite;
use App\Models\Autenticacao\Utilizador;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa a gestão administrativa dos convites.
 *
 * @since 2.0.0
 */
final class ControladorConviteTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Prepara cada teste.
     *
     * @since 2.0.0
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    /**
     * Repõe o relógio global.
     *
     * @since 2.0.0
     */
    protected function tearDown(): void
    {
        Date::setTestNow();

        parent::tearDown();
    }

    /**
     * Confirma que um visitante é redirecionado.
     *
     * @since 2.0.0
     */
    #[Test]
    public function visitante_nao_pode_consultar_convites(): void
    {
        $this
            ->get(
                route(
                    'convites.indice',
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
     */
    #[Test]
    public function utilizador_comum_nao_pode_consultar_convites(): void
    {
        $utilizador =
            Utilizador::factory()
                ->create();

        $this
            ->actingAs(
                $utilizador,
                'sessao',
            )
            ->get(
                route(
                    'convites.indice',
                ),
            )
            ->assertForbidden();
    }

    /**
     * Confirma que um administrador não pode consultar a área.
     *
     * @since 2.0.0
     */
    #[Test]
    public function administrador_nao_pode_consultar_convites(): void
    {
        $administrador =
            Utilizador::factory()
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
                    'convites.indice',
                ),
            )
            ->assertForbidden();
    }

    /**
     * Confirma que um administrador não pode criar convites.
     *
     * São protegidos tanto o formulário como o processamento da criação.
     *
     * @since 2.0.0
     */
    #[Test]
    public function administrador_nao_pode_criar_convites(): void
    {
        $administrador =
            Utilizador::factory()
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
                    'convites.criar',
                ),
            )
            ->assertForbidden();

        $this
            ->actingAs(
                $administrador,
                'sessao',
            )
            ->post(
                route(
                    'convites.guardar',
                ),
                [
                    'nome_convidado' => 'Convite não autorizado',
                ],
            )
            ->assertForbidden();

        $this->assertDatabaseCount(
            'convites',
            0,
        );
    }

    /**
     * Confirma a apresentação de todos os estados.
     *
     * @since 2.0.0
     */
    #[Test]
    public function superadministrador_consulta_a_listagem(): void
    {
        $momento =
            CarbonImmutable::parse(
                '2026-08-04 12:00:00',
            );

        Date::setTestNow(
            $momento,
        );

        $superAdministrador =
            $this->criarSuperAdministrador();

        $utilizador =
            Utilizador::factory()
                ->create([
                    'nome' => 'Utilizador do convite',
                ]);

        Convite::factory()
            ->criadoPor(
                $superAdministrador,
            )
            ->create([
                'nome_convidado' => 'Convite disponível',

                'email_destino' => 'disponivel@example.test',

                'expira_em' => $momento->addDay(),
            ]);

        Convite::factory()
            ->expirado()
            ->create([
                'nome_convidado' => 'Convite expirado',
            ]);

        Convite::factory()
            ->revogadoPor(
                $superAdministrador,
            )
            ->create([
                'nome_convidado' => 'Convite revogado',
            ]);

        Convite::factory()
            ->utilizadoPor(
                $utilizador,
            )
            ->create([
                'nome_convidado' => 'Convite utilizado',
            ]);

        $this
            ->actingAs(
                $superAdministrador,
                'sessao',
            )
            ->get(
                route(
                    'convites.indice',
                ),
            )
            ->assertOk()
            ->assertViewIs(
                'convites.indice',
            )
            ->assertSeeText(
                'Convite disponível',
            )
            ->assertSeeText(
                'Convite expirado',
            )
            ->assertSeeText(
                'Convite revogado',
            )
            ->assertSeeText(
                'Convite utilizado',
            )
            ->assertSeeText(
                'Disponível',
            )
            ->assertSeeText(
                'Expirado',
            )
            ->assertSeeText(
                'Revogado',
            )
            ->assertSeeText(
                'Utilizado',
            )
            ->assertSee(
                route(
                    'convites.criar',
                ),
                false,
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
     */
    #[Test]
    public function pesquisa_convites_pelo_nome(): void
    {
        $superAdministrador =
            $this->criarSuperAdministrador();

        Convite::factory()
            ->create([
                'nome_convidado' => 'Maria Doom',
            ]);

        Convite::factory()
            ->create([
                'nome_convidado' => 'Carlos Thrash',
            ]);

        $this
            ->actingAs(
                $superAdministrador,
                'sessao',
            )
            ->get(
                route(
                    'convites.indice',
                    [
                        'pesquisa' => '  Maria   Doom ',
                    ],
                ),
            )
            ->assertOk()
            ->assertSeeText(
                'Maria Doom',
            )
            ->assertDontSeeText(
                'Carlos Thrash',
            );
    }

    /**
     * Confirma a pesquisa pelo endereço.
     *
     * @since 2.0.0
     */
    #[Test]
    public function pesquisa_convites_pelo_email(): void
    {
        $superAdministrador =
            $this->criarSuperAdministrador();

        Convite::factory()
            ->create([
                'email_destino' => 'encontrado@example.test',
            ]);

        Convite::factory()
            ->create([
                'email_destino' => 'excluido@example.test',
            ]);

        $this
            ->actingAs(
                $superAdministrador,
                'sessao',
            )
            ->get(
                route(
                    'convites.indice',
                    [
                        'pesquisa' => 'encontrado@example.test',
                    ],
                ),
            )
            ->assertOk()
            ->assertSeeText(
                'encontrado@example.test',
            )
            ->assertDontSeeText(
                'excluido@example.test',
            );
    }

    /**
     * Confirma os filtros por todos os estados.
     *
     * @since 2.0.0
     */
    #[Test]
    public function filtra_convites_pelo_estado(): void
    {
        $momento =
            CarbonImmutable::parse(
                '2026-08-04 12:00:00',
            );

        Date::setTestNow(
            $momento,
        );

        $superAdministrador =
            $this->criarSuperAdministrador();

        $utilizador =
            Utilizador::factory()
                ->create();

        Convite::factory()
            ->create([
                'nome_convidado' => 'Estado disponível',

                'expira_em' => $momento->addDay(),
            ]);

        Convite::factory()
            ->expirado()
            ->create([
                'nome_convidado' => 'Estado expirado',
            ]);

        Convite::factory()
            ->revogadoPor(
                $superAdministrador,
            )
            ->create([
                'nome_convidado' => 'Estado revogado',
            ]);

        Convite::factory()
            ->utilizadoPor(
                $utilizador,
            )
            ->create([
                'nome_convidado' => 'Estado utilizado',
            ]);

        $estados = [
            'disponivel' => 'Estado disponível',

            'expirado' => 'Estado expirado',

            'revogado' => 'Estado revogado',

            'utilizado' => 'Estado utilizado',
        ];

        foreach ($estados as $estado => $nomeEsperado) {
            $resposta =
                $this
                    ->actingAs(
                        $superAdministrador,
                        'sessao',
                    )
                    ->get(
                        route(
                            'convites.indice',
                            [
                                'estado' => $estado,
                            ],
                        ),
                    );

            $resposta
                ->assertOk()
                ->assertSeeText(
                    $nomeEsperado,
                );

            foreach ($estados as $outroNome) {
                if ($outroNome === $nomeEsperado) {
                    continue;
                }

                $resposta->assertDontSeeText(
                    $outroNome,
                );
            }
        }
    }

    /**
     * Confirma o formulário de criação.
     *
     * @since 2.0.0
     */
    #[Test]
    public function apresenta_formulario_de_criacao(): void
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
                    'convites.criar',
                ),
            )
            ->assertOk()
            ->assertViewIs(
                'convites.criar',
            )
            ->assertSee(
                route(
                    'convites.guardar',
                ),
                false,
            );
    }

    /**
     * Confirma a validação dos dados obrigatórios.
     *
     * @since 2.0.0
     */
    #[Test]
    public function criacao_exige_nome_valido(): void
    {
        $superAdministrador =
            $this->criarSuperAdministrador();

        $this
            ->actingAs(
                $superAdministrador,
                'sessao',
            )
            ->from(
                route(
                    'convites.criar',
                ),
            )
            ->post(
                route(
                    'convites.guardar',
                ),
                [],
            )
            ->assertRedirect(
                route(
                    'convites.criar',
                ),
            )
            ->assertSessionHasErrorsIn(
                'criacao_convite',
                [
                    'nome_convidado',
                ],
            );

        $this->assertDatabaseCount(
            'convites',
            0,
        );
    }

    /**
     * Confirma a criação e a apresentação única da ligação.
     *
     * @since 2.0.0
     */
    #[Test]
    public function cria_convite_e_apresenta_a_ligacao_original(): void
    {
        $momento =
            CarbonImmutable::parse(
                '2026-08-04 12:00:00',
            );

        Date::setTestNow(
            $momento,
        );

        $superAdministrador =
            $this->criarSuperAdministrador();

        $resposta =
            $this
                ->actingAs(
                    $superAdministrador,
                    'sessao',
                )
                ->post(
                    route(
                        'convites.guardar',
                    ),
                    [
                        'nome_convidado' => '  Helena   Metal  ',

                        'email_destino' => '  HELENA@EXAMPLE.TEST  ',

                        'expira_em' => '2026-08-11T12:30',
                    ],
                );

        $resposta
            ->assertOk()
            ->assertViewIs(
                'convites.criado',
            )
            ->assertHeader(
                'Cache-Control',
                'no-store, private',
            )
            ->assertHeader(
                'Pragma',
                'no-cache',
            )
            ->assertHeader(
                'Referrer-Policy',
                'no-referrer',
            )
            ->assertHeader(
                'X-Robots-Tag',
                'noindex, nofollow, noarchive',
            )
            ->assertSeeText(
                'Helena Metal',
            );

        $conteudo =
            $resposta->getContent();

        self::assertIsString(
            $conteudo,
        );

        self::assertSame(
            1,
            preg_match(
                '/\/convites\/(MT-[A-Za-z0-9_-]+)/',
                $conteudo,
                $correspondencias,
            ),
        );

        $codigo =
            $correspondencias[1];

        $convite =
            Convite::query()
                ->sole();

        self::assertSame(
            'Helena Metal',
            $convite->nome_convidado,
        );

        self::assertSame(
            'helena@example.test',
            $convite->email_destino,
        );

        self::assertSame(
            (int) $superAdministrador->getKey(),
            $convite->criado_por_id,
        );

        self::assertTrue(
            $convite->correspondeAoCodigo(
                $codigo,
            ),
        );

        self::assertFalse(
            DB::table(
                'convites',
            )
                ->where(
                    'codigo_hash',
                    $codigo,
                )
                ->exists(),
        );
    }

    /**
     * Confirma que destinatário e expiração são opcionais.
     *
     * @since 2.0.0
     */
    #[Test]
    public function cria_convite_sem_email_nem_expiracao(): void
    {
        $superAdministrador =
            $this->criarSuperAdministrador();

        $this
            ->actingAs(
                $superAdministrador,
                'sessao',
            )
            ->post(
                route(
                    'convites.guardar',
                ),
                [
                    'nome_convidado' => 'Convite aberto',

                    'email_destino' => '',

                    'expira_em' => '',
                ],
            )
            ->assertOk();

        $convite =
            Convite::query()
                ->sole();

        self::assertNull(
            $convite->email_destino,
        );

        self::assertNull(
            $convite->expira_em,
        );
    }

    /**
     * Confirma que a revogação exige confirmação.
     *
     * @since 2.0.0
     */
    #[Test]
    public function revogacao_exige_confirmacao_explicita(): void
    {
        $superAdministrador =
            $this->criarSuperAdministrador();

        $convite =
            Convite::factory()
                ->create();

        $this
            ->actingAs(
                $superAdministrador,
                'sessao',
            )
            ->from(
                route(
                    'convites.indice',
                ),
            )
            ->patch(
                route(
                    'convites.revogar',
                    $convite,
                ),
                [],
            )
            ->assertRedirect(
                route(
                    'convites.indice',
                ),
            )
            ->assertSessionHasErrorsIn(
                'revogacao_convite',
                [
                    'confirmar_revogacao',
                ],
            );

        self::assertFalse(
            $convite
                ->refresh()
                ->foiRevogado(),
        );
    }

    /**
     * Confirma que um administrador não pode revogar convites.
     *
     * @since 2.0.0
     */
    #[Test]
    public function administrador_nao_pode_revogar_convites(): void
    {
        $administrador =
            Utilizador::factory()
                ->comPapel(
                    PapelUtilizador::Administrador,
                )
                ->create();

        $convite =
            Convite::factory()
                ->create();

        $this
            ->actingAs(
                $administrador,
                'sessao',
            )
            ->patch(
                route(
                    'convites.revogar',
                    $convite,
                ),
                [
                    'confirmar_revogacao' => '1',
                ],
            )
            ->assertForbidden();

        self::assertFalse(
            $convite
                ->refresh()
                ->foiRevogado(),
        );
    }

    /**
     * Confirma a revogação administrativa.
     *
     * @since 2.0.0
     */
    #[Test]
    public function superadministrador_revoga_convite(): void
    {
        $superAdministrador =
            $this->criarSuperAdministrador();

        $convite =
            Convite::factory()
                ->create([
                    'nome_convidado' => 'Convite revogável',
                ]);

        $this
            ->actingAs(
                $superAdministrador,
                'sessao',
            )
            ->patch(
                route(
                    'convites.revogar',
                    $convite,
                ),
                [
                    'confirmar_revogacao' => '1',
                ],
            )
            ->assertRedirect(
                route(
                    'convites.indice',
                ),
            )
            ->assertSessionHas(
                'sucesso',
                'O convite de Convite revogável foi revogado com sucesso.',
            );

        $convite->refresh();

        self::assertTrue(
            $convite->foiRevogado(),
        );

        self::assertSame(
            (int) $superAdministrador->getKey(),
            $convite->revogado_por_id,
        );
    }

    /**
     * Confirma que repetir a revogação não altera a auditoria.
     *
     * @since 2.0.0
     */
    #[Test]
    public function revogacao_repetida_e_idempotente(): void
    {
        $primeiroResponsavel =
            $this->criarSuperAdministrador();

        $segundoResponsavel =
            $this->criarSuperAdministrador();

        $convite =
            Convite::factory()
                ->revogadoPor(
                    $primeiroResponsavel,
                )
                ->create([
                    'nome_convidado' => 'Convite já revogado',
                ]);

        $momentoOriginal =
            $convite->revogado_em;

        $this
            ->actingAs(
                $segundoResponsavel,
                'sessao',
            )
            ->patch(
                route(
                    'convites.revogar',
                    $convite,
                ),
                [
                    'confirmar_revogacao' => '1',
                ],
            )
            ->assertRedirect(
                route(
                    'convites.indice',
                ),
            )
            ->assertSessionHas(
                'informacao',
                'O convite de Convite já revogado já se encontrava revogado.',
            );

        $convite->refresh();

        self::assertSame(
            (int) $primeiroResponsavel->getKey(),
            $convite->revogado_por_id,
        );

        self::assertTrue(
            $convite
                ->revogado_em
                ?->equalTo(
                    $momentoOriginal,
                )
                ?? false,
        );
    }

    /**
     * Confirma que um convite utilizado não pode ser revogado.
     *
     * @since 2.0.0
     */
    #[Test]
    public function convite_utilizado_nao_pode_ser_revogado(): void
    {
        $superAdministrador =
            $this->criarSuperAdministrador();

        $utilizador =
            Utilizador::factory()
                ->create();

        $convite =
            Convite::factory()
                ->utilizadoPor(
                    $utilizador,
                )
                ->create();

        $this
            ->actingAs(
                $superAdministrador,
                'sessao',
            )
            ->patch(
                route(
                    'convites.revogar',
                    $convite,
                ),
                [
                    'confirmar_revogacao' => '1',
                ],
            )
            ->assertForbidden();
    }

    /**
     * Cria um superadministrador ativo.
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
