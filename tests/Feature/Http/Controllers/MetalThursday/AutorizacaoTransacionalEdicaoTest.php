<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\MetalThursday;

use App\Enumeracoes\PapelUtilizador;
use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\Edicao;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Verifica a revogação de privilégios antes da escrita efetiva das edições.
 *
 * @since 2.0.0
 */
final class AutorizacaoTransacionalEdicaoTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Uma edição não pode ser alterada depois de revogar o papel administrativo.
     *
     * @since 2.0.0
     */
    #[Test]
    public function revogacao_durante_pedido_impede_atualizacao(): void
    {
        [$edicao, $administrador] = $this->prepararCenario();
        $leituras = $this->revogarAoBloquearEdicao($edicao, $administrador);

        $this->actingAs($administrador, 'sessao')
            ->patchJson(route('edicoes.atualizar', $edicao), [
                'nome' => 'Alteração indevida',
                'data_inicio' => '2026-01-01',
                'data_fim' => '2026-01-31',
            ])
            ->assertForbidden();

        self::assertSame(2, $leituras());
        $this->assertDatabaseHas('edicoes', [
            'id' => $edicao->id,
            'nome' => 'Edição de janeiro',
        ]);
    }

    /**
     * Uma edição não pode ser eliminada depois de revogar o papel.
     *
     * @since 2.0.0
     */
    #[Test]
    public function revogacao_durante_pedido_impede_eliminacao(): void
    {
        [$edicao, $administrador] = $this->prepararCenario();
        $leituras = $this->revogarAoBloquearEdicao($edicao, $administrador);

        $this->actingAs($administrador, 'sessao')
            ->deleteJson(route('edicoes.eliminar', $edicao))
            ->assertForbidden();

        self::assertSame(2, $leituras());
        $this->assertNotSoftDeleted('edicoes', ['id' => $edicao->id]);
    }

    /**
     * Uma revogação impede alterar a ligação da compilação.
     *
     * @since 2.0.0
     */
    #[Test]
    public function revogacao_durante_pedido_impede_ligacao_compilacao(): void
    {
        [$edicao, $administrador] = $this->prepararCenario();
        $leituras = $this->revogarAoBloquearEdicao($edicao, $administrador);

        $this->actingAs($administrador, 'sessao')
            ->patchJson(route('edicoes.ligacao-compilacao.atualizar', $edicao), [
                'ligacao_compilacao' => 'https://example.com/compilacao',
            ])
            ->assertForbidden();

        self::assertSame(2, $leituras());
        $this->assertDatabaseHas('edicoes', [
            'id' => $edicao->id,
            'ligacao_compilacao' => null,
        ]);
    }

    /**
     * As músicas favoritas não podem ser alteradas por antigo administrador.
     *
     * @since 2.0.0
     */
    #[Test]
    public function revogacao_durante_pedido_impede_musicas_favoritas(): void
    {
        [$edicao, $administrador] = $this->prepararCenario();
        $utilizador = Utilizador::factory()->create();
        $leituras = $this->revogarAoBloquearEdicao($edicao, $administrador);

        $this->actingAs($administrador, 'sessao')
            ->postJson(route('edicoes.musicas-favoritas.guardar', $edicao), [
                'musicas_favoritas' => [
                    $utilizador->id => ['Música indevida', null, null],
                ],
            ])
            ->assertForbidden();

        self::assertSame(2, $leituras());
        $this->assertDatabaseCount('musicas_favoritas_edicao', 0);
    }

    /**
     * @return array{Edicao, Utilizador}
     */
    private function prepararCenario(): array
    {
        $edicao = Edicao::factory()
            ->comNome('Edição de janeiro')
            ->comPeriodo(
                CarbonImmutable::parse('2026-01-01'),
                CarbonImmutable::parse('2026-01-31'),
            )
            ->create();

        $administrador = Utilizador::factory()->create([
            'papel' => PapelUtilizador::Administrador,
        ]);

        return [$edicao, $administrador];
    }

    /**
     * A primeira leitura corresponde ao route model binding; a segunda
     * acontece na transação, depois da autorização inicial do pedido.
     *
     * @return \Closure(): int Número de leituras do registo da edição.
     */
    private function revogarAoBloquearEdicao(
        Edicao $edicao,
        Utilizador $administrador,
    ): \Closure {
        $leituras = 0;

        Edicao::retrieved(static function (Edicao $registo) use (
            $edicao,
            $administrador,
            &$leituras,
        ): void {
            if ($registo->id !== $edicao->id) {
                return;
            }

            $leituras++;

            if ($leituras !== 2) {
                return;
            }

            DB::table('utilizadores')
                ->where('id', $administrador->id)
                ->update(['papel' => PapelUtilizador::Utilizador->value]);
        });

        return static function () use (&$leituras): int {
            return $leituras;
        };
    }
}
