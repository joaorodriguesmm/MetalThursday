<?php

declare(strict_types=1);

namespace Tests\Feature\Servicos\Musica;

use App\Servicos\Musica\ServicoDiscogs;
use App\Servicos\Musica\ServicoMusicBrainz;
use App\Servicos\Musica\ServicoTheAudioDB;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\RequestInterface;
use RuntimeException;
use Tests\TestCase;

/**
 * Garante que as integracoes nao seguem destinos indicados em redirecionamentos.
 *
 * @since 2.0.0
 */
final class ProtecaoRedirecionamentosIntegracoesTest extends TestCase
{
    /**
     * Confirma as opcoes efetivas do cliente Guzzle nos tres servicos.
     *
     * O middleware observa as opcoes mesmo quando a resposta HTTP e simulada.
     * Um teste que verificasse apenas o codigo 302 nao detetaria a ausencia
     * de protecao, porque o Http::fake nao segue redirecionamentos.
     *
     * @since 2.0.0
     */
    #[Test]
    public function integracoes_desativam_redirecionamentos_http(): void
    {
        foreach (['discogs', 'musicbrainz', 'theaudiodb'] as $integracao) {
            config()->set($integracao.'.tentativas', 1);
            config()->set($integracao.'.intervalo_repeticao_ms', 0);
            config()->set($integracao.'.intervalo_minimo_pedidos_ms', 0);
        }

        config()->set('discogs.base_url', 'https://api.discogs.com');
        config()->set('discogs.user_agent', 'MetalThursdayTest/2.0');
        config()->set('discogs.token', 'token-ficticio');
        config()->set('musicbrainz.base_url', 'https://musicbrainz.org');
        config()->set('musicbrainz.user_agent', 'MetalThursdayTest/2.0');
        config()->set('theaudiodb.base_url', 'https://www.theaudiodb.com');
        config()->set('theaudiodb.api_key', '123');

        /** @var list<mixed> $opcoesRedirecionamentos */
        $opcoesRedirecionamentos = [];

        Http::globalMiddleware(
            static function (callable $proximo) use (&$opcoesRedirecionamentos): callable {
                return static function (
                    RequestInterface $pedido,
                    array $opcoes,
                ) use ($proximo, &$opcoesRedirecionamentos): PromiseInterface {
                    $opcoesRedirecionamentos[] = $opcoes['allow_redirects'] ?? null;

                    /** @var PromiseInterface $promessa */
                    $promessa = $proximo($pedido, $opcoes);

                    return $promessa;
                };
            },
        );

        Http::fake([
            'https://api.discogs.com/database/search*' => Http::response(
                '',
                302,
                ['Location' => 'http://127.0.0.1/privado'],
            ),
            'https://musicbrainz.org/ws/2/artist/*' => Http::response(
                '',
                302,
                ['Location' => 'http://127.0.0.1/privado'],
            ),
            'https://www.theaudiodb.com/api/v1/json/123/artist-mb.php*' => Http::response(
                '',
                302,
                ['Location' => 'http://127.0.0.1/privado'],
            ),
        ]);

        $pedidos = [
            static fn (): array => app(ServicoDiscogs::class)
                ->pesquisarLancamentos('Metallica'),
            static fn (): array => app(ServicoMusicBrainz::class)
                ->pesquisarArtistas('Metallica'),
            static fn (): ?array => app(ServicoTheAudioDB::class)
                ->obterArtistaPorMusicBrainz(
                    '65f4f0c5-ef9e-490c-aee3-909e7ae6b2ab',
                ),
        ];

        foreach ($pedidos as $pedido) {
            try {
                $pedido();
                self::fail('Um redirecionamento HTTP nao pode ser aceite.');
            } catch (RuntimeException $excecao) {
                self::assertStringContainsString(
                    'código HTTP 302',
                    $excecao->getMessage(),
                );
            }
        }

        Http::assertSentCount(3);

        self::assertSame(
            [false, false, false],
            $opcoesRedirecionamentos,
        );
    }
}
