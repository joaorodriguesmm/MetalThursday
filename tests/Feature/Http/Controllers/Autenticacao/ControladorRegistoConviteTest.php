<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Autenticacao;

use App\Enumeracoes\IdentificadorPermissaoEmail;
use App\Enumeracoes\PapelUtilizador;
use App\Models\Autenticacao\Utilizador;
use App\Models\Comunicacao\PermissaoEmail;
use App\Servicos\Autenticacao\ServicoConvites;
use Database\Seeders\PermissaoEmailSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ControladorRegistoConviteTest extends TestCase
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

    #[Test]
    public function apresenta_permissao_global_e_permissoes_individuais(): void
    {
        $criador =
            Utilizador::factory()
                ->create();

        $resultado =
            app(
                ServicoConvites::class,
            )->criar(
                nomeConvidado: 'Novo Utilizador',
                emailDestino: null,
                criador: $criador,
                expiraEm: null,
            );

        $this->seed(
            PermissaoEmailSeeder::class,
        );

        $resposta =
            $this->get(
                route(
                    'convites.aceitar',
                    [
                        'codigoConvite' => $resultado->obterCodigo(),
                    ],
                ),
            );

        $resposta
            ->assertOk()
            ->assertSee(
                'Todas as notificações',
            )
            ->assertSee(
                'data-permissao-email-todas',
                escape: false,
            );

        $resposta
            ->assertSee(IdentificadorPermissaoEmail::DecisoesDenunciasComentarios->nome())
            ->assertDontSee(IdentificadorPermissaoEmail::NovasDenunciasComentarios->nome());

        $conteudo =
            $resposta->getContent();

        self::assertIsString(
            $conteudo,
        );

        self::assertSame(
            1,
            substr_count(
                $conteudo,
                'data-permissao-email-todas',
            ),
        );

        self::assertSame(
            count(IdentificadorPermissaoEmail::cases()) - 2,
            substr_count(
                $conteudo,
                'data-permissao-email-individual',
            ),
        );
    }

    /**
     * Confirma o limite HTTP aplicado às tentativas de registo por convite.
     *
     * Os pedidos utilizam dados inválidos para isolar exclusivamente o
     * middleware de limitação da rota.
     *
     * @since 2.0.0
     */
    #[Test]
    public function limita_pedidos_http_de_registo_por_convite(): void
    {
        $this->withServerVariables([
            'REMOTE_ADDR' => '203.0.113.22',
        ]);

        for ($pedido = 0; $pedido < 6; $pedido++) {
            $this
                ->post(
                    route(
                        'convites.registar',
                    ),
                    [],
                )
                ->assertStatus(
                    302,
                );
        }

        $this
            ->post(
                route(
                    'convites.registar',
                ),
                [],
            )
            ->assertStatus(
                429,
            );
    }

    #[Test]
    public function convite_inexistente_nao_revela_se_email_ja_existe(): void
    {
        $codigoConvite =
            'MT-Convite-Inexistente-123456';

        Utilizador::factory()
            ->create([
                'email' => 'existente@exemplo.pt',
            ]);

        $resposta =
            $this->post(
                route(
                    'convites.registar',
                ),
                [
                    'codigo_convite' => $codigoConvite,
                    'nome' => 'Novo Utilizador',
                    'email' => 'existente@exemplo.pt',
                    'palavra_passe' => 'MetalThursday#2026',
                    'confirmacao_palavra_passe' => 'MetalThursday#2026',
                    'permissoes_email' => [],
                ],
            );

        $resposta
            ->assertRedirect(
                route(
                    'convites.aceitar',
                    [
                        'codigoConvite' => $codigoConvite,
                    ],
                ),
            )
            ->assertSessionDoesntHaveErrors([
                'email',
            ])
            ->assertSessionHas(
                'erro',
                'Não foi possível concluir o registo. Confirma os dados e verifica se o convite continua disponível.',
            );
    }

    /**
     * Rejeita um ficheiro PHP apresentado como fotografia JPEG
     * durante o registo por convite.
     *
     * O convite deve permanecer disponível, o utilizador não deve
     * ser criado e nenhum ficheiro deve ser armazenado.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_ficheiro_php_disfarcado_de_fotografia_no_registo(): void
    {
        Storage::fake(
            'publico',
        );

        $criador =
            Utilizador::factory()
                ->create();

        $resultado =
            app(
                ServicoConvites::class,
            )->criar(
                nomeConvidado: 'Novo Utilizador',
                emailDestino: 'convidado@exemplo.pt',
                criador: $criador,
                expiraEm: null,
            );

        $codigoConvite =
            $resultado->obterCodigo();

        $caminhoTemporario = tempnam(
            sys_get_temp_dir(),
            'metal-b19-',
        );

        self::assertIsString(
            $caminhoTemporario,
        );

        try {
            $bytesEscritos = file_put_contents(
                $caminhoTemporario,
                '<?php echo "AUDITORIA_B19_15"; ?>',
            );

            self::assertNotFalse(
                $bytesEscritos,
            );

            $ficheiroMalicioso = new UploadedFile(
                $caminhoTemporario,
                'fotografia.jpg',
                'image/jpeg',
                UPLOAD_ERR_OK,
                true,
            );

            self::assertNotSame(
                'image/jpeg',
                $ficheiroMalicioso->getMimeType(),
            );

            $resposta = $this
                ->from(
                    route(
                        'convites.aceitar',
                        [
                            'codigoConvite' => $codigoConvite,
                        ],
                    ),
                )
                ->post(
                    route(
                        'convites.registar',
                    ),
                    [
                        'codigo_convite' => $codigoConvite,
                        'nome' => 'Novo Utilizador',
                        'email' => 'convidado@exemplo.pt',
                        'palavra_passe' => 'MetalThursday#2026',
                        'confirmacao_palavra_passe' => 'MetalThursday#2026',
                        'permissoes_email' => [],
                        'fotografia' => $ficheiroMalicioso,
                    ],
                );

            $resposta
                ->assertRedirect(
                    route(
                        'convites.aceitar',
                        [
                            'codigoConvite' => $codigoConvite,
                        ],
                    ),
                )
                ->assertSessionHasErrors(
                    [
                        'fotografia',
                    ],
                )
                ->assertSessionDoesntHaveErrors(
                    [
                        'codigo_convite',
                        'nome',
                        'email',
                        'palavra_passe',
                        'confirmacao_palavra_passe',
                        'permissoes_email',
                    ],
                );

            $this->assertDatabaseMissing(
                'utilizadores',
                [
                    'email' => 'convidado@exemplo.pt',
                ],
            );

            $this->assertDatabaseHas(
                'convites',
                [
                    'id' => $resultado->obterConvite()->getKey(),
                    'utilizado_por_id' => null,
                    'utilizado_em' => null,
                ],
            );

            self::assertSame(
                [],
                Storage::disk(
                    'publico',
                )->allFiles(
                    'fotografias/utilizadores',
                ),
            );
        } finally {
            if (is_file($caminhoTemporario)) {
                unlink(
                    $caminhoTemporario,
                );
            }
        }
    }

    #[Test]
    public function registo_rejeita_preferencia_reservada_sem_consumir_convite(): void
    {
        Notification::fake();

        $this->seed(PermissaoEmailSeeder::class);

        $criador = Utilizador::factory()->create();
        $email = 'convidado@exemplo.pt';

        $resultado = app(ServicoConvites::class)->criar(
            nomeConvidado: 'Novo Utilizador',
            emailDestino: $email,
            criador: $criador,
            expiraEm: null,
        );

        $convite = $resultado->obterConvite();

        $nova = PermissaoEmail::query()
            ->where(
                'identificador',
                IdentificadorPermissaoEmail::NovasDenunciasComentarios->value,
            )
            ->sole();

        $decisao = PermissaoEmail::query()
            ->where(
                'identificador',
                IdentificadorPermissaoEmail::DecisoesDenunciasComentarios->value,
            )
            ->sole();

        $dados = [
            'codigo_convite' => $resultado->obterCodigo(),
            'nome' => 'Novo Utilizador',
            'email' => $email,
            'palavra_passe' => 'MetalThursday#2026',
            'confirmacao_palavra_passe' => 'MetalThursday#2026',
            'permissoes_email' => [$nova->id, $decisao->id],
        ];

        $this->postJson(route('convites.registar'), $dados)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('permissoes_email.0');

        $this->assertDatabaseMissing('utilizadores', [
            'email' => $email,
        ]);

        $this->assertDatabaseHas('convites', [
            'id' => $convite->getKey(),
            'utilizado_por_id' => null,
            'utilizado_em' => null,
        ]);

        Notification::assertNothingSent();

        $dados['permissoes_email'] = [$decisao->id];

        $this->post(route('convites.registar'), $dados)
            ->assertRedirect(route('login'))
            ->assertSessionHasNoErrors();

        $utilizador = Utilizador::query()
            ->where('email', $email)
            ->sole();

        self::assertSame(
            PapelUtilizador::Utilizador,
            $utilizador->papel,
        );

        $this->assertDatabaseHas('convites', [
            'id' => $convite->getKey(),
            'utilizado_por_id' => $utilizador->id,
        ]);

        $this->assertDatabaseHas('permissao_email_utilizador', [
            'utilizador_id' => $utilizador->id,
            'permissao_email_id' => $decisao->id,
        ]);

        $this->assertDatabaseMissing('permissao_email_utilizador', [
            'utilizador_id' => $utilizador->id,
            'permissao_email_id' => $nova->id,
        ]);

        self::assertSame(1, $utilizador->permissoesEmail()->count());
    }
}
