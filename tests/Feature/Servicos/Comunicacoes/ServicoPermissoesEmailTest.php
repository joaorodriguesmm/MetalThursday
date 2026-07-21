<?php

declare(strict_types=1);

namespace Tests\Feature\Servicos\Comunicacoes;

use App\Enumeracoes\PapelUtilizador;
use App\Models\Autenticacao\Utilizador;
use App\Servicos\Comunicacoes\ServicoPermissoesEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * Testa o serviço de gestão das permissões de e-mail.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
final class ServicoPermissoesEmailTest extends TestCase
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
    private ServicoPermissoesEmail $servico;

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
            ServicoPermissoesEmail::class,
        );
    }

    /**
     * Confirma que os identificadores são convertidos, deduplicados e
     * ordenados.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function test_normaliza_identificadores_das_permissoes(): void
    {
        $resultado = $this->servico
            ->normalizarIdentificadores([
                '3',
                1,
                '2',
                3,
                1,
            ]);

        self::assertSame(
            [1, 2, 3],
            $resultado,
        );
    }

    /**
     * Confirma que valores inválidos são rejeitados.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function test_rejeita_identificadores_invalidos(): void
    {
        $this->expectException(
            InvalidArgumentException::class,
        );

        $this->servico
            ->normalizarIdentificadores([
                1,
                'invalido',
            ]);
    }

    /**
     * Confirma que as permissões são associadas ao utilizador.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function test_sincroniza_permissoes_do_utilizador(): void
    {
        $utilizador = $this->criarUtilizador();

        $primeiraPermissao = $this->criarPermissao(
            'novidades',
        );

        $segundaPermissao = $this->criarPermissao(
            'lembretes',
        );

        $resultado = $this->servico->sincronizar(
            $utilizador,
            [
                (string) $segundaPermissao,
                $primeiraPermissao,
                $segundaPermissao,
            ],
        );

        self::assertSame(
            [
                $primeiraPermissao,
                $segundaPermissao,
            ],
            $resultado,
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

        $this->assertDatabaseCount(
            'email_permission_user',
            2,
        );
    }

    /**
     * Confirma que uma lista vazia remove as permissões existentes.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function test_lista_vazia_remove_todas_as_permissoes(): void
    {
        $utilizador = $this->criarUtilizador();
        $permissao = $this->criarPermissao('eventos');

        $this->servico->sincronizar(
            $utilizador,
            [$permissao],
        );

        $this->assertDatabaseCount(
            'email_permission_user',
            1,
        );

        $resultado = $this->servico->sincronizar(
            $utilizador,
            [],
        );

        self::assertSame([], $resultado);

        $this->assertDatabaseCount(
            'email_permission_user',
            0,
        );
    }

    /**
     * Confirma que uma permissão inexistente é rejeitada sem alterar as
     * associações atuais.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function test_permissao_inexistente_nao_altera_as_associacoes(): void
    {
        $utilizador = $this->criarUtilizador();
        $permissao = $this->criarPermissao('resumos');

        $this->servico->sincronizar(
            $utilizador,
            [$permissao],
        );

        try {
            $this->servico->sincronizar(
                $utilizador,
                [$permissao, 999999],
            );

            self::fail(
                'Era esperada uma exceção para a permissão inexistente.',
            );
        } catch (InvalidArgumentException) {
            // A exceção esperada confirma a validação.
        }

        $this->assertDatabaseHas(
            'email_permission_user',
            [
                'user_id' => $utilizador->getKey(),
                'email_permission_id' => $permissao,
            ],
        );

        $this->assertDatabaseCount(
            'email_permission_user',
            1,
        );
    }

    /**
     * Confirma que um utilizador não persistido é rejeitado.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function test_rejeita_utilizador_nao_persistido(): void
    {
        $utilizador = new Utilizador;

        $this->expectException(
            InvalidArgumentException::class,
        );

        $this->servico->sincronizar(
            $utilizador,
            [],
        );
    }

    /**
     * Cria um utilizador válido para os testes.
     *
     * @return Utilizador - Utilizador persistido.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function criarUtilizador(): Utilizador
    {
        $utilizador = new Utilizador;

        $utilizador->nome = 'Utilizador de Teste';
        $utilizador->email = 'utilizador@exemplo.pt';
        $utilizador->password = 'MetalThursday#2026';
        $utilizador->papel =
            PapelUtilizador::Utilizador;

        $utilizador->saveOrFail();

        return $utilizador;
    }

    /**
     * Cria uma permissão de e-mail.
     *
     * @param  string  $slug  - Identificador textual da permissão.
     * @return int - Identificador da permissão criada.
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
}
