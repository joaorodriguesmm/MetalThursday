<?php

declare(strict_types=1);

namespace Tests\Feature\Servicos\Autenticacao;

use App\Enumeracoes\PapelUtilizador;
use App\Models\Autenticacao\Convite;
use App\Models\Autenticacao\Utilizador;
use App\Models\Comunicacao\PermissaoEmail;
use App\Servicos\Autenticacao\ServicoRegistoPorConvite;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa o registo transacional de utilizadores através de convites.
 *
 * @since 2.0.0
 */
final class ServicoRegistoPorConviteTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Palavra-passe válida utilizada nos testes.
     *
     * @var string
     *
     * @since 2.0.0
     */
    private const PALAVRA_PASSE =
        'MetalThursday#2026';

    /**
     * Serviço testado.
     *
     * @since 2.0.0
     */
    private ServicoRegistoPorConvite $servico;

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

        $this->servico = app(
            ServicoRegistoPorConvite::class,
        );
    }

    /**
     * Confirma que o utilizador é criado, a fotografia é armazenada, as
     * permissões são sincronizadas e o convite é utilizado.
     *
     * @since 2.0.0
     */
    #[Test]
    public function regista_utilizador_e_utiliza_convite(): void
    {
        $codigo =
            'MT-Codigo-Seguro-Principal';

        $convite =
            $this->criarConvite(
                codigo: $codigo,
                emailDestino: 'utilizador@exemplo.pt',
            );

        $primeiraPermissao =
            $this->criarPermissao(
                nome: 'Novidades',
                identificador: 'novidades',
                ordem: 1,
            );

        $segundaPermissao =
            $this->criarPermissao(
                nome: 'Lembretes',
                identificador: 'lembretes',
                ordem: 2,
            );

        $identificadorPrimeiraPermissao =
            (int) $primeiraPermissao->getKey();

        $identificadorSegundaPermissao =
            (int) $segundaPermissao->getKey();

        $fotografia = UploadedFile::fake()->create(
            name: 'fotografia.jpg',
            kilobytes: 128,
            mimeType: 'image/jpeg',
        );

        $utilizador =
            $this
                ->servico
                ->registar(
                    codigoConvite: $codigo,
                    nome: '  João   Rodrigues  ',
                    email: '  UTILIZADOR@EXEMPLO.PT ',
                    palavraPasse: self::PALAVRA_PASSE,
                    fotografia: $fotografia,
                    identificadoresPermissoesEmail: [
                        $identificadorSegundaPermissao,
                        (string) $identificadorPrimeiraPermissao,
                        $identificadorSegundaPermissao,
                    ],
                );

        self::assertTrue(
            $utilizador->exists,
        );

        self::assertSame(
            'João Rodrigues',
            $utilizador->nome,
        );

        self::assertSame(
            'utilizador@exemplo.pt',
            $utilizador->email,
        );

        self::assertSame(
            PapelUtilizador::Utilizador,
            $utilizador->papel,
        );

        self::assertNull(
            $utilizador->email_verified_at,
        );

        self::assertIsString(
            $utilizador->fotografia,
        );

        self::assertStringStartsWith(
            'fotografias/utilizadores/',
            $utilizador->fotografia,
        );

        self::assertTrue(
            Storage::disk(
                'publico',
            )->exists(
                $utilizador->fotografia,
            ),
        );

        $hashPalavraPasse =
            $utilizador->getAuthPassword();

        self::assertIsString(
            $hashPalavraPasse,
        );

        self::assertNotSame(
            self::PALAVRA_PASSE,
            $hashPalavraPasse,
        );

        self::assertTrue(
            Hash::check(
                self::PALAVRA_PASSE,
                $hashPalavraPasse,
            ),
        );

        $this->assertDatabaseHas(
            'utilizadores',
            [
                'id' => $utilizador->getKey(),

                'nome' => 'João Rodrigues',

                'email' => 'utilizador@exemplo.pt',

                'fotografia' => $utilizador->fotografia,

                'papel' => PapelUtilizador::Utilizador->value,

                'email_verified_at' => null,
            ],
        );

        $this->assertDatabaseHas(
            'permissao_email_utilizador',
            [
                'utilizador_id' => $utilizador->getKey(),

                'permissao_email_id' => $identificadorPrimeiraPermissao,
            ],
        );

        $this->assertDatabaseHas(
            'permissao_email_utilizador',
            [
                'utilizador_id' => $utilizador->getKey(),

                'permissao_email_id' => $identificadorSegundaPermissao,
            ],
        );

        self::assertSame(
            [
                $identificadorPrimeiraPermissao,
                $identificadorSegundaPermissao,
            ],
            $this->obterIdentificadoresPermissoes(
                $utilizador,
            ),
        );

        $convite->refresh();

        self::assertSame(
            $utilizador->getKey(),
            $convite->utilizado_por_id,
        );

        self::assertNotNull(
            $convite->utilizado_em,
        );

        self::assertTrue(
            $convite->foiUtilizado(),
        );

        self::assertFalse(
            $convite->estaDisponivel(),
        );

        self::assertSame(
            Convite::calcularHashCodigo(
                $codigo,
            ),
            $convite->codigo_hash,
        );
    }

    /**
     * Confirma que o mesmo convite não pode ser reutilizado.
     *
     * @since 2.0.0
     */
    #[Test]
    public function nao_permite_reutilizar_convite(): void
    {
        $codigo =
            'MT-Convite-Utilizacao-Unica';

        $convite =
            $this->criarConvite(
                $codigo,
            );

        $primeiroUtilizador =
            $this
                ->servico
                ->registar(
                    codigoConvite: $codigo,
                    nome: 'Primeiro Utilizador',
                    email: 'primeiro@exemplo.pt',
                    palavraPasse: self::PALAVRA_PASSE,
                );

        try {
            $this
                ->servico
                ->registar(
                    codigoConvite: $codigo,
                    nome: 'Segundo Utilizador',
                    email: 'segundo@exemplo.pt',
                    palavraPasse: self::PALAVRA_PASSE,
                );

            self::fail(
                'Era esperada uma exceção ao reutilizar o convite.',
            );
        } catch (DomainException $excecao) {
            self::assertSame(
                'O convite não está disponível para utilização.',
                $excecao->getMessage(),
            );
        }

        $this->assertDatabaseCount(
            'utilizadores',
            1,
        );

        $this->assertDatabaseMissing(
            'utilizadores',
            [
                'email' => 'segundo@exemplo.pt',
            ],
        );

        $convite->refresh();

        self::assertSame(
            $primeiroUtilizador->getKey(),
            $convite->utilizado_por_id,
        );

        self::assertNotNull(
            $convite->utilizado_em,
        );

        self::assertFalse(
            $convite->estaDisponivel(),
        );
    }

    /**
     * Confirma que um endereço diferente do destinatário é rejeitado.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_email_diferente_do_destinatario(): void
    {
        $codigo =
            'MT-Convite-Com-Destinatario';

        $convite =
            $this->criarConvite(
                codigo: $codigo,
                emailDestino: 'destinatario@exemplo.pt',
            );

        try {
            $this
                ->servico
                ->registar(
                    codigoConvite: $codigo,
                    nome: 'Outro Utilizador',
                    email: 'outro@exemplo.pt',
                    palavraPasse: self::PALAVRA_PASSE,
                );

            self::fail(
                'Era esperada uma exceção para o endereço incorreto.',
            );
        } catch (DomainException $excecao) {
            self::assertSame(
                'O endereço de e-mail não corresponde ao destinatário do convite.',
                $excecao->getMessage(),
            );
        }

        $this->assertDatabaseCount(
            'utilizadores',
            0,
        );

        $convite->refresh();

        self::assertNull(
            $convite->utilizado_por_id,
        );

        self::assertNull(
            $convite->utilizado_em,
        );

        self::assertTrue(
            $convite->estaDisponivel(),
        );
    }

    /**
     * Confirma que uma permissão inexistente reverte a transação e elimina a
     * fotografia armazenada antes da falha.
     *
     * @since 2.0.0
     */
    #[Test]
    public function permissao_inexistente_reverte_toda_a_transacao(): void
    {
        $codigo =
            'MT-Convite-Rollback-Permissao';

        $convite =
            $this->criarConvite(
                $codigo,
            );

        $fotografia = UploadedFile::fake()->create(
            name: 'fotografia-rollback.jpg',
            kilobytes: 128,
            mimeType: 'image/jpeg',
        );

        try {
            $this
                ->servico
                ->registar(
                    codigoConvite: $codigo,
                    nome: 'Utilizador Rollback',
                    email: 'rollback@exemplo.pt',
                    palavraPasse: self::PALAVRA_PASSE,
                    fotografia: $fotografia,
                    identificadoresPermissoesEmail: [
                        999999,
                    ],
                );

            self::fail(
                'Era esperada uma exceção para a permissão inexistente.',
            );
        } catch (InvalidArgumentException $excecao) {
            self::assertSame(
                'As seguintes permissões de e-mail não existem: 999999.',
                $excecao->getMessage(),
            );
        }

        $this->assertDatabaseCount(
            'utilizadores',
            0,
        );

        $this->assertDatabaseCount(
            'permissao_email_utilizador',
            0,
        );

        self::assertSame(
            [],
            Storage::disk(
                'publico',
            )->allFiles(
                'fotografias/utilizadores',
            ),
        );

        $convite->refresh();

        self::assertNull(
            $convite->utilizado_por_id,
        );

        self::assertNull(
            $convite->utilizado_em,
        );

        self::assertTrue(
            $convite->estaDisponivel(),
        );
    }

    /**
     * Confirma que um endereço já utilizado não consome o convite e que a
     * fotografia armazenada é eliminada depois do rollback.
     *
     * @since 2.0.0
     */
    #[Test]
    public function email_duplicado_nao_consome_o_convite(): void
    {
        $this->criarUtilizadorExistente(
            'existente@exemplo.pt',
        );

        $codigo =
            'MT-Convite-Email-Duplicado';

        $convite =
            $this->criarConvite(
                $codigo,
            );

        $fotografia = UploadedFile::fake()->create(
            name: 'fotografia-duplicada.jpg',
            kilobytes: 128,
            mimeType: 'image/jpeg',
        );

        try {
            $this
                ->servico
                ->registar(
                    codigoConvite: $codigo,
                    nome: 'Novo Utilizador',
                    email: 'existente@exemplo.pt',
                    palavraPasse: self::PALAVRA_PASSE,
                    fotografia: $fotografia,
                );

            self::fail(
                'Era esperada uma exceção para o endereço duplicado.',
            );
        } catch (DomainException $excecao) {
            self::assertSame(
                'O endereço de e-mail já está associado a outro utilizador.',
                $excecao->getMessage(),
            );
        }

        $this->assertDatabaseCount(
            'utilizadores',
            1,
        );

        self::assertSame(
            [],
            Storage::disk(
                'publico',
            )->allFiles(
                'fotografias/utilizadores',
            ),
        );

        $convite->refresh();

        self::assertNull(
            $convite->utilizado_por_id,
        );

        self::assertNull(
            $convite->utilizado_em,
        );

        self::assertTrue(
            $convite->estaDisponivel(),
        );
    }

    /**
     * Confirma que um convite expirado não pode ser utilizado.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_convite_expirado(): void
    {
        $codigo =
            'MT-Convite-Expirado';

        $convite =
            $this->criarConvite(
                codigo: $codigo,
                expiraEm: CarbonImmutable::now()
                    ->subMinute(),
            );

        try {
            $this
                ->servico
                ->registar(
                    codigoConvite: $codigo,
                    nome: 'Utilizador Expirado',
                    email: 'expirado@exemplo.pt',
                    palavraPasse: self::PALAVRA_PASSE,
                );

            self::fail(
                'Era esperada uma exceção para o convite expirado.',
            );
        } catch (DomainException $excecao) {
            self::assertSame(
                'O convite não está disponível para utilização.',
                $excecao->getMessage(),
            );
        }

        $this->assertDatabaseCount(
            'utilizadores',
            0,
        );

        $convite->refresh();

        self::assertNull(
            $convite->utilizado_por_id,
        );

        self::assertNull(
            $convite->utilizado_em,
        );

        self::assertTrue(
            $convite->estaExpirado(),
        );

        self::assertFalse(
            $convite->estaDisponivel(),
        );
    }

    /**
     * Confirma que uma palavra-passe insegura é rejeitada antes do
     * armazenamento da fotografia e da abertura da transação.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_palavra_passe_insegura(): void
    {
        $codigo =
            'MT-Convite-Palavra-Passe-Fraca';

        $convite =
            $this->criarConvite(
                $codigo,
            );

        $fotografia = UploadedFile::fake()->create(
            name: 'fotografia-insegura.jpg',
            kilobytes: 128,
            mimeType: 'image/jpeg',
        );

        try {
            $this
                ->servico
                ->registar(
                    codigoConvite: $codigo,
                    nome: 'Utilizador Inseguro',
                    email: 'inseguro@exemplo.pt',
                    palavraPasse: 'fraca',
                    fotografia: $fotografia,
                );

            self::fail(
                'Era esperada uma exceção para a palavra-passe insegura.',
            );
        } catch (InvalidArgumentException) {
            self::assertTrue(
                true,
            );
        }

        $this->assertDatabaseCount(
            'utilizadores',
            0,
        );

        self::assertSame(
            [],
            Storage::disk(
                'publico',
            )->allFiles(
                'fotografias/utilizadores',
            ),
        );

        $convite->refresh();

        self::assertNull(
            $convite->utilizado_por_id,
        );

        self::assertNull(
            $convite->utilizado_em,
        );

        self::assertTrue(
            $convite->estaDisponivel(),
        );
    }

    /**
     * Cria um convite persistido para os testes.
     *
     * @param  string  $codigo  Código original do convite.
     * @param  string|null  $emailDestino  Destinatário opcional.
     * @param  CarbonImmutable|null  $expiraEm  Expiração opcional.
     * @return Convite Convite persistido.
     *
     * @since 2.0.0
     */
    private function criarConvite(
        string $codigo,
        ?string $emailDestino = null,
        ?CarbonImmutable $expiraEm = null,
    ): Convite {
        $convite = new Convite;

        $convite->nome_convidado =
            'Utilizador Convidado';

        $convite->email_destino =
            $emailDestino;

        $convite->expira_em =
            $expiraEm;

        $convite->definirCodigo(
            $codigo,
        );

        $convite->saveOrFail();

        return $convite->refresh();
    }

    /**
     * Cria uma permissão de e-mail persistida.
     *
     * @param  string  $nome  Nome apresentado.
     * @param  string  $identificador  Identificador técnico.
     * @param  int  $ordem  Ordem de apresentação.
     * @return PermissaoEmail Permissão persistida.
     *
     * @since 2.0.0
     */
    private function criarPermissao(
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
     * Cria um utilizador existente.
     *
     * @param  string  $email  Endereço do utilizador.
     * @return Utilizador Utilizador persistido.
     *
     * @since 2.0.0
     */
    private function criarUtilizadorExistente(
        string $email,
    ): Utilizador {
        $utilizador = new Utilizador;

        $utilizador->nome =
            'Utilizador Existente';

        $utilizador->email =
            $email;

        $utilizador->password =
            self::PALAVRA_PASSE;

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
     * Obtém os identificadores das permissões atribuídas ao utilizador.
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
