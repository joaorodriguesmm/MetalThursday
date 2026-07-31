<?php

declare(strict_types=1);

namespace Tests\Feature\Servicos\Autenticacao;

use App\Enumeracoes\PapelUtilizador;
use App\Models\Autenticacao\Convite;
use App\Models\Autenticacao\Utilizador;
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
 * @version 2.0.0
 */
final class ServicoConvitesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Repõe o relógio global depois de cada teste.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    protected function tearDown(): void
    {
        Date::setTestNow();

        parent::tearDown();
    }

    /**
     * Confirma que o serviço cria, normaliza e persiste um convite seguro.
     *
     * Apenas o hash SHA-256 do código deve ser persistido.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    #[Test]
    public function cria_e_persiste_um_convite_seguro(): void
    {
        $momentoAtual = CarbonImmutable::parse(
            '2026-07-21 12:00:00',
        );

        Date::setTestNow(
            $momentoAtual,
        );

        $criador =
            $this->criarUtilizador();

        $expiraEm =
            $momentoAtual->addDays(
                7,
            );

        $resultado = app(
            ServicoConvites::class,
        )->criar(
            nomeConvidado: '  Maria   da Silva  ',
            emailDestino: '  MARIA@EXEMPLO.PT  ',
            criador: $criador,
            expiraEm: $expiraEm,
        );

        $convite =
            $resultado->obterConvite();

        $codigo =
            $resultado->obterCodigo();

        $identificadorCriador =
            (int) $criador->getKey();

        $hashCodigo =
            Convite::calcularHashCodigo(
                $codigo,
            );

        self::assertTrue(
            $convite->exists,
        );

        self::assertSame(
            'Maria da Silva',
            $convite->nome_convidado,
        );

        self::assertSame(
            'maria@exemplo.pt',
            $convite->email_destino,
        );

        self::assertSame(
            $identificadorCriador,
            $convite->criado_por_id,
        );

        self::assertNull(
            $convite->utilizado_por_id,
        );

        self::assertNull(
            $convite->utilizado_em,
        );

        self::assertNull(
            $convite->revogado_em,
        );

        self::assertNotNull(
            $convite->expira_em,
        );

        self::assertTrue(
            $convite
                ->expira_em
                ->equalTo(
                    $expiraEm,
                ),
        );

        self::assertStringStartsWith(
            'MT-',
            $codigo,
        );

        self::assertSame(
            $hashCodigo,
            $convite->codigo_hash,
        );

        self::assertTrue(
            $convite->correspondeAoCodigo(
                $codigo,
            ),
        );

        self::assertDatabaseHas(
            'convites',
            [
                'id' => $convite->getKey(),

                'nome_convidado' => 'Maria da Silva',

                'email_destino' => 'maria@exemplo.pt',

                'criado_por_id' => $identificadorCriador,

                'codigo_hash' => $hashCodigo,

                'utilizado_por_id' => null,

                'utilizado_em' => null,

                'revogado_em' => null,
            ],
        );

        self::assertFalse(
            DB::table(
                'convites',
            )
                ->where(
                    'codigo_hash',
                    $codigo,
                )
                ->exists(),
            'O código original não pode ser persistido.',
        );
    }

    /**
     * Confirma que apenas convites disponíveis são encontrados.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    #[Test]
    public function encontra_um_convite_disponivel_pelo_codigo(): void
    {
        $servico = app(
            ServicoConvites::class,
        );

        $resultado =
            $servico->criar(
                nomeConvidado: 'Utilizador convidado',
            );

        $codigo =
            $resultado->obterCodigo();

        $conviteEncontrado =
            $servico->encontrarDisponivelPorCodigo(
                "  {$codigo}  ",
            );

        self::assertNotNull(
            $conviteEncontrado,
        );

        self::assertSame(
            $resultado
                ->obterConvite()
                ->getKey(),
            $conviteEncontrado->getKey(),
        );

        self::assertTrue(
            $conviteEncontrado->correspondeAoCodigo(
                $codigo,
            ),
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
     * @version 2.0.0
     */
    #[Test]
    public function nao_encontra_um_convite_expirado(): void
    {
        $momentoCriacao = CarbonImmutable::parse(
            '2026-07-21 12:00:00',
        );

        Date::setTestNow(
            $momentoCriacao,
        );

        $servico = app(
            ServicoConvites::class,
        );

        $resultado =
            $servico->criar(
                nomeConvidado: 'Convite temporário',
                expiraEm: $momentoCriacao->addHour(),
            );

        Date::setTestNow(
            $momentoCriacao->addHours(
                2,
            ),
        );

        self::assertNull(
            $servico->encontrarDisponivelPorCodigo(
                $resultado->obterCodigo(),
            ),
        );

        $conviteAtualizado =
            $resultado
                ->obterConvite()
                ->fresh();

        self::assertInstanceOf(
            Convite::class,
            $conviteAtualizado,
        );

        self::assertTrue(
            $conviteAtualizado->estaExpirado(),
        );

        self::assertFalse(
            $conviteAtualizado->estaDisponivel(),
        );
    }

    /**
     * Confirma que a revogação é persistida e idempotente.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    #[Test]
    public function revoga_um_convite_pendente(): void
    {
        $primeiroMomento = CarbonImmutable::parse(
            '2026-07-21 12:00:00',
        );

        Date::setTestNow(
            $primeiroMomento,
        );

        $servico = app(
            ServicoConvites::class,
        );

        $resultado =
            $servico->criar(
                nomeConvidado: 'Convite revogado',
            );

        $conviteRevogado =
            $servico->revogar(
                $resultado->obterConvite(),
            );

        self::assertNotNull(
            $conviteRevogado->revogado_em,
        );

        self::assertTrue(
            $conviteRevogado
                ->revogado_em
                ->equalTo(
                    $primeiroMomento,
                ),
        );

        self::assertTrue(
            $conviteRevogado->foiRevogado(),
        );

        self::assertFalse(
            $conviteRevogado->estaDisponivel(),
        );

        self::assertNull(
            $servico->encontrarDisponivelPorCodigo(
                $resultado->obterCodigo(),
            ),
        );

        Date::setTestNow(
            $primeiroMomento->addDay(),
        );

        $conviteRevogadoNovamente =
            $servico->revogar(
                $conviteRevogado,
            );

        self::assertNotNull(
            $conviteRevogadoNovamente->revogado_em,
        );

        self::assertTrue(
            $conviteRevogadoNovamente
                ->revogado_em
                ->equalTo(
                    $primeiroMomento,
                ),
        );

        $this->assertDatabaseHas(
            'convites',
            [
                'id' => $conviteRevogadoNovamente->getKey(),

                'revogado_em' => $primeiroMomento->format(
                    'Y-m-d H:i:s',
                ),
            ],
        );
    }

    /**
     * Confirma que não é possível criar um convite já expirado.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    #[Test]
    public function rejeita_uma_data_de_expiracao_no_passado(): void
    {
        $momentoAtual = CarbonImmutable::parse(
            '2026-07-21 12:00:00',
        );

        Date::setTestNow(
            $momentoAtual,
        );

        $this->expectException(
            InvalidArgumentException::class,
        );

        $this->expectExceptionMessage(
            'A expiração do convite deve estar no futuro.',
        );

        app(
            ServicoConvites::class,
        )->criar(
            nomeConvidado: 'Convite inválido',
            expiraEm: $momentoAtual->subSecond(),
        );
    }

    /**
     * Cria um utilizador persistido para os testes.
     *
     * @return Utilizador Utilizador criado.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    private function criarUtilizador(): Utilizador
    {
        $utilizador = new Utilizador;

        $utilizador->nome =
            'Administrador de testes';

        $utilizador->email =
            'administrador@exemplo.pt';

        $utilizador->password =
            'PalavraPasse#Segura2026';

        $utilizador->papel =
            PapelUtilizador::Administrador;

        $utilizador->email_verified_at =
            now()
                ->subDay()
                ->startOfSecond();

        $utilizador->saveOrFail();

        return $utilizador->refresh();
    }
}
