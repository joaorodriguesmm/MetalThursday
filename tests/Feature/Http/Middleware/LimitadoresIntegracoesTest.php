<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Middleware;

use App\Enumeracoes\PapelUtilizador;
use App\Models\Autenticacao\Utilizador;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

/**
 * Testa os limites HTTP aplicados às integrações externas.
 *
 * @since 2.0.0
 */
final class LimitadoresIntegracoesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Desativa as esperas reais dos fornecedores externos.
     *
     * @since 2.0.0
     */
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('discogs.base_url', 'https://api.discogs.com');
        config()->set('discogs.user_agent', 'MetalThursdayTest/2.0');
        config()->set('discogs.token', 'token-de-teste');
        config()->set('discogs.intervalo_repeticao_ms', 0);
        config()->set('discogs.intervalo_minimo_pedidos_ms', 0);

        config()->set('musicbrainz.base_url', 'https://musicbrainz.org');
        config()->set('musicbrainz.user_agent', 'MetalThursdayTest/2.0');
        config()->set('musicbrainz.intervalo_repeticao_ms', 0);
        config()->set('musicbrainz.intervalo_minimo_pedidos_ms', 0);
    }

    /**
     * Confirma o limite Discogs e a separação do contador por utilizador.
     *
     * @since 2.0.0
     */
    #[Test]
    public function limita_discogs_por_utilizador(): void
    {
        self::assertSame(
            20,
            config('integracoes.limites_pedidos_http.discogs_por_minuto'),
        );

        config()->set(
            'integracoes.limites_pedidos_http.discogs_por_minuto',
            2,
        );

        Http::fake([
            'https://api.discogs.com/database/search*' => Http::response(
                ['results' => []],
                200,
            ),
        ]);

        $primeiroUtilizador = $this->criarAdministrador();
        $segundoUtilizador = $this->criarAdministrador();

        $this->limparLimite('discogs', $primeiroUtilizador);
        $this->limparLimite('discogs', $segundoUtilizador);

        for ($indice = 0; $indice < 2; $indice++) {
            $this
                ->actingAs($primeiroUtilizador, 'sessao')
                ->getJson(
                    route(
                        'lancamentos.importacao.pesquisar',
                        ['pesquisa' => 'Metallica'],
                    ),
                )
                ->assertOk();
        }

        $this
            ->actingAs($primeiroUtilizador, 'sessao')
            ->getJson(
                route(
                    'lancamentos.importacao.pesquisar',
                    ['pesquisa' => 'Metallica'],
                ),
            )
            ->assertStatus(Response::HTTP_TOO_MANY_REQUESTS)
            ->assertHeader('Retry-After');

        Http::assertSentCount(2);

        $this
            ->actingAs($segundoUtilizador, 'sessao')
            ->getJson(
                route(
                    'lancamentos.importacao.pesquisar',
                    ['pesquisa' => 'Metallica'],
                ),
            )
            ->assertOk();

        Http::assertSentCount(3);
    }

    /**
     * Confirma o limite da importação de artistas e a separação por utilizador.
     *
     * @since 2.0.0
     */
    #[Test]
    public function limita_importacao_artistas_por_utilizador(): void
    {
        self::assertSame(
            12,
            config('integracoes.limites_pedidos_http.artistas_por_minuto'),
        );

        config()->set(
            'integracoes.limites_pedidos_http.artistas_por_minuto',
            2,
        );

        Http::fake([
            'https://musicbrainz.org/ws/2/artist/*' => Http::response(
                ['artists' => []],
                200,
            ),
        ]);

        $primeiroUtilizador = $this->criarAdministrador();
        $segundoUtilizador = $this->criarAdministrador();

        $this->limparLimite('artistas', $primeiroUtilizador);
        $this->limparLimite('artistas', $segundoUtilizador);

        for ($indice = 0; $indice < 2; $indice++) {
            $this
                ->actingAs($primeiroUtilizador, 'sessao')
                ->getJson(
                    route(
                        'artistas.importacao.pesquisar',
                        ['pesquisa' => 'Moonspell'],
                    ),
                )
                ->assertOk();
        }

        $this
            ->actingAs($primeiroUtilizador, 'sessao')
            ->getJson(
                route(
                    'artistas.importacao.pesquisar',
                    ['pesquisa' => 'Moonspell'],
                ),
            )
            ->assertStatus(Response::HTTP_TOO_MANY_REQUESTS)
            ->assertHeader('Retry-After');

        Http::assertSentCount(2);

        $this
            ->actingAs($segundoUtilizador, 'sessao')
            ->getJson(
                route(
                    'artistas.importacao.pesquisar',
                    ['pesquisa' => 'Moonspell'],
                ),
            )
            ->assertOk();

        Http::assertSentCount(3);
    }

    /**
     * Cria um administrador com e-mail verificado.
     *
     * @return Utilizador Utilizador criado.
     *
     * @since 2.0.0
     */
    private function criarAdministrador(): Utilizador
    {
        return Utilizador::factory()
            ->comPapel(PapelUtilizador::Administrador)
            ->create([
                'email_verified_at' => now(),
            ]);
    }

    /**
     * Limpa o contador do limitador para um utilizador de teste.
     *
     * @since 2.0.0
     */
    private function limparLimite(
        string $integracao,
        Utilizador $utilizador,
    ): void {
        RateLimiter::clear(
            $integracao
                .':utilizador:'
                .(string) $utilizador->getKey(),
        );
    }
}
