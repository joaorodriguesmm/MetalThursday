<?php

declare(strict_types=1);

namespace Tests\Feature\Factories\MetalThursday;

use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\Edicao;
use App\Models\MetalThursday\MusicaFavoritaEdicao;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa os estados personalizados da factory das músicas favoritas.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
final class FactoriesMusicasFavoritasEdicaoTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Confirma que todos os estados personalizados são aplicados.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function cria_musica_favorita_com_estados_personalizados(): void
    {
        $edicao = Edicao::factory()
            ->create();

        $proprietario = Utilizador::factory()
            ->create();

        $registador = Utilizador::factory()
            ->create();

        $musicaFavorita = MusicaFavoritaEdicao::factory()
            ->paraEdicao(
                $edicao,
            )
            ->pertencenteA(
                $proprietario,
            )
            ->registadaPor(
                $registador,
            )
            ->comPosicao(
                2,
            )
            ->comMusica(
                "  Banda\t—\nMúsica favorita  ",
            )
            ->create();

        self::assertSame(
            $edicao->getKey(),
            $musicaFavorita->edicao_id,
        );

        self::assertSame(
            $proprietario->getKey(),
            $musicaFavorita->utilizador_id,
        );

        self::assertSame(
            $registador->getKey(),
            $musicaFavorita->registado_por_id,
        );

        self::assertSame(
            2,
            $musicaFavorita->posicao,
        );

        self::assertSame(
            'Banda — Música favorita',
            $musicaFavorita->musica,
        );
    }

    /**
     * Confirma que o estado sem registador remove a associação opcional.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function cria_musica_favorita_sem_registador(): void
    {
        $musicaFavorita = MusicaFavoritaEdicao::factory()
            ->semRegistador()
            ->create();

        self::assertNull(
            $musicaFavorita->registado_por_id,
        );
    }

    /**
     * Confirma que a factory utiliza os limites públicos do modelo.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function rejeita_posicao_fora_do_intervalo(): void
    {
        $this->expectException(
            InvalidArgumentException::class,
        );

        MusicaFavoritaEdicao::factory()
            ->comPosicao(
                MusicaFavoritaEdicao::POSICAO_MAXIMA + 1,
            );
    }

    /**
     * Confirma que a factory rejeita caracteres de controlo proibidos pelo
     * modelo.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function rejeita_musica_com_caracteres_de_controlo(): void
    {
        $this->expectException(
            InvalidArgumentException::class,
        );

        MusicaFavoritaEdicao::factory()
            ->comMusica(
                "Música\0inválida",
            );
    }
}
