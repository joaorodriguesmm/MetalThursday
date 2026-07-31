<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Utilizadores;

use App\Enumeracoes\PapelUtilizador;
use App\Models\Autenticacao\Utilizador;
use App\Models\Comunicacao\PermissaoEmail;
use App\Notifications\NotificacaoVerificacaoEmail;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

/**
 * Testa os pedidos HTTP associados aos dados gerais do perfil.
 *
 * @since 2.0.0
 *
 * @version 2.1.0
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
     *
     * @version 1.0.0
     */
    private const MENSAGEM_PERFIL_ATUALIZADO =
        'O perfil foi atualizado com sucesso.';

    /**
     * Mensagem apresentada depois de alterar o endereço de e-mail.
     *
     * @var string
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const MENSAGEM_EMAIL_ALTERADO =
        'O perfil foi atualizado. Verifica o novo endereço de e-mail antes de iniciares sessão novamente.';

    /**
     * Disco público falso utilizado pelos testes.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private FilesystemAdapter $discoPublico;

    /**
     * Prepara cada teste.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
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
     *
     * @version 2.0.0
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
     *
     * @version 2.0.0
     */
    #[Test]
    public function apresenta_pagina_de_edicao_do_perfil(): void
    {
        $utilizador = $this->criarUtilizador();

        $permissaoTodas =
            $this->criarPermissaoEmail(
                nome: 'Todas',
                identificador: 'todas',
                ordem: 1,
            );

        $permissaoNovidades =
            $this->criarPermissaoEmail(
                nome: 'Novidades',
                identificador: 'novidades',
                ordem: 2,
            );

        $identificadorPermissaoTodas =
            (int) $permissaoTodas->getKey();

        $identificadorPermissaoNovidades =
            (int) $permissaoNovidades->getKey();

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

                        'descricao' => 'Permissão de teste: todas.',

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
     *
     * @version 2.0.0
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
     *
     * @version 2.1.0
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
     * Guarda a fotografia nova e elimina a fotografia anterior.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
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
     * Devolve os erros no saco exclusivo do formulário do perfil.
     *
     * Os dados persistidos devem permanecer inalterados.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
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
     *
     * @version 2.0.0
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
            ? now()
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
     *
     * @version 2.0.0
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
     *
     * @version 2.0.0
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
