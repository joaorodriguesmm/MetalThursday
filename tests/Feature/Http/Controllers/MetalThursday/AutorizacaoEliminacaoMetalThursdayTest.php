<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\MetalThursday;

use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\Edicao;
use App\Models\MetalThursday\MetalThursday;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa a autorização da eliminação depois de obter o registo bloqueado.
 *
 * @since 2.0.0
 */
final class AutorizacaoEliminacaoMetalThursdayTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Uma alteração da titularidade após carregar a rota impede a eliminação.
     *
     * A alteração é simulada ao recuperar o modelo pela primeira vez: o modelo
     * já carregado mantém a autoria anterior, mas a consulta transacional
     * encontra os dados mais recentes e tem de voltar a autorizar a operação.
     *
     * @since 2.0.0
     */
    #[Test]
    public function nao_elimina_quando_criador_muda_apos_resolucao_da_rota(): void
    {
        $criadorAnterior = Utilizador::factory()->create();
        $criadorAtual = Utilizador::factory()->create();

        $this->actingAs($criadorAnterior, 'sessao');

        $data = CarbonImmutable::parse('2026-01-08');

        $edicao = Edicao::factory()
            ->comPeriodo($data->startOfMonth(), $data->endOfMonth())
            ->create();

        $metalThursday = MetalThursday::factory()
            ->comData($data)
            ->comEdicao($edicao)
            ->comAutor($criadorAnterior)
            ->create();

        self::assertSame(
            (int) $criadorAnterior->getKey(),
            $metalThursday->criado_por_id,
        );

        $autoriaAlterada = false;

        MetalThursday::retrieved(
            static function (MetalThursday $registo) use (
                $metalThursday,
                $criadorAtual,
                &$autoriaAlterada,
            ): void {
                if (
                    $autoriaAlterada
                    || (int) $registo->getKey() !== (int) $metalThursday->getKey()
                ) {
                    return;
                }

                $autoriaAlterada = true;

                DB::table('metal_thursdays')
                    ->where('id', $registo->getKey())
                    ->update([
                        'criado_por_id' => $criadorAtual->getKey(),
                    ]);
            },
        );

        $this
            ->deleteJson(
                route('metal-thursday.eliminar', $metalThursday),
            )
            ->assertForbidden();

        self::assertTrue($autoriaAlterada);

        $this->assertDatabaseHas('metal_thursdays', [
            'id' => $metalThursday->getKey(),
            'criado_por_id' => $criadorAtual->getKey(),
        ]);

        $this->assertNotSoftDeleted('metal_thursdays', [
            'id' => $metalThursday->getKey(),
        ]);
    }
}
