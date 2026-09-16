<?php

declare(strict_types=1);

namespace Tests\Feature\Servicos\Musica;

use App\Enumeracoes\TipoLancamento;
use App\Servicos\Musica\ServicoDiscogs;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa os metadados editoriais obtidos para uma Release Discogs.
 *
 * @since 2.0.0
 */
final class ServicoDiscogsMetadadosLancamentoTest extends TestCase
{
    /**
     * Configura a integração Discogs utilizada pelos testes.
     *
     * @since 2.0.0
     */
    protected function setUp(): void
    {
        parent::setUp();

        config()->set(
            'discogs.base_url',
            'https://api.discogs.com',
        );

        config()->set(
            'discogs.user_agent',
            'MetalThursdayTest/2.0',
        );

        config()->set(
            'discogs.tentativas',
            1,
        );

        config()->set(
            'discogs.intervalo_repeticao_ms',
            0,
        );

        config()->set(
            'discogs.intervalo_minimo_pedidos_ms',
            0,
        );
    }

    /**
     * Confirma que o ano original é obtido do Master e não da Release.
     *
     * @since 2.0.0
     */
    #[Test]
    public function obtem_ano_original_do_master(): void
    {
        Http::fake([
            'https://api.discogs.com/releases/249504' => Http::response(
                [
                    'id' => 249504,
                    'title' => 'Master Of Puppets',
                    'year' => 1991,
                    'master_id' => 12345,
                ],
                200,
            ),

            'https://api.discogs.com/masters/12345' => Http::response(
                [
                    'id' => 12345,
                    'year' => 1986,
                ],
                200,
            ),
        ]);

        $lancamento =
            app(
                ServicoDiscogs::class,
            )->obterLancamento(
                249504,
            );

        self::assertSame(
            1991,
            $lancamento['ano'],
        );

        self::assertSame(
            1986,
            $lancamento['ano_original'],
        );
    }

    /**
     * Confirma que o ano da Release é usado quando não existe Master.
     *
     * @since 2.0.0
     */
    #[Test]
    public function usa_ano_da_release_sem_master(): void
    {
        Http::fake([
            'https://api.discogs.com/releases/249504' => Http::response(
                [
                    'id' => 249504,
                    'title' => 'Master Of Puppets',
                    'year' => 1991,
                ],
                200,
            ),
        ]);

        $lancamento =
            app(
                ServicoDiscogs::class,
            )->obterLancamento(
                249504,
            );

        self::assertSame(
            1991,
            $lancamento['ano_original'],
        );
    }

    /**
     * Confirma que uma falha no Master mantém o ano da Release como sugestão.
     *
     * @since 2.0.0
     */
    #[Test]
    public function falha_do_master_usa_ano_da_release(): void
    {
        Http::fake([
            'https://api.discogs.com/releases/249504' => Http::response(
                [
                    'id' => 249504,
                    'title' => 'Master Of Puppets',
                    'year' => 1991,
                    'master_id' => 12345,
                ],
                200,
            ),

            'https://api.discogs.com/masters/12345' => Http::response(
                [],
                404,
            ),
        ]);

        $lancamento =
            app(
                ServicoDiscogs::class,
            )->obterLancamento(
                249504,
            );

        self::assertSame(
            1991,
            $lancamento['ano_original'],
        );
    }

    /**
     * Confirma que descrições editoriais inequívocas sugerem o tipo interno.
     *
     * @since 2.0.0
     */
    #[Test]
    public function sugere_tipo_de_lancamento_pelos_formatos(): void
    {
        Http::fake([
            'https://api.discogs.com/releases/1' => Http::response(
                [
                    'id' => 1,
                    'title' => 'Álbum de Estúdio',

                    'formats' => [
                        [
                            'name' => 'CD',

                            'descriptions' => [
                                'Album',
                            ],
                        ],
                    ],
                ],
                200,
            ),

            'https://api.discogs.com/releases/2' => Http::response(
                [
                    'id' => 2,
                    'title' => 'Registo ao Vivo',

                    'formats' => [
                        [
                            'name' => 'Vinyl',

                            'descriptions' => [
                                'Album',
                                'Live',
                            ],
                        ],
                    ],
                ],
                200,
            ),

            'https://api.discogs.com/releases/3' => Http::response(
                [
                    'id' => 3,
                    'title' => 'Single Sided sem tipo editorial',

                    'formats' => [
                        [
                            'name' => 'Vinyl',

                            'descriptions' => [
                                'Single Sided',
                            ],
                        ],
                    ],
                ],
                200,
            ),
        ]);

        $album =
            app(
                ServicoDiscogs::class,
            )->obterLancamento(
                1,
            );

        $aoVivo =
            app(
                ServicoDiscogs::class,
            )->obterLancamento(
                2,
            );

        $indeterminado =
            app(
                ServicoDiscogs::class,
            )->obterLancamento(
                3,
            );

        self::assertSame(
            TipoLancamento::AlbumEstudio->value,
            $album['tipo_sugerido'],
        );

        self::assertSame(
            TipoLancamento::AlbumAoVivo->value,
            $aoVivo['tipo_sugerido'],
        );

        self::assertNull(
            $indeterminado['tipo_sugerido'],
        );
    }
}
