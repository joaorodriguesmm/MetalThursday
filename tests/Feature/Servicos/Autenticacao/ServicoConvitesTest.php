<?php

declare(strict_types=1);

namespace Tests\Feature\Servicos\Autenticacao;

use App\Models\Autenticacao\Convite;
use App\Models\User;
use App\Servicos\Autenticacao\ServicoConvites;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa o serviço dos convites com persistência real.
 *
 * Os testes utilizam exclusivamente a base de dados configurada para o
 * ambiente de testes.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
final class ServicoConvitesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Repõe o relógio global depois de cada teste.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    protected function tearDown(): void
    {
        Date::setTestNow();

        parent::tearDown();
    }

    /**
     * Confirma que o serviço cria e normaliza um convite.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function cria_e_persiste_um_convite_seguro(): void
    {
        $momentoAtual = CarbonImmutable::parse(
            '2026-07-21 12:00:00',
        );

        Date::setTestNow($momentoAtual);

        $criador = $this->criarUtilizador();
        $expiraEm = $momentoAtual->addDays(7);

        $resultado = app(ServicoConvites::class)->criar(
            nomeConvidado: '  Maria   da Silva  ',
            emailDestino: '  MARIA@EXEMPLO.PT  ',
            criador: $criador,
            expiraEm: $expiraEm,
        );

        $convite = $resultado->obterConvite();
        $codigo = $resultado->obterCodigo();

        self::assertTrue($convite->exists);
        self::assertSame('Maria da Silva', $convite->nome_convidado);
        self::assertSame('maria@exemplo.pt', $convite->email_destino);
        self::assertSame($criador->getKey(), $convite->criado_por);
        self::assertNull($convite->utilizado_por);
        self::assertNull($convite->utilizado_em);
        self::assertNull($convite->revogado_em);

        self::assertNotNull($convite->expira_em);
        self::assertTrue(
            $convite->expira_em->equalTo($expiraEm),
        );

        self::assertStringStartsWith('MT-', $codigo);
        self::assertSame(
            hash('sha256', $codigo),
            $convite->codigo_hash,
        );

        self::assertDatabaseHas(
            'convites',
            [
                'id' => $convite->getKey(),
                'nome_convidado' => 'Maria da Silva',
                'email_destino' => 'maria@exemplo.pt',
                'criado_por' => $criador->getKey(),
                'codigo_hash' => hash('sha256', $codigo),
                'utilizado_por' => null,
                'utilizado_em' => null,
                'revogado_em' => null,
            ],
        );

        self::assertFalse(
            DB::table('convites')
                ->where('codigo_hash', $codigo)
                ->exists(),
            'O código original não pode ser persistido.',
        );
    }

    /**
     * Confirma que apenas convites disponíveis são encontrados.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function encontra_um_convite_disponivel_pelo_codigo(): void
    {
        $servico = app(ServicoConvites::class);

        $resultado = $servico->criar(
            nomeConvidado: 'Utilizador convidado',
        );

        $codigo = $resultado->obterCodigo();

        $conviteEncontrado = $servico
            ->encontrarDisponivelPorCodigo(
                "  {$codigo}  ",
            );

        self::assertNotNull($conviteEncontrado);
        self::assertSame(
            $resultado->obterConvite()->getKey(),
            $conviteEncontrado->getKey(),
        );

        self::assertNull(
            $servico->encontrarDisponivelPorCodigo(
                'MT-CODIGO-INEXISTENTE',
            ),
        );
    }

    /**
     * Confirma que um convite expirado deixa de estar disponível.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function nao_encontra_um_convite_expirado(): void
    {
        $momentoCriacao = CarbonImmutable::parse(
            '2026-07-21 12:00:00',
        );

        Date::setTestNow($momentoCriacao);

        $servico = app(ServicoConvites::class);

        $resultado = $servico->criar(
            nomeConvidado: 'Convite temporário',
            expiraEm: $momentoCriacao->addHour(),
        );

        Date::setTestNow(
            $momentoCriacao->addHours(2),
        );

        self::assertNull(
            $servico->encontrarDisponivelPorCodigo(
                $resultado->obterCodigo(),
            ),
        );

        self::assertTrue(
            $resultado
                ->obterConvite()
                ->fresh()
                ->estaExpirado(),
        );
    }

    /**
     * Confirma que a revogação é persistida e idempotente.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function revoga_um_convite_pendente(): void
    {
        $primeiroMomento = CarbonImmutable::parse(
            '2026-07-21 12:00:00',
        );

        Date::setTestNow($primeiroMomento);

        $servico = app(ServicoConvites::class);

        $resultado = $servico->criar(
            nomeConvidado: 'Convite revogado',
        );

        $conviteRevogado = $servico->revogar(
            $resultado->obterConvite(),
        );

        self::assertNotNull($conviteRevogado->revogado_em);
        self::assertTrue(
            $conviteRevogado->revogado_em->equalTo(
                $primeiroMomento,
            ),
        );

        self::assertNull(
            $servico->encontrarDisponivelPorCodigo(
                $resultado->obterCodigo(),
            ),
        );

        Date::setTestNow(
            $primeiroMomento->addDay(),
        );

        $conviteRevogadoNovamente = $servico->revogar(
            $conviteRevogado,
        );

        self::assertNotNull(
            $conviteRevogadoNovamente->revogado_em,
        );

        self::assertTrue(
            $conviteRevogadoNovamente->revogado_em->equalTo(
                $primeiroMomento,
            ),
        );
    }

    /**
     * Confirma que não é possível criar um convite já expirado.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function rejeita_uma_data_de_expiracao_no_passado(): void
    {
        $momentoAtual = CarbonImmutable::parse(
            '2026-07-21 12:00:00',
        );

        Date::setTestNow($momentoAtual);

        $this->expectException(
            InvalidArgumentException::class,
        );

        $this->expectExceptionMessage(
            'A expiração do convite deve estar no futuro.',
        );

        app(ServicoConvites::class)->criar(
            nomeConvidado: 'Convite inválido',
            expiraEm: $momentoAtual->subSecond(),
        );
    }

    /**
     * Cria um utilizador persistido para os testes.
     *
     * A inserção direta evita depender de factories ou das regras de
     * atribuição em massa do modelo durante esta fase da refatoração.
     *
     * @return User - Utilizador criado.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function criarUtilizador(): User
    {
        $identificador = DB::table('users')->insertGetId([
            'name' => 'Administrador de testes',
            'email' => 'administrador@example.test',
            'email_verified_at' => null,
            'password' => null,
            'photo' => null,
            'invite_code' => 'MT-CRIADOR-TESTES',
            'remember_token' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return User::query()->findOrFail($identificador);
    }
}
