<?php

declare(strict_types=1);

namespace Tests\Feature\Models\Comum;

use App\Models\Autenticacao\Utilizador;
use App\Models\Comum\Ligacao;
use App\Models\Musica\Artista;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa a entidade genérica de ligações polimórficas.
 *
 * @since 2.0.0
 */
final class LigacaoTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Confirma que um artista pode possuir várias ligações ordenadas.
     *
     * @since 2.0.0
     */
    #[Test]
    public function associa_varias_ligacoes_a_um_artista(): void
    {
        $artista = Artista::factory()
            ->create([
                'origem_geografica_id' => null,
            ]);

        $artista
            ->ligacoes()
            ->createMany([
                [
                    'titulo' => 'Site oficial',

                    'url' => 'https://example.com',

                    'ordem' => 1,
                ],
                [
                    'titulo' => 'Bandcamp',

                    'url' => 'https://example.bandcamp.com',

                    'ordem' => 2,
                ],
            ]);

        $artista->load(
            'ligacoes.ligavel',
        );

        self::assertCount(
            2,
            $artista->ligacoes,
        );

        self::assertSame(
            [
                'Site oficial',
                'Bandcamp',
            ],
            $artista->ligacoes
                ->pluck(
                    'titulo',
                )
                ->all(),
        );

        self::assertTrue(
            $artista->ligacoes
                ->firstOrFail()
                ->ligavel
                ->is(
                    $artista,
                ),
        );
    }

    /**
     * Confirma que a mesma entidade de ligação pode pertencer a um utilizador.
     *
     * @since 2.0.0
     */
    #[Test]
    public function associa_ligacao_a_um_utilizador(): void
    {
        $utilizador = Utilizador::factory()
            ->create();

        $ligacao = $utilizador
            ->ligacoes()
            ->create([
                'titulo' => 'GitHub',

                'url' => 'https://github.com/exemplo',

                'ordem' => 1,
            ]);

        self::assertInstanceOf(
            Ligacao::class,
            $ligacao,
        );

        self::assertTrue(
            $ligacao->ligavel->is(
                $utilizador,
            ),
        );
    }

    /**
     * Confirma a normalização dos campos textuais de uma ligação.
     *
     * @since 2.0.0
     */
    #[Test]
    public function normaliza_titulo_e_url(): void
    {
        $ligacao = new Ligacao([
            'titulo' => '  Site   oficial  ',

            'url' => '  https://example.com/artista  ',

            'ordem' => 1,
        ]);

        self::assertSame(
            'Site oficial',
            $ligacao->titulo,
        );

        self::assertSame(
            'https://example.com/artista',
            $ligacao->url,
        );
    }

    /**
     * Confirma que uma ligação não aceita credenciais incorporadas.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_url_com_credenciais(): void
    {
        $this->expectException(
            InvalidArgumentException::class,
        );

        new Ligacao([
            'titulo' => 'Inválida',

            'url' => 'https://utilizador:segredo@example.com',

            'ordem' => 1,
        ]);
    }
}
