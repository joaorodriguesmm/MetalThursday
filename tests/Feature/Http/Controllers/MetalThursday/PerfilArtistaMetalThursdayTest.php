<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\MetalThursday;

use App\Enumeracoes\PapelUtilizador;
use App\Models\Autenticacao\Utilizador;
use App\Models\Geografia\OrigemGeografica;
use App\Models\Musica\Artista;
use App\Models\Musica\Genero;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa a integração do perfil enriquecido dos artistas nos formulários de
 * MetalThursday.
 *
 * @since 2.0.0
 */
final class PerfilArtistaMetalThursdayTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Confirma que as opções de artista carregam o ano de início necessário
     * ao rótulo contextual.
     *
     * @since 2.0.0
     */
    #[Test]
    public function formulario_apresenta_ano_inicio_no_rotulo_do_artista(): void
    {
        $administrador = Utilizador::factory()
            ->comPapel(
                PapelUtilizador::Administrador,
            )
            ->create([
                'email_verified_at' => now(),
            ]);

        $origem = OrigemGeografica::factory()
            ->create([
                'nome' => 'Suécia',

                'codigo' => 'SE',
            ]);

        $genero = Genero::factory()
            ->create([
                'nome' => 'Heavy Metal',
            ]);

        $artista = Artista::factory()
            ->create([
                'nome' => 'Ghost',

                'origem_geografica_id' => $origem->getKey(),

                'ano_inicio_atividade' => 2006,
            ]);

        $artista
            ->generos()
            ->attach(
                $genero->getKey(),
            );

        $this
            ->actingAs(
                $administrador,
                'sessao',
            )
            ->get(
                route(
                    'metal-thursday.criar',
                ),
            )
            ->assertOk()
            ->assertSee(
                'Ghost — Suécia · 2006 · Heavy Metal',
            );
    }

    /**
     * Confirma que os géneros são opcionais na criação rápida de artistas.
     *
     * @since 2.0.0
     */
    #[Test]
    public function modal_criacao_rapida_apresenta_generos_como_opcionais(): void
    {
        $administrador = Utilizador::factory()
            ->comPapel(
                PapelUtilizador::Administrador,
            )
            ->create([
                'email_verified_at' => now(),
            ]);

        $resposta = $this
            ->actingAs(
                $administrador,
                'sessao',
            )
            ->get(
                route(
                    'metal-thursday.criar',
                ),
            );

        $resposta->assertOk();

        self::assertMatchesRegularExpression(
            '/<label\b(?=[^>]*\bfor="generos-novo-artista")[^>]*>(?:(?!<\/label>).)*Géneros(?:(?!<\/label>).)*\(opcional\)(?:(?!<\/label>).)*<\/label>/s',
            $resposta->getContent(),
        );

        self::assertDoesNotMatchRegularExpression(
            '/<select\b(?=[^>]*\bid="generos-novo-artista")[^>]*\brequired\b[^>]*>/s',
            $resposta->getContent(),
        );
    }

    /**
     * Confirma que um artista sem géneros mantém um rótulo de seleção válido.
     *
     * @since 2.0.0
     */
    #[Test]
    public function formulario_apresenta_rotulo_valido_para_artista_sem_generos(): void
    {
        $administrador = Utilizador::factory()
            ->comPapel(
                PapelUtilizador::Administrador,
            )
            ->create([
                'email_verified_at' => now(),
            ]);

        $origem = OrigemGeografica::factory()
            ->create([
                'nome' => 'Portugal',

                'codigo' => 'PT',
            ]);

        $artista = Artista::factory()
            ->create([
                'nome' => 'Moonspell',

                'origem_geografica_id' => $origem->getKey(),
            ]);

        $resposta = $this
            ->actingAs(
                $administrador,
                'sessao',
            )
            ->get(
                route(
                    'metal-thursday.criar',
                ),
            );

        $resposta->assertOk();

        self::assertMatchesRegularExpression(
            '/<option\b(?=[^>]*\bvalue="' . preg_quote((string) $artista->getKey(), '/') . '")[^>]*>\s*Moonspell — Portugal\s*<\/option>/s',
            $resposta->getContent(),
        );
    }
}
