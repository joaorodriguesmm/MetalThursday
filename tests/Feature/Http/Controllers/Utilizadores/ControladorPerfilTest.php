<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Utilizadores;

use App\Enumeracoes\PapelUtilizador;
use App\Models\Autenticacao\Utilizador;
use App\Models\Comunicacao\PermissaoEmail;
use App\Notifications\NotificacaoVerificacaoEmail;
use Carbon\CarbonImmutable;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\Events\NotificationSending;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

/**
 * Testa os pedidos HTTP associados aos dados gerais do perfil.
 *
 * @since 2.0.0
 */
final class ControladorPerfilTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Mensagem apresentada depois de atualizar o perfil sem alterar o e-mail.
     *
     * @var string
     *
     * @since 2.0.0
     */
    private const MENSAGEM_PERFIL_ATUALIZADO =
        'O perfil foi atualizado com sucesso.';

    /**
     * Mensagem apresentada depois de alterar o endereço de e-mail.
     *
     * @var string
     *
     * @since 2.0.0
     */
    private const MENSAGEM_EMAIL_ALTERADO =
        'O perfil foi atualizado. Verifica o novo endereço de e-mail antes de iniciares sessão novamente.';

    /**
     * Disco público falso utilizado pelos testes.
     *
     * @since 2.0.0
     */
    private FilesystemAdapter $discoPublico;

    /**
     * Prepara cada teste.
     *
     * @since 2.0.0
     */
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(
            'publico',
        );

        /** @var FilesystemAdapter $discoPublico */
        $discoPublico = Storage::disk(
            'publico',
        );

        $this->discoPublico =
            $discoPublico;

        /*
         * Evita que os testes das vistas dependam dos ficheiros produzidos
         * pelo Vite.
         */
        $this->withoutVite();
    }

    /**
     * Impede visitantes de aceder à edição do perfil.
     *
     * @since 2.0.0
     */
    #[Test]
    public function visitante_nao_pode_aceder_ao_perfil(): void
    {
        $resposta = $this->get(
            route(
                'perfil.editar',
            ),
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
     * Apresenta a página com o utilizador e as permissões de e-mail.
     *
     * As permissões devem respeitar a ordem persistida e indicar quais estão
     * selecionadas e qual representa todas as comunicações.
     *
     * @since 2.0.0
     */
    #[Test]
    public function apresenta_pagina_de_edicao_do_perfil(): void
    {
        $utilizador = $this->criarUtilizador();

        $permissaoTodas =
            $this->criarPermissaoEmail(
                nome: 'Todas',
                identificador: 'todas_notificacoes',
                ordem: 1,
            );

        $permissaoNovidades =
            $this->criarPermissaoEmail(
                nome: 'Novidades',
                identificador: 'novidades',
                ordem: 2,
            );

        $identificadorPermissaoTodas =
            $permissaoTodas->getKey();

        self::assertIsInt(
            $identificadorPermissaoTodas,
        );

        $identificadorPermissaoNovidades =
            $permissaoNovidades->getKey();

        self::assertIsInt(
            $identificadorPermissaoNovidades,
        );

        $utilizador
            ->permissoesEmail()
            ->sync([
                $identificadorPermissaoNovidades,
            ]);

        $resposta = $this
            ->actingAs(
                $utilizador,
                'sessao',
            )
            ->get(
                route(
                    'perfil.editar',
                ),
            );

        $resposta
            ->assertOk()
            ->assertViewIs(
                'utilizadores.perfil.editar',
            )
            ->assertViewHas(
                'utilizador',
                static fn (
                    mixed $valor,
                ): bool => (
                    $valor instanceof Utilizador
                    && $valor->is(
                        $utilizador,
                    )
                ),
            )
            ->assertViewHas(
                'permissoesEmailFormulario',
                [
                    [
                        'identificador' => $identificadorPermissaoTodas,

                        'nome' => 'Todas',

                        'descricao' => 'Permissão de teste: todas_notificacoes.',

                        'ePermissaoTodas' => true,

                        'selecionada' => false,
                    ],

                    [
                        'identificador' => $identificadorPermissaoNovidades,

                        'nome' => 'Novidades',

                        'descricao' => 'Permissão de teste: novidades.',

                        'ePermissaoTodas' => false,

                        'selecionada' => true,
                    ],
                ],
            );

        $this->assertAuthenticatedAs(
            $utilizador,
            'sessao',
        );
    }

    /**
     * Atualiza o nome sem terminar a sessão quando o e-mail não muda.
     *
     * @since 2.0.0
     */
    #[Test]
    public function atualiza_perfil_sem_alterar_email(): void
    {
        Notification::fake();

        $utilizador = $this->criarUtilizador(
            nome: 'Nome Original',
            email: 'utilizador@exemplo.pt',
            emailVerificado: true,
        );

        $dataVerificacaoOriginal =
            $utilizador->email_verified_at;

        $resposta = $this
            ->actingAs(
                $utilizador,
                'sessao',
            )
            ->patch(
                route(
                    'perfil.atualizar',
                ),
                [
                    'nome' => '  João   Rodrigues  ',

                    'email' => 'UTILIZADOR@EXEMPLO.PT',
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
                self::MENSAGEM_PERFIL_ATUALIZADO,
            )
            ->assertSessionHasNoErrors();

        $this->assertAuthenticatedAs(
            $utilizador,
            'sessao',
        );

        $utilizador->refresh();

        self::assertSame(
            'João Rodrigues',
            $utilizador->nome,
        );

        self::assertSame(
            'utilizador@exemplo.pt',
            $utilizador->email,
        );

        self::assertNotNull(
            $dataVerificacaoOriginal,
        );

        self::assertNotNull(
            $utilizador->email_verified_at,
        );

        self::assertTrue(
            $dataVerificacaoOriginal->equalTo(
                $utilizador->email_verified_at,
            ),
        );

        Notification::assertNothingSent();

        $this->assertDatabaseHas(
            'utilizadores',
            [
                'id' => $utilizador->getKey(),

                'nome' => 'João Rodrigues',

                'email' => 'utilizador@exemplo.pt',
            ],
        );
    }

    /**
     * Termina a sessão e envia uma nova verificação quando o e-mail muda.
     *
     * @since 2.0.0
     */
    #[Test]
    public function alteracao_do_email_termina_sessao_e_envia_verificacao(): void
    {
        Notification::fake();

        $utilizador = $this->criarUtilizador(
            nome: 'Utilizador Teste',
            email: 'anterior@exemplo.pt',
            emailVerificado: true,
        );

        $resposta = $this
            ->actingAs(
                $utilizador,
                'sessao',
            )
            ->patch(
                route(
                    'perfil.atualizar',
                ),
                [
                    'nome' => 'Utilizador Teste',

                    'email' => 'novo@exemplo.pt',
                ],
            );

        $resposta
            ->assertRedirect(
                route(
                    'login',
                ),
            )
            ->assertSessionHas(
                'sucesso',
                self::MENSAGEM_EMAIL_ALTERADO,
            )
            ->assertSessionHasNoErrors();

        $this->assertGuest(
            'sessao',
        );

        $utilizador->refresh();

        self::assertSame(
            'novo@exemplo.pt',
            $utilizador->email,
        );

        self::assertNull(
            $utilizador->email_verified_at,
        );

        Notification::assertSentTo(
            $utilizador,
            NotificacaoVerificacaoEmail::class,
        );

        $this->assertDatabaseHas(
            'utilizadores',
            [
                'id' => $utilizador->getKey(),

                'email' => 'novo@exemplo.pt',

                'email_verified_at' => null,
            ],
        );
    }

    /**
     * Preserva a alteração do e-mail e termina a sessão mesmo quando
     * o envio da notificação de verificação falha.
     *
     * @since 2.0.0
     */
    #[Test]
    public function falha_no_envio_da_verificacao_preserva_email_e_termina_sessao(): void
    {
        $utilizador = $this->criarUtilizador(
            nome: 'Utilizador Teste',
            email: 'anterior@exemplo.pt',
            emailVerificado: true,
        );

        $tentativaEnvio = false;

        /*
     * A exceção é lançada antes do envio efetivo da notificação.
     * Não é necessária qualquer ligação ao servidor SMTP.
     */
        Event::listen(
            NotificationSending::class,
            static function (
                NotificationSending $evento,
            ) use (&$tentativaEnvio): void {
                if (
                    ! $evento->notification instanceof NotificacaoVerificacaoEmail
                ) {
                    return;
                }

                $tentativaEnvio = true;

                throw new RuntimeException(
                    'Falha simulada no envio da verificação de e-mail.',
                );
            },
        );

        $resposta = $this
            ->actingAs(
                $utilizador,
                'sessao',
            )
            ->patch(
                route(
                    'perfil.atualizar',
                ),
                [
                    'nome' => 'Utilizador Teste',

                    'email' => 'novo@exemplo.pt',
                ],
            );

        self::assertTrue(
            $tentativaEnvio,
            'A aplicação deveria tentar enviar a notificação de verificação.',
        );

        $resposta
            ->assertRedirect(
                route(
                    'login',
                ),
            )
            ->assertSessionHas(
                'erro',
            )
            ->assertSessionHasNoErrors();

        $this->assertGuest(
            'sessao',
        );

        $utilizador->refresh();

        self::assertSame(
            'novo@exemplo.pt',
            $utilizador->email,
        );

        self::assertNull(
            $utilizador->email_verified_at,
        );

        $this->assertDatabaseHas(
            'utilizadores',
            [
                'id' => $utilizador->getKey(),

                'email' => 'novo@exemplo.pt',

                'email_verified_at' => null,
            ],
        );
    }

    /**
     * Impede a exposição de mensagens sensíveis quando uma falha
     * no envio da verificação de e-mail é registada nos logs.
     *
     * @since 2.0.0
     */
    #[Test]
    public function nao_expoe_mensagem_sensivel_ao_reportar_falha_da_verificacao(): void
    {
        $segredoFicticio =
            'AUDITORIA_SEGREDO_FICTICIO_B19_17_EMAIL';

        $utilizador = $this->criarUtilizador(
            nome: 'Utilizador Teste',
            email: 'anterior@exemplo.pt',
            emailVerificado: true,
        );

        Event::listen(
            NotificationSending::class,
            static function (
                NotificationSending $evento,
            ) use ($segredoFicticio): void {
                if (
                    ! $evento->notification instanceof NotificacaoVerificacaoEmail
                ) {
                    return;
                }

                throw new RuntimeException(
                    'Falha simulada de SMTP com segredo: '.$segredoFicticio,
                );
            },
        );

        /*
     * Interceta os registos para não escrever a exceção
     * simulada nos ficheiros reais de logging.
     */
        Log::spy();

        $resposta = $this
            ->actingAs(
                $utilizador,
                'sessao',
            )
            ->patch(
                route(
                    'perfil.atualizar',
                ),
                [
                    'nome' => 'Utilizador Teste',

                    'email' => 'novo@exemplo.pt',
                ],
            );

        $resposta
            ->assertRedirect(
                route(
                    'login',
                ),
            )
            ->assertSessionHas(
                'erro',
            );

        $this->assertGuest(
            'sessao',
        );

        $mensagemRegistada = null;
        $contextoRegistado = null;

        Log::shouldHaveReceived('error')
            ->once()
            ->withArgs(
                static function (
                    string $mensagem,
                    array $contexto,
                ) use (
                    &$mensagemRegistada,
                    &$contextoRegistado,
                ): bool {
                    $mensagemRegistada = $mensagem;
                    $contextoRegistado = $contexto;

                    return true;
                },
            );

        self::assertIsString(
            $mensagemRegistada,
        );

        self::assertIsArray(
            $contextoRegistado,
        );

        self::assertStringNotContainsString(
            $segredoFicticio,
            $mensagemRegistada,
        );

        /*
     * Uma exceção completa pode incluir mensagens sensíveis,
     * incluindo as das exceções anteriores.
     */
        self::assertArrayNotHasKey(
            'exception',
            $contextoRegistado,
        );
    }

    /**
     * Guarda a fotografia nova e elimina a fotografia anterior.
     *
     * @since 2.0.0
     */
    #[Test]
    public function substitui_fotografia_do_perfil(): void
    {
        $caminhoAnterior =
            'fotografias/utilizadores/fotografia-anterior.jpg';

        $this->discoPublico->put(
            $caminhoAnterior,
            'fotografia-anterior',
        );

        $utilizador = $this->criarUtilizador(
            fotografia: $caminhoAnterior,
        );

        $fotografiaNova =
            $this->criarFotografiaPngValida();

        $resposta = $this
            ->actingAs(
                $utilizador,
                'sessao',
            )
            ->patch(
                route(
                    'perfil.atualizar',
                ),
                [
                    'nome' => $utilizador->nome,

                    'email' => $utilizador->email,

                    'fotografia' => $fotografiaNova,
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
                self::MENSAGEM_PERFIL_ATUALIZADO,
            )
            ->assertSessionHasNoErrors();

        $this->assertAuthenticatedAs(
            $utilizador,
            'sessao',
        );

        $utilizador->refresh();

        $caminhoNovo =
            $utilizador->fotografia;

        self::assertIsString(
            $caminhoNovo,
        );

        self::assertStringStartsWith(
            'fotografias/utilizadores/',
            $caminhoNovo,
        );

        self::assertNotSame(
            $caminhoAnterior,
            $caminhoNovo,
        );

        $this->discoPublico->assertMissing(
            $caminhoAnterior,
        );

        $this->discoPublico->assertExists(
            $caminhoNovo,
        );

        $this->assertDatabaseHas(
            'utilizadores',
            [
                'id' => $utilizador->getKey(),

                'fotografia' => $caminhoNovo,
            ],
        );
    }

    /**
     * Rejeita um ficheiro PHP apresentado como fotografia JPEG.
     *
     * A fotografia anterior deve permanecer intacta e nenhum
     * ficheiro novo pode ser armazenado.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_ficheiro_php_disfarcado_de_fotografia(): void
    {
        $caminhoAnterior =
            'fotografias/utilizadores/fotografia-anterior.jpg';

        $this->discoPublico->put(
            $caminhoAnterior,
            'fotografia-anterior',
        );

        $utilizador = $this->criarUtilizador(
            fotografia: $caminhoAnterior,
        );

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
                        'perfil.atualizar',
                    ),
                    [
                        'nome' => $utilizador->nome,

                        'email' => $utilizador->email,

                        'fotografia' => $ficheiroMalicioso,
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
                        'fotografia',
                    ],
                    null,
                    'perfil',
                );

            $utilizador->refresh();

            self::assertSame(
                $caminhoAnterior,
                $utilizador->fotografia,
            );

            $this->discoPublico->assertExists(
                $caminhoAnterior,
            );

            self::assertSame(
                [
                    $caminhoAnterior,
                ],
                $this->discoPublico->allFiles(
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

    /**
     * Devolve os erros no saco exclusivo do formulário do perfil.
     *
     * Os dados persistidos devem permanecer inalterados.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_dados_invalidos_no_saco_do_perfil(): void
    {
        $utilizador = $this->criarUtilizador(
            nome: 'Nome Original',
            email: 'original@exemplo.pt',
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
                    'perfil.atualizar',
                ),
                [
                    'nome' => 'A',

                    'email' => 'email-invalido',
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
                    'nome',
                    'email',
                ],
                null,
                'perfil',
            );

        $this->assertAuthenticatedAs(
            $utilizador,
            'sessao',
        );

        $utilizador->refresh();

        self::assertSame(
            'Nome Original',
            $utilizador->nome,
        );

        self::assertSame(
            'original@exemplo.pt',
            $utilizador->email,
        );

        $this->assertDatabaseHas(
            'utilizadores',
            [
                'id' => $utilizador->getKey(),

                'nome' => 'Nome Original',

                'email' => 'original@exemplo.pt',
            ],
        );
    }

    /**
     * Cria um utilizador persistido.
     *
     * @param  string  $nome  Nome do utilizador.
     * @param  string  $email  Endereço de e-mail.
     * @param  string|null  $fotografia  Caminho opcional da fotografia.
     * @param  bool  $emailVerificado  Indicação de verificação do e-mail.
     * @return Utilizador Utilizador criado.
     *
     * @since 2.0.0
     */
    private function criarUtilizador(
        string $nome = 'Utilizador Teste',
        string $email = 'utilizador@exemplo.pt',
        ?string $fotografia = null,
        bool $emailVerificado = true,
    ): Utilizador {
        $utilizador = new Utilizador;

        $utilizador->nome =
            $nome;

        $utilizador->email =
            $email;

        $utilizador->password =
            'PalavraPasse#Segura2026';

        $utilizador->papel =
            PapelUtilizador::Utilizador;

        $utilizador->fotografia =
            $fotografia;

        $utilizador->email_verified_at =
            $emailVerificado
            ? CarbonImmutable::now()
                ->subDay()
                ->startOfSecond()
            : null;

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
     * Cria uma fotografia PNG válida sem depender da extensão GD.
     *
     * O conteúdo corresponde a uma imagem PNG real de um píxel. O ficheiro é
     * marcado como carregamento de teste para poder ser processado pelo
     * Laravel e pelo Symfony sem ter sido enviado através de HTTP.
     *
     * @return UploadedFile Fotografia temporária válida.
     *
     * @throws RuntimeException Quando não é possível criar o ficheiro.
     *
     * @since 2.0.0
     */
    private function criarFotografiaPngValida(): UploadedFile
    {
        $conteudo = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=',
            true,
        );

        if ($conteudo === false) {
            throw new RuntimeException(
                'Não foi possível descodificar a fotografia de teste.',
            );
        }

        $caminhoTemporario = tempnam(
            sys_get_temp_dir(),
            'metal-thursday-fotografia-',
        );

        if ($caminhoTemporario === false) {
            throw new RuntimeException(
                'Não foi possível criar o ficheiro temporário da fotografia.',
            );
        }

        $bytesEscritos = file_put_contents(
            $caminhoTemporario,
            $conteudo,
        );

        if ($bytesEscritos === false) {
            @unlink(
                $caminhoTemporario,
            );

            throw new RuntimeException(
                'Não foi possível escrever a fotografia temporária.',
            );
        }

        return new UploadedFile(
            $caminhoTemporario,
            'fotografia-nova.png',
            'image/png',
            UPLOAD_ERR_OK,
            true,
        );
    }
}
