<?php

declare(strict_types=1);

namespace Tests\Feature\Models\Autenticacao;

use App\Enumeracoes\PapelUtilizador;
use App\Models\Autenticacao\RegistoAcessoUtilizador;
use App\Models\Autenticacao\Utilizador;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa os contratos de acesso do modelo dos utilizadores.
 *
 * @since 2.0.0
 */
final class UtilizadorTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Confirma os métodos de estado de um utilizador ativo.
     *
     * @since 2.0.0
     */
    #[Test]
    public function reconhece_um_utilizador_com_acesso_ativo(): void
    {
        $utilizador = Utilizador::factory()
            ->create();

        self::assertTrue(
            $utilizador->temAcessoAtivo(),
        );

        self::assertFalse(
            $utilizador->estaSuspenso(),
        );
    }

    /**
     * Confirma os métodos de estado de um utilizador suspenso.
     *
     * @since 2.0.0
     */
    #[Test]
    public function reconhece_um_utilizador_suspenso(): void
    {
        $responsavel = $this->criarSuperAdministrador();

        $utilizador = Utilizador::factory()
            ->suspensoPor(
                $responsavel,
                'Motivo válido.',
            )
            ->create()
            ->fresh();

        self::assertNotNull(
            $utilizador,
        );

        self::assertTrue(
            $utilizador->estaSuspenso(),
        );

        self::assertFalse(
            $utilizador->temAcessoAtivo(),
        );

        self::assertInstanceOf(
            CarbonImmutable::class,
            $utilizador->suspenso_em,
        );
    }

    /**
     * Confirma a normalização do motivo da suspensão atual.
     *
     * @since 2.0.0
     */
    #[Test]
    public function normaliza_o_motivo_da_suspensao_atual(): void
    {
        $utilizador = new Utilizador;

        $utilizador->motivo_suspensao =
            "  Motivo \n normalizado. ";

        self::assertSame(
            'Motivo normalizado.',
            $utilizador->motivo_suspensao,
        );
    }

    /**
     * Confirma que um motivo não textual é rejeitado.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_um_motivo_de_suspensao_nao_textual(): void
    {
        $this->expectException(
            InvalidArgumentException::class,
        );

        $utilizador = new Utilizador;

        $utilizador->motivo_suspensao = 123;
    }

    /**
     * Confirma a relação com o responsável pela suspensão atual.
     *
     * @since 2.0.0
     */
    #[Test]
    public function obtem_o_responsavel_pela_suspensao_atual(): void
    {
        $responsavel = $this->criarSuperAdministrador();

        $utilizador = Utilizador::factory()
            ->suspensoPor(
                $responsavel,
            )
            ->create();

        self::assertTrue(
            $utilizador
                ->responsavelSuspensao
                ->is(
                    $responsavel,
                ),
        );
    }

    /**
     * Confirma a separação entre utilizadores ativos e suspensos.
     *
     * @since 2.0.0
     */
    #[Test]
    public function filtra_utilizadores_pelo_estado_do_acesso(): void
    {
        $responsavel = $this->criarSuperAdministrador();

        $ativo = Utilizador::factory()
            ->create();

        $suspenso = Utilizador::factory()
            ->suspensoPor(
                $responsavel,
            )
            ->create();

        $identificadoresAtivos = Utilizador::query()
            ->comAcessoAtivo()
            ->pluck(
                'id',
            )
            ->map(
                static fn (
                    mixed $identificador,
                ): int => (int) $identificador,
            )
            ->all();

        $identificadoresSuspensos = Utilizador::query()
            ->suspensos()
            ->pluck(
                'id',
            )
            ->map(
                static fn (
                    mixed $identificador,
                ): int => (int) $identificador,
            )
            ->all();

        self::assertContains(
            (int) $ativo->getKey(),
            $identificadoresAtivos,
        );

        self::assertContains(
            (int) $responsavel->getKey(),
            $identificadoresAtivos,
        );

        self::assertNotContains(
            (int) $suspenso->getKey(),
            $identificadoresAtivos,
        );

        self::assertSame(
            [
                (int) $suspenso->getKey(),
            ],
            $identificadoresSuspensos,
        );
    }

    /**
     * Confirma que os dados administrativos não são expostos
     * automaticamente.
     *
     * @since 2.0.0
     */
    #[Test]
    public function oculta_os_dados_administrativos_da_serializacao(): void
    {
        $responsavel = $this->criarSuperAdministrador();

        $utilizador = Utilizador::factory()
            ->suspensoPor(
                $responsavel,
                'Motivo reservado.',
            )
            ->create();

        $dados = $utilizador->toArray();

        self::assertArrayNotHasKey(
            'suspenso_em',
            $dados,
        );

        self::assertArrayNotHasKey(
            'motivo_suspensao',
            $dados,
        );

        self::assertArrayNotHasKey(
            'suspenso_por_id',
            $dados,
        );
    }

    /**
     * Confirma a ordenação cronológica inversa do histórico de acesso.
     *
     * @since 2.0.0
     */
    #[Test]
    public function ordena_o_historico_de_acesso_do_mais_recente_para_o_mais_antigo(): void
    {
        $responsavel = $this->criarSuperAdministrador();

        $utilizador = Utilizador::factory()
            ->create();

        $maisAntigo = RegistoAcessoUtilizador::factory()
            ->suspensao(
                'Primeira suspensão.',
            )
            ->paraUtilizador(
                $utilizador,
            )
            ->registadoPor(
                $responsavel,
            )
            ->state([
                'registado_em' => '2026-08-01 10:00:00',
            ])
            ->create();

        $maisRecente = RegistoAcessoUtilizador::factory()
            ->reativacao()
            ->paraUtilizador(
                $utilizador,
            )
            ->registadoPor(
                $responsavel,
            )
            ->state([
                'registado_em' => '2026-08-02 10:00:00',
            ])
            ->create();

        self::assertSame(
            [
                (int) $maisRecente->getKey(),
                (int) $maisAntigo->getKey(),
            ],
            $utilizador
                ->registosAcesso()
                ->pluck(
                    'id',
                )
                ->map(
                    static fn (
                        mixed $identificador,
                    ): int => (int) $identificador,
                )
                ->all(),
        );
    }

    /**
     * Confirma o histórico das ações efetuadas pelo responsável.
     *
     * @since 2.0.0
     */
    #[Test]
    public function obtem_as_alteracoes_de_acesso_efetuadas_pelo_responsavel(): void
    {
        $responsavel = $this->criarSuperAdministrador();

        $utilizador = Utilizador::factory()
            ->create();

        $registo = RegistoAcessoUtilizador::factory()
            ->paraUtilizador(
                $utilizador,
            )
            ->registadoPor(
                $responsavel,
            )
            ->create();

        self::assertTrue(
            $responsavel
                ->registosAcessoEfetuados()
                ->get()
                ->contains(
                    static fn (
                        RegistoAcessoUtilizador $registoEncontrado,
                    ): bool => $registoEncontrado->is(
                        $registo,
                    ),
                ),
        );
    }

    /**
     * Cria um superadministrador responsável por alterações de acesso.
     *
     * @return Utilizador Superadministrador persistido.
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
