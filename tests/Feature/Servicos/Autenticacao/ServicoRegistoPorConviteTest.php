<?php

declare(strict_types=1);

namespace Tests\Feature\Servicos\Autenticacao;

use App\Enumeracoes\PapelUtilizador;
use App\Models\Autenticacao\Convite;
use App\Models\Autenticacao\Utilizador;
use App\Servicos\Autenticacao\ServicoRegistoPorConvite;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * Testa o registo transacional de utilizadores através de convites.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
final class ServicoRegistoPorConviteTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Serviço testado.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private ServicoRegistoPorConvite $servico;

    /**
     * Prepara cada teste.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->servico = app(
            ServicoRegistoPorConvite::class,
        );
    }

    /**
     * Confirma que o utilizador é criado e o convite é utilizado.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function test_regista_utilizador_e_utiliza_convite(): void
    {
        $codigo = 'MT-Codigo-Seguro-Principal';

        $convite = $this->criarConvite(
            codigo: $codigo,
            emailDestino: 'utilizador@exemplo.pt',
        );

        $primeiraPermissao = $this->criarPermissao(
            'novidades',
        );

        $segundaPermissao = $this->criarPermissao(
            'lembretes',
        );

        $utilizador = $this->servico->registar(
            codigoConvite: $codigo,
            nome: '  João   Rodrigues  ',
            email: '  UTILIZADOR@EXEMPLO.PT ',
            palavraPasse: 'MetalThursday#2026',
            caminhoFotografia: 'fotografias/utilizadores/foto.webp',
            identificadoresPermissoesEmail: [
                $segundaPermissao,
                (string) $primeiraPermissao,
                $segundaPermissao,
            ],
        );

        self::assertTrue($utilizador->exists);

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

        self::assertSame(
            'fotografias/utilizadores/foto.webp',
            $utilizador->fotografia,
        );

        self::assertNotSame(
            'MetalThursday#2026',
            $utilizador->password,
        );

        self::assertTrue(
            Hash::check(
                'MetalThursday#2026',
                $utilizador->password,
            ),
        );

        $this->assertDatabaseHas(
            'users',
            [
                'id' => $utilizador->getKey(),
                'name' => 'João Rodrigues',
                'email' => 'utilizador@exemplo.pt',
                'papel' => PapelUtilizador::Utilizador->value,
            ],
        );

        $this->assertDatabaseHas(
            'email_permission_user',
            [
                'user_id' => $utilizador->getKey(),
                'email_permission_id' => $primeiraPermissao,
            ],
        );

        $this->assertDatabaseHas(
            'email_permission_user',
            [
                'user_id' => $utilizador->getKey(),
                'email_permission_id' => $segundaPermissao,
            ],
        );

        $convite->refresh();

        self::assertSame(
            $utilizador->getKey(),
            $convite->utilizado_por,
        );

        self::assertNotNull(
            $convite->utilizado_em,
        );

        self::assertFalse(
            $convite->estaDisponivel(),
        );

        self::assertSame(
            Convite::calcularHashCodigo($codigo),
            $convite->codigo_hash,
        );
    }

    /**
     * Confirma que o mesmo convite não pode ser reutilizado.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function test_nao_permite_reutilizar_convite(): void
    {
        $codigo = 'MT-Convite-Utilizacao-Unica';

        $this->criarConvite($codigo);

        $this->servico->registar(
            codigoConvite: $codigo,
            nome: 'Primeiro Utilizador',
            email: 'primeiro@exemplo.pt',
            palavraPasse: 'MetalThursday#2026',
        );

        try {
            $this->servico->registar(
                codigoConvite: $codigo,
                nome: 'Segundo Utilizador',
                email: 'segundo@exemplo.pt',
                palavraPasse: 'MetalThursday#2026',
            );

            self::fail(
                'Era esperada uma exceção ao reutilizar o convite.',
            );
        } catch (DomainException) {
            // A exceção esperada confirma a utilização única.
        }

        $this->assertDatabaseCount('users', 1);

        $this->assertDatabaseMissing(
            'users',
            [
                'email' => 'segundo@exemplo.pt',
            ],
        );
    }

    /**
     * Confirma que um e-mail diferente do destinatário é rejeitado.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function test_rejeita_email_diferente_do_destinatario(): void
    {
        $codigo = 'MT-Convite-Com-Destinatario';

        $convite = $this->criarConvite(
            codigo: $codigo,
            emailDestino: 'destinatario@exemplo.pt',
        );

        try {
            $this->servico->registar(
                codigoConvite: $codigo,
                nome: 'Outro Utilizador',
                email: 'outro@exemplo.pt',
                palavraPasse: 'MetalThursday#2026',
            );

            self::fail(
                'Era esperada uma exceção para o e-mail incorreto.',
            );
        } catch (DomainException) {
            // A exceção esperada confirma a restrição.
        }

        $this->assertDatabaseCount('users', 0);

        $convite->refresh();

        self::assertNull($convite->utilizado_por);
        self::assertNull($convite->utilizado_em);
        self::assertTrue($convite->estaDisponivel());
    }

    /**
     * Confirma que uma permissão inexistente reverte toda a transação.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function test_permissao_inexistente_reverte_toda_a_transacao(): void
    {
        $codigo = 'MT-Convite-Rollback-Permissao';

        $convite = $this->criarConvite($codigo);

        try {
            $this->servico->registar(
                codigoConvite: $codigo,
                nome: 'Utilizador Rollback',
                email: 'rollback@exemplo.pt',
                palavraPasse: 'MetalThursday#2026',
                identificadoresPermissoesEmail: [
                    999999,
                ],
            );

            self::fail(
                'Era esperada uma exceção para a permissão inexistente.',
            );
        } catch (InvalidArgumentException) {
            // A exceção esperada deve provocar o rollback.
        }

        $this->assertDatabaseCount('users', 0);

        $this->assertDatabaseCount(
            'email_permission_user',
            0,
        );

        $convite->refresh();

        self::assertNull($convite->utilizado_por);
        self::assertNull($convite->utilizado_em);
        self::assertTrue($convite->estaDisponivel());
    }

    /**
     * Confirma que um e-mail já utilizado não consome o convite.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function test_email_duplicado_nao_consome_o_convite(): void
    {
        $this->criarUtilizadorExistente(
            'existente@exemplo.pt',
        );

        $codigo = 'MT-Convite-Email-Duplicado';
        $convite = $this->criarConvite($codigo);

        try {
            $this->servico->registar(
                codigoConvite: $codigo,
                nome: 'Novo Utilizador',
                email: 'existente@exemplo.pt',
                palavraPasse: 'MetalThursday#2026',
            );

            self::fail(
                'Era esperada uma exceção para o e-mail duplicado.',
            );
        } catch (DomainException) {
            // A exceção normalizada é o comportamento esperado.
        }

        $this->assertDatabaseCount('users', 1);

        $convite->refresh();

        self::assertNull($convite->utilizado_por);
        self::assertNull($convite->utilizado_em);
        self::assertTrue($convite->estaDisponivel());
    }

    /**
     * Confirma que um convite expirado não pode ser utilizado.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function test_rejeita_convite_expirado(): void
    {
        $codigo = 'MT-Convite-Expirado';

        $convite = $this->criarConvite(
            codigo: $codigo,
            expiraEm: CarbonImmutable::now()
                ->subMinute(),
        );

        try {
            $this->servico->registar(
                codigoConvite: $codigo,
                nome: 'Utilizador Expirado',
                email: 'expirado@exemplo.pt',
                palavraPasse: 'MetalThursday#2026',
            );

            self::fail(
                'Era esperada uma exceção para o convite expirado.',
            );
        } catch (DomainException) {
            // O convite expirado deve ser rejeitado.
        }

        $this->assertDatabaseCount('users', 0);

        $convite->refresh();

        self::assertNull($convite->utilizado_por);
        self::assertNull($convite->utilizado_em);
        self::assertFalse($convite->estaDisponivel());
    }

    /**
     * Confirma que uma palavra-passe insegura é rejeitada antes da
     * persistência.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function test_rejeita_palavra_passe_insegura(): void
    {
        $codigo = 'MT-Convite-Palavra-Passe-Fraca';
        $convite = $this->criarConvite($codigo);

        try {
            $this->servico->registar(
                codigoConvite: $codigo,
                nome: 'Utilizador Inseguro',
                email: 'inseguro@exemplo.pt',
                palavraPasse: 'fraca',
            );

            self::fail(
                'Era esperada uma exceção para a palavra-passe insegura.',
            );
        } catch (InvalidArgumentException) {
            // A política deve rejeitar a palavra-passe.
        }

        $this->assertDatabaseCount('users', 0);

        $convite->refresh();

        self::assertNull($convite->utilizado_por);
        self::assertNull($convite->utilizado_em);
        self::assertTrue($convite->estaDisponivel());
    }

    /**
     * Cria um convite para os testes.
     *
     * @param  string  $codigo  - Código original do convite.
     * @param  string|null  $emailDestino  - Destinatário opcional.
     * @param  CarbonImmutable|null  $expiraEm  - Expiração opcional.
     * @return Convite - Convite persistido.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function criarConvite(
        string $codigo,
        ?string $emailDestino = null,
        ?CarbonImmutable $expiraEm = null,
    ): Convite {
        $convite = new Convite;

        $convite->nome_convidado =
            'Utilizador Convidado';

        $convite->email_destino = $emailDestino;
        $convite->expira_em = $expiraEm;

        $convite->definirCodigo($codigo);
        $convite->saveOrFail();

        return $convite;
    }

    /**
     * Cria uma permissão de e-mail.
     *
     * @param  string  $slug  - Identificador textual.
     * @return int - Identificador da permissão.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function criarPermissao(string $slug): int
    {
        return (int) DB::table(
            'email_permissions',
        )->insertGetId([
            'name' => ucfirst($slug),
            'slug' => $slug,
            'description' => sprintf(
                'Permissão de teste: %s.',
                $slug,
            ),
        ]);
    }

    /**
     * Cria um utilizador existente.
     *
     * @param  string  $email  - Endereço do utilizador.
     * @return Utilizador - Utilizador persistido.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function criarUtilizadorExistente(
        string $email,
    ): Utilizador {
        $utilizador = new Utilizador;

        $utilizador->nome = 'Utilizador Existente';
        $utilizador->email = $email;
        $utilizador->password =
            'MetalThursday#2026';

        $utilizador->papel =
            PapelUtilizador::Utilizador;

        $utilizador->saveOrFail();

        return $utilizador;
    }
}
