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
}
