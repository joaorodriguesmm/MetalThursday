<?php

declare(strict_types=1);

namespace Tests\Feature\Servicos\Comunicacoes;

use App\Enumeracoes\IdentificadorPermissaoEmail;
use App\Enumeracoes\PapelUtilizador;
use App\Models\Autenticacao\Utilizador;
use App\Models\Comunicacao\PermissaoEmail;
use App\Servicos\Comunicacoes\ServicoPermissoesEmail;
use Database\Seeders\PermissaoEmailSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa o serviço de gestão das permissões de e-mail.
 *
 * @since 2.0.0
 */
final class ServicoPermissoesEmailTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Serviço testado.
     *
     * @since 2.0.0
     */
    private ServicoPermissoesEmail $servico;

    /**
     * Prepara cada teste.
     *
     * @since 2.0.0
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
     * @since 2.0.0
     */
    #[Test]
    public function normaliza_identificadores_das_permissoes(): void
    {
        $resultado =
            $this
                ->servico
                ->normalizarIdentificadores(
                    [
                        '3',
                        1,
                        '2',
                        3,
                        1,
                    ],
                );

        self::assertSame(
            [
                1,
                2,
                3,
            ],
            $resultado,
        );
    }

    /**
     * Confirma que valores inválidos são rejeitados.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_identificadores_invalidos(): void
    {
        $this->expectException(
            InvalidArgumentException::class,
        );

        $this->expectExceptionMessage(
            'Foi recebida uma permissão de e-mail inválida.',
        );

        $this
            ->servico
            ->normalizarIdentificadores(
                [
                    1,
                    'invalido',
                ],
            );
    }

    /**
     * Confirma que as permissões são associadas ao utilizador.
     *
     * Os identificadores devolvidos devem estar normalizados e a relação
     * previamente carregada deve ser invalidada depois da sincronização.
     *
     * @since 2.0.0
     */
    #[Test]
    public function sincroniza_permissoes_do_utilizador(): void
    {
        $utilizador =
            $this->criarUtilizador();

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

        $utilizador->load(
            'permissoesEmail',
        );

        self::assertTrue(
            $utilizador->relationLoaded(
                'permissoesEmail',
            ),
        );

        $resultado =
            $this
                ->servico
                ->sincronizar(
                    $utilizador,
                    [
                        (string) $identificadorSegundaPermissao,
                        $identificadorPrimeiraPermissao,
                        $identificadorSegundaPermissao,
                    ],
                );

        self::assertSame(
            [
                $identificadorPrimeiraPermissao,
                $identificadorSegundaPermissao,
            ],
            $resultado,
        );

        self::assertFalse(
            $utilizador->relationLoaded(
                'permissoesEmail',
            ),
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

        $this->assertDatabaseCount(
            'permissao_email_utilizador',
            2,
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
    }

    /**
     * Confirma que uma lista vazia remove todas as permissões existentes.
     *
     * @since 2.0.0
     */
    #[Test]
    public function lista_vazia_remove_todas_as_permissoes(): void
    {
        $utilizador =
            $this->criarUtilizador();

        $permissao =
            $this->criarPermissao(
                nome: 'Eventos',
                identificador: 'eventos',
                ordem: 1,
            );

        $identificadorPermissao =
            (int) $permissao->getKey();

        $this
            ->servico
            ->sincronizar(
                $utilizador,
                [
                    $identificadorPermissao,
                ],
            );

        $this->assertDatabaseCount(
            'permissao_email_utilizador',
            1,
        );

        $resultado =
            $this
                ->servico
                ->sincronizar(
                    $utilizador,
                    [],
                );

        self::assertSame(
            [],
            $resultado,
        );

        $this->assertDatabaseCount(
            'permissao_email_utilizador',
            0,
        );

        self::assertSame(
            [],
            $this->obterIdentificadoresPermissoes(
                $utilizador,
            ),
        );
    }

    /**
     * Confirma que uma permissão inexistente é rejeitada sem alterar as
     * associações atuais.
     *
     * @since 2.0.0
     */
    #[Test]
    public function permissao_inexistente_nao_altera_as_associacoes(): void
    {
        $utilizador =
            $this->criarUtilizador();

        $permissao =
            $this->criarPermissao(
                nome: 'Resumos',
                identificador: 'resumos',
                ordem: 1,
            );

        $identificadorPermissao =
            (int) $permissao->getKey();

        $this
            ->servico
            ->sincronizar(
                $utilizador,
                [
                    $identificadorPermissao,
                ],
            );

        try {
            $this
                ->servico
                ->sincronizar(
                    $utilizador,
                    [
                        $identificadorPermissao,
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

        $this->assertDatabaseHas(
            'permissao_email_utilizador',
            [
                'utilizador_id' => $utilizador->getKey(),

                'permissao_email_id' => $identificadorPermissao,
            ],
        );

        $this->assertDatabaseCount(
            'permissao_email_utilizador',
            1,
        );

        self::assertSame(
            [
                $identificadorPermissao,
            ],
            $this->obterIdentificadoresPermissoes(
                $utilizador,
            ),
        );
    }

    /**
     * Confirma que um utilizador não persistido é rejeitado.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_utilizador_nao_persistido(): void
    {
        $utilizador = new Utilizador;

        $this->expectException(
            InvalidArgumentException::class,
        );

        $this->expectExceptionMessage(
            'O utilizador deve estar persistido antes de sincronizar as permissões.',
        );

        $this
            ->servico
            ->sincronizar(
                $utilizador,
                [],
            );
    }

    /**
     * Confirma que a permissão global preserva as escolhas específicas recebidas.
     *
     * Quando a opção global está ativa, o formulário envia também as escolhas
     * específicas anteriormente selecionadas. O serviço deve sincronizar
     * exatamente esse conjunto para permitir a sua recuperação quando a opção
     * global for posteriormente desativada.
     *
     * @since 2.0.0
     */
    #[Test]
    public function permissao_global_preserva_permissoes_especificas_recebidas(): void
    {
        app(
            PermissaoEmailSeeder::class,
        )->run();

        $utilizador =
            $this->criarUtilizador();

        $permissaoEspecifica = PermissaoEmail::query()
            ->where(
                'identificador',
                IdentificadorPermissaoEmail::NovasPublicacoes->value,
            )
            ->sole();

        $permissaoGlobal = PermissaoEmail::query()
            ->where(
                'identificador',
                IdentificadorPermissaoEmail::TodasNotificacoes->value,
            )
            ->sole();

        $identificadorEspecifica =
            (int) $permissaoEspecifica->getKey();

        $identificadorGlobal =
            (int) $permissaoGlobal->getKey();

        $this
            ->servico
            ->sincronizar(
                $utilizador,
                [
                    $identificadorEspecifica,
                ],
            );

        $resultado =
            $this
                ->servico
                ->sincronizar(
                    $utilizador,
                    [
                        $identificadorGlobal,
                        $identificadorEspecifica,
                    ],
                );

        self::assertSame(
            [
                $identificadorGlobal,
                $identificadorEspecifica,
            ],
            $resultado,
        );

        self::assertSame(
            [
                $identificadorGlobal,
                $identificadorEspecifica,
            ],
            $this->obterIdentificadoresPermissoes(
                $utilizador,
            ),
        );

        $resultadoSemGlobal =
            $this
                ->servico
                ->sincronizar(
                    $utilizador,
                    [
                        $identificadorEspecifica,
                    ],
                );

        self::assertSame(
            [
                $identificadorEspecifica,
            ],
            $resultadoSemGlobal,
        );

        self::assertSame(
            [
                $identificadorEspecifica,
            ],
            $this->obterIdentificadoresPermissoes(
                $utilizador,
            ),
        );
    }

    /**
     * Confirma que a permissão global respeita o subconjunto específico recebido.
     *
     * Os campos preservados do formulário enviam explicitamente as permissões
     * específicas que devem permanecer associadas quando a opção global está
     * ativa. Uma permissão específica removida antes da gravação não pode ser
     * novamente introduzida pelo serviço.
     *
     * @since 2.0.0
     */
    #[Test]
    public function permissao_global_respeita_subconjunto_especifico_recebido(): void
    {
        $this->seed(
            PermissaoEmailSeeder::class,
        );

        $utilizador =
            Utilizador::factory()
                ->create();

        $permissaoGlobal =
            PermissaoEmail::query()
                ->where(
                    'identificador',
                    IdentificadorPermissaoEmail::TodasNotificacoes->value,
                )
                ->sole();

        $novasPublicacoes =
            PermissaoEmail::query()
                ->where(
                    'identificador',
                    IdentificadorPermissaoEmail::NovasPublicacoes->value,
                )
                ->sole();

        $lembreteTarefas =
            PermissaoEmail::query()
                ->where(
                    'identificador',
                    IdentificadorPermissaoEmail::LembreteDiarioTarefas->value,
                )
                ->sole();

        $utilizador
            ->permissoesEmail()
            ->sync([
                $permissaoGlobal->getKey(),
                $novasPublicacoes->getKey(),
                $lembreteTarefas->getKey(),
            ]);

        $this
            ->servico
            ->sincronizar(
                $utilizador,
                [
                    (int) $permissaoGlobal->getKey(),
                    (int) $novasPublicacoes->getKey(),
                ],
            );

        self::assertSame(
            [
                (int) $permissaoGlobal->getKey(),
                (int) $novasPublicacoes->getKey(),
            ],
            $utilizador
                ->permissoesEmail()
                ->orderBy(
                    'permissoes_email.id',
                )
                ->pluck(
                    'permissoes_email.id',
                )
                ->map(
                    static fn (mixed $identificador): int => (int) $identificador,
                )
                ->all(),
        );
    }

    /**
     * Cria um utilizador válido para os testes.
     *
     * @return Utilizador Utilizador persistido.
     *
     * @since 2.0.0
     */
    private function criarUtilizador(): Utilizador
    {
        $utilizador = new Utilizador;

        $utilizador->nome =
            'Utilizador de Teste';

        $utilizador->email =
            'utilizador@exemplo.pt';

        $utilizador->password =
            'MetalThursday#2026';

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
     * Obtém os identificadores das permissões atualmente atribuídas.
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
