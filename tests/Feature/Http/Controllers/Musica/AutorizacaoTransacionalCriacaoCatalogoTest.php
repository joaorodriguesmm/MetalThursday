<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Musica;

use App\Enumeracoes\PapelUtilizador;
use App\Models\Autenticacao\Utilizador;
use App\Models\Musica\Artista;
use App\Models\Musica\Genero;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Confirma que a suspensão durante a criação impede novas escritas no catálogo.
 *
 * @since 2.0.0
 */
final class AutorizacaoTransacionalCriacaoCatalogoTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A criação de um artista não prossegue se a conta foi suspensa.
     *
     * @since 2.0.0
     */
    #[Test]
    public function suspensao_durante_pedido_impede_criacao_artista(): void
    {
        $utilizador = Utilizador::factory()->create();
        $responsavel = Utilizador::factory()
            ->comPapel(PapelUtilizador::SuperAdministrador)
            ->create();

        $suspensaoEfetuada = $this->suspenderAposBloqueio(
            'bloqueios_nomes_artistas',
            $utilizador,
            $responsavel,
        );

        $this->actingAs($utilizador, 'sessao')
            ->postJson(route('artistas.guardar'), [
                'nome' => 'Artista protegido contra suspensão',
                'generos' => [],
            ])
            ->assertForbidden();

        self::assertTrue($suspensaoEfetuada());
        $this->assertDatabaseMissing('artistas', [
            'nome' => 'Artista protegido contra suspensão',
        ]);
    }

    /**
     * A criação de um género não prossegue se a conta foi suspensa.
     *
     * @since 2.0.0
     */
    #[Test]
    public function suspensao_durante_pedido_impede_criacao_genero(): void
    {
        $utilizador = Utilizador::factory()->create();
        $responsavel = Utilizador::factory()
            ->comPapel(PapelUtilizador::SuperAdministrador)
            ->create();

        $suspensaoEfetuada = $this->suspenderAposBloqueio(
            'generos',
            $utilizador,
            $responsavel,
        );

        $this->actingAs($utilizador, 'sessao')
            ->postJson(route('generos.guardar'), [
                'nome' => 'Género protegido contra suspensão',
                'generos_pai' => [],
            ])
            ->assertForbidden();

        self::assertTrue($suspensaoEfetuada());
        $this->assertDatabaseMissing('generos', [
            'nome' => 'Género protegido contra suspensão',
        ]);
    }

    /**
     * A revogação do papel de superadministrador impede editar artista alheio.
     *
     * @since 2.0.0
     */
    #[Test]
    public function revogacao_durante_pedido_impede_edicao_artista_alheio(): void
    {
        $criador = Utilizador::factory()->create();
        $superAdministrador = Utilizador::factory()
            ->comPapel(PapelUtilizador::SuperAdministrador)
            ->create();

        $this->actingAs($criador, 'sessao');
        $artista = Artista::factory()->comNome('Artista original')->create();

        $this->despromoverAposLeitura(
            Artista::class,
            $artista->id,
            $superAdministrador,
        );

        $this->actingAs($superAdministrador, 'sessao')
            ->patchJson(route('artistas.atualizar', $artista), [
                'nome' => 'Artista indevidamente alterado',
                'generos' => [],
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('artistas', [
            'id' => $artista->id,
            'nome' => 'Artista original',
        ]);
    }

    /**
     * A revogação do papel de superadministrador impede editar género alheio.
     *
     * @since 2.0.0
     */
    #[Test]
    public function revogacao_durante_pedido_impede_edicao_genero_alheio(): void
    {
        $criador = Utilizador::factory()->create();
        $superAdministrador = Utilizador::factory()
            ->comPapel(PapelUtilizador::SuperAdministrador)
            ->create();

        $this->actingAs($criador, 'sessao');
        $genero = Genero::factory()->comNome('Doom Metal')->create();

        $this->despromoverAposLeitura(
            Genero::class,
            $genero->id,
            $superAdministrador,
        );

        $this->actingAs($superAdministrador, 'sessao')
            ->patchJson(route('generos.atualizar', $genero), [
                'nome' => 'Doom Metal indevidamente alterado',
                'generos_pai' => [],
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('generos', [
            'id' => $genero->id,
            'nome' => 'Doom Metal',
        ]);
    }

    /**
     * Simula a perda do papel privilegiado depois do route model binding.
     *
     * @param  class-string<Artista|Genero>  $modelo  Modelo observado.
     */
    private function despromoverAposLeitura(
        string $modelo,
        int $identificador,
        Utilizador $superAdministrador,
    ): void {
        $leituras = 0;

        $modelo::retrieved(static function (
            Artista|Genero $registo,
        ) use (
            $identificador,
            $superAdministrador,
            &$leituras,
        ): void {
            if ($registo->id !== $identificador) {
                return;
            }

            $leituras++;

            if ($leituras !== 2) {
                return;
            }

            DB::table('utilizadores')
                ->where('id', $superAdministrador->id)
                ->update(['papel' => PapelUtilizador::Utilizador->value]);
        });
    }

    /**
     * Simula a suspensão depois de bloquear os registos do catálogo.
     *
     * @return \Closure(): bool Indica se a suspensão foi simulada.
     */
    private function suspenderAposBloqueio(
        string $tabela,
        Utilizador $utilizador,
        Utilizador $responsavel,
    ): \Closure {
        $suspendeu = false;

        DB::listen(static function (QueryExecuted $consulta) use (
            $tabela,
            $utilizador,
            $responsavel,
            &$suspendeu,
        ): void {
            $sql = strtolower($consulta->sql);

            if ($suspendeu
                || ! str_contains($sql, $tabela)
                || ! str_contains($sql, 'for update')
            ) {
                return;
            }

            $suspendeu = true;

            DB::table('utilizadores')
                ->where('id', $utilizador->id)
                ->update([
                    'suspenso_em' => now(),
                    'motivo_suspensao' => 'Suspensão durante a criação.',
                    'suspenso_por_id' => $responsavel->id,
                ]);
        });

        return static function () use (&$suspendeu): bool {
            return $suspendeu;
        };
    }
}
