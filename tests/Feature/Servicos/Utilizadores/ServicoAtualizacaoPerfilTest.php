<?php

declare(strict_types=1);

namespace Tests\Feature\Servicos\Utilizadores;

use App\Enumeracoes\PapelUtilizador;
use App\Models\Autenticacao\Utilizador;
use App\Resultados\Utilizadores\PerfilAtualizado;
use App\Servicos\Utilizadores\ServicoAtualizacaoPerfil;
use App\Servicos\Utilizadores\ServicoFotografiasUtilizador;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa o serviço responsável pela atualização do perfil.
 *
 * @since 2.0.0
 */
final class ServicoAtualizacaoPerfilTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Disco público utilizado pelas fotografias.
     *
     * @var string
     *
     * @since 2.0.0
     */
    private const DISCO_PUBLICO =
        'publico';

    /**
     * Diretório das fotografias dos utilizadores.
     *
     * @var string
     *
     * @since 2.0.0
     */
    private const DIRETORIO_FOTOGRAFIAS =
        'fotografias/utilizadores';

    /**
     * Palavra-passe utilizada nos utilizadores de teste.
     *
     * @var string
     *
     * @since 2.0.0
     */
    private const PALAVRA_PASSE =
        'PalavraPasse#Segura2026';

    /**
     * Serviço testado.
     *
     * @since 2.0.0
     */
    private ServicoAtualizacaoPerfil $servicoPerfil;

    /**
     * Prepara cada teste.
     *
     * @since 2.0.0
     */
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(
            self::DISCO_PUBLICO,
        );

        $this->servicoPerfil =
            new ServicoAtualizacaoPerfil(
                new ServicoFotografiasUtilizador,
            );
    }

    /**
     * Atualiza o nome e preserva a verificação quando o e-mail não muda.
     *
     * O endereço recebido utiliza maiúsculas para confirmar que a comparação
     * ocorre depois da normalização aplicada pelo objeto de valor.
     *
     * @since 2.0.0
     */
    #[Test]
    public function atualiza_nome_sem_invalidar_email_inalterado(): void
    {
        $caminhoFotografia =
            self::DIRETORIO_FOTOGRAFIAS
            .'/existente.jpg';

        Storage::disk(
            self::DISCO_PUBLICO,
        )->put(
            $caminhoFotografia,
            'fotografia-existente',
        );

        $utilizador =
            $this->criarUtilizador(
                nome: 'Nome Original',
                email: 'utilizador@exemplo.pt',
                fotografia: $caminhoFotografia,
                emailVerificado: true,
            );

        $dataVerificacaoOriginal =
            $utilizador->email_verified_at;

        $resultado =
            $this
                ->servicoPerfil
                ->atualizar(
                    utilizador: $utilizador,
                    nome: '  João   Rodrigues  ',
                    email: 'UTILIZADOR@EXEMPLO.PT',
                );

        self::assertInstanceOf(
            PerfilAtualizado::class,
            $resultado,
        );

        self::assertFalse(
            $resultado->emailFoiAlterado(),
        );

        $utilizadorAtualizado =
            $resultado
                ->obterUtilizador()
                ->refresh();

        self::assertSame(
            'João Rodrigues',
            $utilizadorAtualizado->nome,
        );

        self::assertSame(
            'utilizador@exemplo.pt',
            $utilizadorAtualizado->email,
        );

        self::assertSame(
            $caminhoFotografia,
            $utilizadorAtualizado->fotografia,
        );

        self::assertNotNull(
            $dataVerificacaoOriginal,
        );

        self::assertNotNull(
            $utilizadorAtualizado->email_verified_at,
        );

        self::assertTrue(
            $dataVerificacaoOriginal->equalTo(
                $utilizadorAtualizado->email_verified_at,
            ),
        );

        self::assertTrue(
            Storage::disk(
                self::DISCO_PUBLICO,
            )->exists(
                $caminhoFotografia,
            ),
        );

        $this->assertDatabaseHas(
            'utilizadores',
            [
                'id' => $utilizador->getKey(),

                'nome' => 'João Rodrigues',

                'email' => 'utilizador@exemplo.pt',

                'fotografia' => $caminhoFotografia,

                'email_verified_at' => $dataVerificacaoOriginal->format(
                    'Y-m-d H:i:s',
                ),
            ],
        );
    }

    /**
     * Invalida a verificação quando o endereço de e-mail é alterado.
     *
     * @since 2.0.0
     */
    #[Test]
    public function altera_email_e_invalida_verificacao(): void
    {
        $utilizador =
            $this->criarUtilizador(
                nome: 'Utilizador Teste',
                email: 'anterior@exemplo.pt',
                emailVerificado: true,
            );

        self::assertNotNull(
            $utilizador->email_verified_at,
        );

        $resultado =
            $this
                ->servicoPerfil
                ->atualizar(
                    utilizador: $utilizador,
                    nome: 'Utilizador Teste',
                    email: 'novo@exemplo.pt',
                );

        self::assertTrue(
            $resultado->emailFoiAlterado(),
        );

        $utilizadorAtualizado =
            $resultado
                ->obterUtilizador()
                ->refresh();

        self::assertSame(
            'novo@exemplo.pt',
            $utilizadorAtualizado->email,
        );

        self::assertNull(
            $utilizadorAtualizado->email_verified_at,
        );

        $this->assertDatabaseHas(
            'utilizadores',
            [
                'id' => $utilizador->getKey(),

                'nome' => 'Utilizador Teste',

                'email' => 'novo@exemplo.pt',

                'email_verified_at' => null,
            ],
        );

        $this->assertDatabaseMissing(
            'utilizadores',
            [
                'id' => $utilizador->getKey(),

                'email' => 'anterior@exemplo.pt',
            ],
        );
    }

    /**
     * Substitui a fotografia depois de confirmar a atualização da base de
     * dados.
     *
     * @since 2.0.0
     */
    #[Test]
    public function substitui_fotografia_e_elimina_a_anterior(): void
    {
        $caminhoAnterior =
            self::DIRETORIO_FOTOGRAFIAS
            .'/anterior.jpg';

        Storage::disk(
            self::DISCO_PUBLICO,
        )->put(
            $caminhoAnterior,
            'fotografia-anterior',
        );

        $utilizador =
            $this->criarUtilizador(
                nome: 'Utilizador Teste',
                email: 'utilizador@exemplo.pt',
                fotografia: $caminhoAnterior,
            );

        $novaFotografia =
            UploadedFile::fake()->create(
                name: 'nova-fotografia.webp',
                kilobytes: 256,
                mimeType: 'image/webp',
            );

        $resultado =
            $this
                ->servicoPerfil
                ->atualizar(
                    utilizador: $utilizador,
                    nome: 'Utilizador Teste',
                    email: 'utilizador@exemplo.pt',
                    fotografia: $novaFotografia,
                );

        self::assertFalse(
            $resultado->emailFoiAlterado(),
        );

        $utilizadorAtualizado =
            $resultado
                ->obterUtilizador()
                ->refresh();

        $caminhoNovo =
            $utilizadorAtualizado->fotografia;

        self::assertIsString(
            $caminhoNovo,
        );

        self::assertNotSame(
            $caminhoAnterior,
            $caminhoNovo,
        );

        self::assertStringStartsWith(
            self::DIRETORIO_FOTOGRAFIAS
                .'/',
            $caminhoNovo,
        );

        self::assertFalse(
            Storage::disk(
                self::DISCO_PUBLICO,
            )->exists(
                $caminhoAnterior,
            ),
        );

        self::assertTrue(
            Storage::disk(
                self::DISCO_PUBLICO,
            )->exists(
                $caminhoNovo,
            ),
        );

        self::assertSame(
            [
                $caminhoNovo,
            ],
            Storage::disk(
                self::DISCO_PUBLICO,
            )->allFiles(
                self::DIRETORIO_FOTOGRAFIAS,
            ),
        );

        $this->assertDatabaseHas(
            'utilizadores',
            [
                'id' => $utilizador->getKey(),

                'nome' => 'Utilizador Teste',

                'email' => 'utilizador@exemplo.pt',

                'fotografia' => $caminhoNovo,
            ],
        );

        $this->assertDatabaseMissing(
            'utilizadores',
            [
                'id' => $utilizador->getKey(),

                'fotografia' => $caminhoAnterior,
            ],
        );
    }

    /**
     * Reverte os dados e elimina a fotografia nova quando o e-mail colide
     * com outro utilizador.
     *
     * @since 2.0.0
     */
    #[Test]
    public function conflito_de_email_reverte_atualizacao_e_limpa_fotografia_nova(): void
    {
        $caminhoAnterior =
            self::DIRETORIO_FOTOGRAFIAS
            .'/fotografia-historica.jpg';

        Storage::disk(
            self::DISCO_PUBLICO,
        )->put(
            $caminhoAnterior,
            'fotografia-historica',
        );

        $utilizador =
            $this->criarUtilizador(
                nome: 'Nome Original',
                email: 'original@exemplo.pt',
                fotografia: $caminhoAnterior,
                emailVerificado: true,
            );

        $dataVerificacaoOriginal =
            $utilizador->email_verified_at;

        $this->criarUtilizador(
            nome: 'Outro Utilizador',
            email: 'ocupado@exemplo.pt',
        );

        $novaFotografia =
            UploadedFile::fake()->create(
                name: 'nova.jpg',
                kilobytes: 128,
                mimeType: 'image/jpeg',
            );

        try {
            $this
                ->servicoPerfil
                ->atualizar(
                    utilizador: $utilizador,
                    nome: 'Nome Alterado',
                    email: 'ocupado@exemplo.pt',
                    fotografia: $novaFotografia,
                );

            self::fail(
                'Era esperada uma exceção devido ao endereço de e-mail duplicado.',
            );
        } catch (DomainException $excecao) {
            self::assertSame(
                'O endereço de e-mail já está associado a outro utilizador.',
                $excecao->getMessage(),
            );
        }

        $utilizador->refresh();

        self::assertSame(
            'Nome Original',
            $utilizador->nome,
        );

        self::assertSame(
            'original@exemplo.pt',
            $utilizador->email,
        );

        self::assertSame(
            $caminhoAnterior,
            $utilizador->fotografia,
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

        self::assertTrue(
            Storage::disk(
                self::DISCO_PUBLICO,
            )->exists(
                $caminhoAnterior,
            ),
        );

        self::assertSame(
            [
                $caminhoAnterior,
            ],
            Storage::disk(
                self::DISCO_PUBLICO,
            )->allFiles(
                self::DIRETORIO_FOTOGRAFIAS,
            ),
        );

        $this->assertDatabaseHas(
            'utilizadores',
            [
                'id' => $utilizador->getKey(),

                'nome' => 'Nome Original',

                'email' => 'original@exemplo.pt',

                'fotografia' => $caminhoAnterior,

                'email_verified_at' => $dataVerificacaoOriginal->format(
                    'Y-m-d H:i:s',
                ),
            ],
        );

        $this->assertDatabaseMissing(
            'utilizadores',
            [
                'id' => $utilizador->getKey(),

                'nome' => 'Nome Alterado',

                'email' => 'ocupado@exemplo.pt',
            ],
        );
    }

    /**
     * Rejeita os dados inválidos antes de guardar uma fotografia.
     *
     * @param  string  $nome  Nome recebido.
     * @param  string  $email  Endereço recebido.
     *
     * @since 2.0.0
     */
    #[Test]
    #[DataProvider('fornecerDadosInvalidos')]
    public function rejeita_dados_invalidos_antes_de_guardar_fotografia(
        string $nome,
        string $email,
    ): void {
        $utilizador =
            $this->criarUtilizador(
                nome: 'Utilizador Teste',
                email: 'utilizador@exemplo.pt',
            );

        $fotografia =
            UploadedFile::fake()->create(
                name: 'fotografia.jpg',
                kilobytes: 128,
                mimeType: 'image/jpeg',
            );

        try {
            $this
                ->servicoPerfil
                ->atualizar(
                    utilizador: $utilizador,
                    nome: $nome,
                    email: $email,
                    fotografia: $fotografia,
                );

            self::fail(
                'Era esperada uma exceção para os dados inválidos.',
            );
        } catch (InvalidArgumentException) {
            self::assertTrue(
                true,
            );
        }

        $utilizador->refresh();

        self::assertSame(
            'Utilizador Teste',
            $utilizador->nome,
        );

        self::assertSame(
            'utilizador@exemplo.pt',
            $utilizador->email,
        );

        self::assertNull(
            $utilizador->fotografia,
        );

        self::assertSame(
            [],
            Storage::disk(
                self::DISCO_PUBLICO,
            )->allFiles(
                self::DIRETORIO_FOTOGRAFIAS,
            ),
        );
    }

    /**
     * Fornece dados inválidos do perfil.
     *
     * @return array<string, array{nome: string, email: string}> Dados
     *                                                           inválidos.
     *
     * @since 2.0.0
     */
    public static function fornecerDadosInvalidos(): array
    {
        return [
            'nome demasiado curto' => [
                'nome' => 'A',

                'email' => 'valido@exemplo.pt',
            ],

            'endereço de e-mail inválido' => [
                'nome' => 'Nome Válido',

                'email' => 'endereco-invalido',
            ],
        ];
    }

    /**
     * Rejeita a atualização de um utilizador ainda não persistido.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_utilizador_nao_persistido(): void
    {
        $utilizador = new Utilizador;

        $utilizador->nome =
            'Utilizador Teste';

        $utilizador->email =
            'utilizador@exemplo.pt';

        $this->expectException(
            InvalidArgumentException::class,
        );

        $this->expectExceptionMessage(
            'O utilizador deve estar persistido para atualizar o perfil.',
        );

        $this
            ->servicoPerfil
            ->atualizar(
                utilizador: $utilizador,
                nome: 'Nome Atualizado',
                email: 'atualizado@exemplo.pt',
            );
    }

    /**
     * Cria um utilizador persistido para os testes.
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
        string $nome,
        string $email,
        ?string $fotografia = null,
        bool $emailVerificado = false,
    ): Utilizador {
        $utilizador = new Utilizador;

        $utilizador->nome =
            $nome;

        $utilizador->email =
            $email;

        $utilizador->password =
            self::PALAVRA_PASSE;

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
}
