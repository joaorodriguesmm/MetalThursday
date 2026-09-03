<?php

declare(strict_types=1);

namespace Tests\Feature\Models\Musica;

use App\Enumeracoes\EstadoAtividadeArtista;
use App\Models\Geografia\OrigemGeografica;
use App\Models\Musica\Artista;
use App\Models\Musica\Genero;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/** Testa os metadados enriquecidos do perfil dos artistas. */
final class PerfilArtistaTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function persiste_metadados_do_perfil(): void
    {
        $artista = Artista::factory()->create([
            'origem_geografica_id' => null,
            'ano_inicio_atividade' => 1989,
            'ano_fim_atividade' => 2017,
            'estado_atividade' => EstadoAtividadeArtista::Terminado,
            'biografia' => "Primeiro parágrafo.\n\nSegundo parágrafo.",
            'imagem' => 'https://static.example.com/artista.jpg',
            'discogs_id' => 12345,
        ]);

        $artista->refresh();

        self::assertSame(1989, $artista->ano_inicio_atividade);
        self::assertSame(2017, $artista->ano_fim_atividade);
        self::assertSame(EstadoAtividadeArtista::Terminado, $artista->estado_atividade);
        self::assertSame(
            "Primeiro parágrafo.\n\nSegundo parágrafo.",
            $artista->biografia,
        );
        self::assertSame(
            'https://static.example.com/artista.jpg',
            $artista->imagem,
        );
        self::assertSame(
            'https://static.example.com/artista.jpg',
            $artista->url_imagem,
        );
        self::assertSame(12345, $artista->discogs_id);
        self::assertSame(
            'https://www.discogs.com/artist/12345',
            $artista->url_discogs,
        );
    }

    #[Test]
    public function rejeita_imagem_que_nao_e_url_externa(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Artista::factory()->create([
            'imagem' => '/storage/fotografias/artistas/artista.jpg',
        ]);
    }

    #[Test]
    public function rotulo_selecao_inclui_ano_inicio_atividade(): void
    {
        $origem = OrigemGeografica::factory()->create([
            'nome' => 'Suécia',
            'codigo' => 'SE',
        ]);

        $genero = Genero::factory()->create([
            'nome' => 'Heavy Metal',
        ]);

        $artista = Artista::factory()->create([
            'nome' => 'Ghost',
            'origem_geografica_id' => $origem->getKey(),
            'ano_inicio_atividade' => 2006,
        ]);

        $artista->generos()->attach($genero->getKey());
        $artista->load([
            'origemGeografica:id,nome',
            'generos:id,nome',
        ]);

        self::assertSame(
            'Ghost — Suécia · 2006 · Heavy Metal',
            $artista->obterRotuloSelecao(),
        );
    }

    #[Test]
    public function permite_perfil_sem_metadados_adicionais(): void
    {
        self::assertTrue(
            Schema::hasColumns(
                'artistas',
                [
                    'ano_inicio_atividade',
                    'ano_fim_atividade',
                    'estado_atividade',
                    'biografia',
                    'imagem',
                    'discogs_id',
                ],
            ),
        );

        $artista = Artista::factory()->create([
            'origem_geografica_id' => null,
        ]);

        self::assertNull($artista->ano_inicio_atividade);
        self::assertNull($artista->ano_fim_atividade);
        self::assertNull($artista->estado_atividade);
        self::assertNull($artista->biografia);
        self::assertNull($artista->imagem);
        self::assertNull($artista->discogs_id);
        self::assertNull($artista->url_discogs);
    }
}
