<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Requests\Musica;

use App\Http\Requests\Musica\CriarArtistaRequest;
use App\Models\Musica\Artista;
use App\Models\Musica\Genero;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Validator as Validador;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa a validação dos metadados enriquecidos do perfil dos artistas.
 *
 * @since 2.0.0
 */
final class PerfilArtistaRequestTest extends TestCase
{
    use RefreshDatabase;

    /** Confirma que todos os novos campos podem ser enviados em conjunto. */
    #[Test]
    public function aceita_perfil_completo_na_criacao(): void
    {
        $genero = Genero::factory()->create();

        $validador = $this->criarValidador([
            'nome' => 'Moonspell',
            'ano_inicio_atividade' => 1992,
            'ano_fim_atividade' => null,
            'estado_atividade' => 'ativo',
            'biografia' => 'Banda portuguesa de metal.',
            'imagem' => 'https://static.example.com/moonspell.jpg',
            'discogs_id' => 12345,
            'ligacoes' => [
                [
                    'titulo' => 'Site oficial',
                    'url' => 'https://www.moonspell.com',
                ],
                [
                    'titulo' => 'Bandcamp',
                    'url' => 'https://moonspell.bandcamp.com',
                ],
            ],
            'generos' => [
                (int) $genero->getKey(),
            ],
        ]);

        self::assertFalse(
            $validador->fails(),
            implode(PHP_EOL, $validador->errors()->all()),
        );

        $dadosValidados = $validador->validated();

        foreach (
            [
                'ano_inicio_atividade',
                'ano_fim_atividade',
                'estado_atividade',
                'biografia',
                'imagem',
                'discogs_id',
                'ligacoes',
            ] as $campo
        ) {
            self::assertArrayHasKey($campo, $dadosValidados);
        }

        self::assertSame(
            'https://static.example.com/moonspell.jpg',
            $dadosValidados['imagem'],
        );

        self::assertCount(2, $dadosValidados['ligacoes']);
    }

    #[Test]
    public function rejeita_ano_inicio_futuro(): void
    {
        $validador = $this->criarValidadorBase([
            'ano_inicio_atividade' => (int) date('Y') + 1,
        ]);

        self::assertTrue($validador->errors()->has('ano_inicio_atividade'));
    }

    #[Test]
    public function rejeita_ano_fim_anterior_ao_inicio(): void
    {
        $validador = $this->criarValidadorBase([
            'ano_inicio_atividade' => 2000,
            'ano_fim_atividade' => 1999,
        ]);

        self::assertTrue($validador->errors()->has('ano_fim_atividade'));
    }

    #[Test]
    public function rejeita_estado_atividade_desconhecido(): void
    {
        $validador = $this->criarValidadorBase([
            'estado_atividade' => 'desconhecido',
        ]);

        self::assertTrue($validador->errors()->has('estado_atividade'));
    }

    #[Test]
    public function rejeita_ligacao_com_credenciais(): void
    {
        $validador = $this->criarValidadorBase([
            'ligacoes' => [
                [
                    'titulo' => 'Inválida',
                    'url' => 'https://utilizador:segredo@example.com',
                ],
            ],
        ]);

        self::assertTrue($validador->errors()->has('ligacoes.0.url'));
    }

    #[Test]
    public function rejeita_discogs_id_ja_associado(): void
    {
        self::assertTrue(Schema::hasColumn('artistas', 'discogs_id'));

        Artista::factory()->create([
            'discogs_id' => 12345,
        ]);

        $validador = $this->criarValidadorBase([
            'discogs_id' => 12345,
        ]);

        self::assertTrue($validador->errors()->has('discogs_id'));
    }

    /** Confirma que a imagem é sempre um endereço externo HTTP/HTTPS seguro. */
    #[Test]
    public function rejeita_imagem_que_nao_e_url_externa_segura(): void
    {
        foreach (
            [
                '/storage/fotografias/artistas/artista.jpg',
                'file:///tmp/artista.jpg',
                'https://utilizador:segredo@example.com/artista.jpg',
            ] as $imagem
        ) {
            $validador = $this->criarValidadorBase([
                'imagem' => $imagem,
            ]);

            self::assertTrue(
                $validador->errors()->has('imagem'),
                sprintf('A imagem "%s" deveria ter sido rejeitada.', $imagem),
            );
        }
    }

    /**
     * @param  array<string, mixed>  $dados
     */
    private function criarValidador(array $dados): Validador
    {
        $pedido = new CriarArtistaRequest;

        return Validator::make(
            $dados,
            $pedido->rules(),
            $pedido->messages(),
            $pedido->attributes(),
        );
    }

    /**
     * @param  array<string, mixed>  $substituicoes
     */
    private function criarValidadorBase(array $substituicoes): Validador
    {
        $genero = Genero::factory()->create();

        return $this->criarValidador([
            'nome' => 'Moonspell',
            'generos' => [
                (int) $genero->getKey(),
            ],
            ...$substituicoes,
        ]);
    }
}
