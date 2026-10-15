<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Musica;

use App\Models\Autenticacao\Utilizador;
use App\Models\Musica\Artista;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa a revalidação transacional das permissões dos artistas.
 *
 * @since 2.0.0
 */
final class AutorizacaoTransacionalArtistaTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Rejeita a atualização quando a autoria muda durante o pedido.
     *
     * @since 2.0.0
     */
    #[Test]
    public function nao_atualiza_apos_alteracao_do_criador(): void
    {
        $criadorAnterior = Utilizador::factory()->create();
        $criadorAtual = Utilizador::factory()->create();

        $this->actingAs($criadorAnterior, 'sessao');

        $artista = Artista::factory()
            ->comNome('Artista Original')
            ->create();

        self::assertSame(
            (int) $criadorAnterior->getKey(),
            $artista->criado_por_id,
        );

        $autoriaAlterada = false;

        Artista::retrieved(
            static function (Artista $registo) use (
                $artista,
                $criadorAtual,
                &$autoriaAlterada,
            ): void {
                if (
                    $autoriaAlterada
                    || (int) $registo->getKey() !== (int) $artista->getKey()
                ) {
                    return;
                }

                $autoriaAlterada = true;

                DB::table('artistas')
                    ->where('id', $registo->getKey())
                    ->update([
                        'criado_por_id' => $criadorAtual->getKey(),
                    ]);
            },
        );

        $this
            ->patchJson(
                route('artistas.atualizar', $artista),
                [
                    'nome' => 'Alteracao Indevida',
                    'generos' => [],
                ],
            )
            ->assertForbidden();

        self::assertTrue($autoriaAlterada);

        $this->assertDatabaseHas('artistas', [
            'id' => $artista->getKey(),
            'nome' => 'Artista Original',
            'criado_por_id' => $criadorAtual->getKey(),
            'deleted_at' => null,
        ]);

        $this->assertDatabaseMissing('artistas', [
            'id' => $artista->getKey(),
            'nome' => 'Alteracao Indevida',
        ]);
    }

    /**
     * Rejeita a eliminação quando a autoria muda durante o pedido.
     *
     * @since 2.0.0
     */
    #[Test]
    public function nao_elimina_apos_alteracao_do_criador(): void
    {
        $criadorAnterior = Utilizador::factory()->create();
        $criadorAtual = Utilizador::factory()->create();

        $this->actingAs($criadorAnterior, 'sessao');

        $artista = Artista::factory()
            ->comNome('Artista Protegido')
            ->create();

        self::assertSame(
            (int) $criadorAnterior->getKey(),
            $artista->criado_por_id,
        );

        $autoriaAlterada = false;

        Artista::retrieved(
            static function (Artista $registo) use (
                $artista,
                $criadorAtual,
                &$autoriaAlterada,
            ): void {
                if (
                    $autoriaAlterada
                    || (int) $registo->getKey() !== (int) $artista->getKey()
                ) {
                    return;
                }

                $autoriaAlterada = true;

                DB::table('artistas')
                    ->where('id', $registo->getKey())
                    ->update([
                        'criado_por_id' => $criadorAtual->getKey(),
                    ]);
            },
        );

        $this
            ->deleteJson(
                route('artistas.eliminar', $artista),
            )
            ->assertForbidden();

        self::assertTrue($autoriaAlterada);

        $this->assertDatabaseHas('artistas', [
            'id' => $artista->getKey(),
            'nome' => 'Artista Protegido',
            'criado_por_id' => $criadorAtual->getKey(),
        ]);

        $this->assertNotSoftDeleted('artistas', [
            'id' => $artista->getKey(),
        ]);
    }
}
