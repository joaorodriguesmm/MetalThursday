<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Musica;

use App\Enumeracoes\PapelUtilizador;
use App\Enumeracoes\TipoLancamento;
use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\MetalThursday;
use App\Models\MetalThursday\ReservaMetalThursday;
use App\Models\Musica\Lancamento;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa a autorização durante a importação de lançamentos.
 *
 * @since 2.0.0
 */
final class AutorizacaoTransacionalImportacaoLancamentoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('discogs.base_url', 'https://api.discogs.com');
        config()->set('discogs.user_agent', 'MetalThursdayTest/2.0');
        config()->set('discogs.token', 'token-de-teste');
        config()->set('discogs.intervalo_repeticao_ms', 0);
        config()->set('discogs.intervalo_minimo_pedidos_ms', 0);
    }

    /**
     * Impede a importação quando a reserva muda de responsável
     * durante a consulta ao Discogs.
     *
     * @since 2.0.0
     */
    #[Test]
    public function nao_importa_apos_transferencia_da_reserva(): void
    {
        $utilizador = Utilizador::factory()->create([
            'email_verified_at' => now(),
        ]);

        $novoResponsavel = Utilizador::factory()->create();

        $reserva = ReservaMetalThursday::factory()
            ->comData(CarbonImmutable::parse('2026-01-15'))
            ->comResponsavel($utilizador)
            ->create();

        $reservaTransferida = false;

        Http::fake(
            static function (Request $pedido) use (
                $reserva,
                $novoResponsavel,
                &$reservaTransferida,
            ) {
                if (
                    ! $reservaTransferida
                    && $pedido->url()
                    === 'https://api.discogs.com/releases/249504'
                ) {
                    DB::table($reserva->getTable())
                        ->where('id', $reserva->getKey())
                        ->update([
                            'responsavel_id' => $novoResponsavel->getKey(),
                        ]);

                    $reservaTransferida = true;
                }

                return Http::response([
                    'id' => 249504,
                    'title' => 'Master Of Puppets',
                    'artists' => [],
                    'tracklist' => [],
                ], 200);
            },
        );

        $resposta = $this
            ->actingAs($utilizador, 'sessao')
            ->postJson(
                route(
                    'lancamentos.importacao.importar',
                    ['identificadorDiscogs' => 249504],
                ),
            );

        self::assertTrue(
            $reservaTransferida,
            'A transferencia da reserva deve ocorrer durante a consulta.',
        );

        $resposta->assertForbidden();

        $this->assertDatabaseMissing('lancamentos', [
            'discogs_release_id' => 249504,
        ]);

        $this->assertDatabaseHas($reserva->getTable(), [
            'id' => $reserva->getKey(),
            'responsavel_id' => $novoResponsavel->getKey(),
        ]);
    }

    /**
     * Impede a importação quando a autoria e a titularidade
     * da MetalThursday mudam durante a consulta ao Discogs.
     *
     * @since 2.0.0
     */
    #[Test]
    public function nao_importa_apos_transferencia_da_metal_thursday(): void
    {
        $utilizador = Utilizador::factory()->create([
            'email_verified_at' => now(),
        ]);

        $novoResponsavel = Utilizador::factory()->create();

        $metalThursday = MetalThursday::factory()->create([
            'autor_id' => $utilizador->getKey(),
            'criado_por_id' => $utilizador->getKey(),
        ]);

        $metalThursdayTransferida = false;

        Http::fake(
            static function (Request $pedido) use (
                $metalThursday,
                $novoResponsavel,
                &$metalThursdayTransferida,
            ) {
                if (
                    ! $metalThursdayTransferida
                    && $pedido->url()
                    === 'https://api.discogs.com/releases/249504'
                ) {
                    DB::table($metalThursday->getTable())
                        ->where('id', $metalThursday->getKey())
                        ->update([
                            'autor_id' => $novoResponsavel->getKey(),
                            'criado_por_id' => $novoResponsavel->getKey(),
                        ]);

                    $metalThursdayTransferida = true;
                }

                return Http::response([
                    'id' => 249504,
                    'title' => 'Master Of Puppets',
                    'artists' => [],
                    'tracklist' => [],
                ], 200);
            },
        );

        $resposta = $this
            ->actingAs($utilizador, 'sessao')
            ->postJson(
                route(
                    'lancamentos.importacao.importar',
                    [
                        'identificadorDiscogs' => 249504,
                        'metal_thursday_id' => $metalThursday->getKey(),
                    ],
                ),
            );

        self::assertTrue(
            $metalThursdayTransferida,
            'A transferencia deve ocorrer durante a consulta.',
        );

        $resposta->assertForbidden();

        $this->assertDatabaseMissing('lancamentos', [
            'discogs_release_id' => 249504,
        ]);

        $this->assertDatabaseHas($metalThursday->getTable(), [
            'id' => $metalThursday->getKey(),
            'autor_id' => $novoResponsavel->getKey(),
            'criado_por_id' => $novoResponsavel->getKey(),
        ]);
    }

    /**
     * Impede a reutilização de um lançamento completo quando
     * o utilizador perde a reserva após a autorização inicial.
     *
     * @since 2.0.0
     */
    #[Test]
    public function nao_reutiliza_lancamento_apos_transferencia_da_reserva(): void
    {
        $utilizador = Utilizador::factory()->create([
            'email_verified_at' => now(),
        ]);

        $novoResponsavel = Utilizador::factory()->create();

        $reserva = ReservaMetalThursday::factory()
            ->comData(CarbonImmutable::parse('2026-01-15'))
            ->comResponsavel($utilizador)
            ->create();

        $lancamento = Lancamento::factory()->create([
            'titulo' => 'Master Of Puppets',
            'tipo' => TipoLancamento::AlbumEstudio,
            'ano_original' => 1986,
            'discogs_release_id' => 249504,
        ]);

        $reservaTransferida = false;

        Lancamento::retrieved(
            static function (Lancamento $encontrado) use (
                $reserva,
                $novoResponsavel,
                &$reservaTransferida,
            ): void {
                if (
                    $reservaTransferida
                    || (int) $encontrado->discogs_release_id !== 249504
                ) {
                    return;
                }

                DB::table($reserva->getTable())
                    ->where('id', $reserva->getKey())
                    ->update([
                        'responsavel_id' => $novoResponsavel->getKey(),
                    ]);

                $reservaTransferida = true;
            },
        );

        Http::fake();

        $resposta = $this
            ->actingAs($utilizador, 'sessao')
            ->postJson(
                route(
                    'lancamentos.importacao.importar',
                    [
                        'identificadorDiscogs' => 249504,
                    ],
                ),
            );

        self::assertTrue(
            $reservaTransferida,
            'A reserva deve ser transferida durante a leitura do lançamento.',
        );

        $resposta->assertForbidden();

        $this->assertDatabaseHas('lancamentos', [
            'id' => $lancamento->getKey(),
            'titulo' => 'Master Of Puppets',
            'discogs_release_id' => 249504,
        ]);

        self::assertSame(
            1,
            Lancamento::withTrashed()
                ->where('discogs_release_id', 249504)
                ->count(),
        );

        Http::assertNothingSent();
    }

    /**
     * Impede a importação quando um administrador perde os privilégios
     * durante a consulta ao Discogs e não possui reserva pendente.
     *
     * @since 2.0.0
     */
    #[Test]
    public function nao_importa_apos_revogacao_de_privilegios(): void
    {
        $administrador = Utilizador::factory()
            ->comPapel(PapelUtilizador::Administrador)
            ->create([
                'email_verified_at' => now(),
            ]);

        $privilegiosRevogados = false;

        Http::fake(
            static function (Request $pedido) use (
                $administrador,
                &$privilegiosRevogados,
            ) {
                if (
                    ! $privilegiosRevogados
                    && $pedido->url()
                    === 'https://api.discogs.com/releases/249504'
                ) {
                    DB::table($administrador->getTable())
                        ->where('id', $administrador->getKey())
                        ->update([
                            'papel' => PapelUtilizador::Utilizador->value,
                        ]);

                    $privilegiosRevogados = true;
                }

                return Http::response([
                    'id' => 249504,
                    'title' => 'Master Of Puppets',
                    'artists' => [],
                    'tracklist' => [],
                ], 200);
            },
        );

        $resposta = $this
            ->actingAs($administrador, 'sessao')
            ->postJson(
                route(
                    'lancamentos.importacao.importar',
                    [
                        'identificadorDiscogs' => 249504,
                    ],
                ),
            );

        self::assertTrue(
            $privilegiosRevogados,
            'Os privilégios devem ser revogados durante a consulta.',
        );

        $resposta->assertForbidden();

        $this->assertDatabaseMissing('lancamentos', [
            'discogs_release_id' => 249504,
        ]);

        $this->assertDatabaseHas($administrador->getTable(), [
            'id' => $administrador->getKey(),
            'papel' => PapelUtilizador::Utilizador->value,
        ]);
    }
}
