<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Musica;

use App\Models\Autenticacao\Utilizador;
use App\Models\Musica\Genero;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa a revalidação transacional das permissões dos géneros.
 *
 * @since 2.0.0
 */
final class AutorizacaoTransacionalGeneroTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Impede a atualização quando a autoria muda durante o pedido.
     *
     * @since 2.0.0
     */
    #[Test]
    public function nao_atualiza_apos_alteracao_do_criador(): void
    {
        $criadorAnterior = Utilizador::factory()->create();
        $criadorAtual = Utilizador::factory()->create();

        $this->actingAs($criadorAnterior, 'sessao');

        $genero = Genero::factory()
            ->comNome('Doom Metal')
            ->create();

        self::assertSame(
            (int) $criadorAnterior->getKey(),
            $genero->criado_por_id,
        );

        $autoriaAlterada = false;

        $this->simularTransferenciaAutoria(
            $genero,
            $criadorAtual,
            $autoriaAlterada,
        );

        $this
            ->patchJson(
                route('generos.atualizar', $genero),
                [
                    'nome' => 'Epic Doom Metal',
                    'generos_pai' => [],
                ],
            )
            ->assertForbidden();

        self::assertTrue($autoriaAlterada);

        $this->assertDatabaseHas('generos', [
            'id' => $genero->getKey(),
            'nome' => 'Doom Metal',
            'criado_por_id' => $criadorAtual->getKey(),
            'deleted_at' => null,
        ]);

        $this->assertDatabaseMissing('generos', [
            'id' => $genero->getKey(),
            'nome' => 'Epic Doom Metal',
        ]);
    }

    /**
     * Impede a eliminação quando a autoria muda durante o pedido.
     *
     * @since 2.0.0
     */
    #[Test]
    public function nao_elimina_apos_alteracao_do_criador(): void
    {
        $criadorAnterior = Utilizador::factory()->create();
        $criadorAtual = Utilizador::factory()->create();

        $this->actingAs($criadorAnterior, 'sessao');

        $genero = Genero::factory()
            ->comNome('Black Metal')
            ->create();

        self::assertSame(
            (int) $criadorAnterior->getKey(),
            $genero->criado_por_id,
        );

        $autoriaAlterada = false;

        $this->simularTransferenciaAutoria(
            $genero,
            $criadorAtual,
            $autoriaAlterada,
        );

        $this
            ->deleteJson(
                route('generos.eliminar', $genero),
            )
            ->assertForbidden();

        self::assertTrue($autoriaAlterada);

        $this->assertDatabaseHas('generos', [
            'id' => $genero->getKey(),
            'nome' => 'Black Metal',
            'criado_por_id' => $criadorAtual->getKey(),
        ]);

        $this->assertNotSoftDeleted('generos', [
            'id' => $genero->getKey(),
        ]);
    }

    /**
     * Altera a autoria após a leitura inicial do género.
     *
     * @since 2.0.0
     */
    private function simularTransferenciaAutoria(
        Genero $genero,
        Utilizador $novoCriador,
        bool &$autoriaAlterada,
    ): void {
        Genero::retrieved(
            static function (Genero $registo) use (
                $genero,
                $novoCriador,
                &$autoriaAlterada,
            ): void {
                if (
                    $autoriaAlterada
                    || (int) $registo->getKey()
                        !== (int) $genero->getKey()
                ) {
                    return;
                }

                $autoriaAlterada = true;

                DB::table('generos')
                    ->where('id', $registo->getKey())
                    ->update([
                        'criado_por_id' => $novoCriador->getKey(),
                    ]);
            },
        );
    }
}
