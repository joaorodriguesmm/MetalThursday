<?php

declare(strict_types=1);

namespace Tests\Feature\Servicos\Autenticacao;

use App\Models\Autenticacao\Convite;
use App\Servicos\Autenticacao\ServicoRegistoPorConvite;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Verifica a expiração de convites durante a obtenção de bloqueios SQL.
 *
 * @since 2.0.0
 */
final class RegistoConviteExpiracaoDuranteBloqueioTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Um convite que expira antes da validação após FOR UPDATE é rejeitado.
     *
     * O relógio simulado avança após a consulta SQL, sem esperas reais.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_convite_expirado_ao_obter_bloqueio(): void
    {
        $momentoInicial = CarbonImmutable::parse('2026-10-14 12:00:00');
        $momentoExpiracao = $momentoInicial->addMinute();
        $momentoAposBloqueio = $momentoExpiracao->addSecond();

        CarbonImmutable::setTestNow($momentoInicial);

        try {
            $codigo = 'MT-Convite-Expiracao-Bloqueio';

            $convite = new Convite;
            $convite->nome_convidado = 'Utilizador Convidado';
            $convite->expira_em = $momentoExpiracao;
            $convite->definirCodigo($codigo);
            $convite->saveOrFail();

            self::assertTrue($convite->estaDisponivel());

            $consultaObservada = false;

            DB::listen(
                static function (QueryExecuted $consulta) use (
                    &$consultaObservada,
                    $momentoAposBloqueio,
                ): void {
                    if (
                        $consultaObservada
                        || ! str_contains($consulta->sql, 'convites')
                        || ! str_contains(strtolower($consulta->sql), 'for update')
                    ) {
                        return;
                    }

                    $consultaObservada = true;
                    CarbonImmutable::setTestNow($momentoAposBloqueio);
                },
            );

            try {
                app(ServicoRegistoPorConvite::class)->registar(
                    codigoConvite: $codigo,
                    nome: 'Utilizador Expiracao Bloqueio',
                    email: 'expiracao-bloqueio@exemplo.pt',
                    palavraPasse: 'MetalThursday#2026',
                );

                self::fail(
                    'O registo aceitou um convite que expirou durante o bloqueio.',
                );
            } catch (DomainException $excecao) {
                self::assertSame(
                    'O convite não está disponível para utilização.',
                    $excecao->getMessage(),
                );
            }

            self::assertTrue(
                $consultaObservada,
                'A consulta do convite com FOR UPDATE deve ter sido executada.',
            );

            $this->assertDatabaseMissing(
                'utilizadores',
                ['email' => 'expiracao-bloqueio@exemplo.pt'],
            );

            $convite->refresh();

            self::assertNull($convite->utilizado_por_id);
            self::assertNull($convite->utilizado_em);
        } finally {
            CarbonImmutable::setTestNow();
        }
    }
}
