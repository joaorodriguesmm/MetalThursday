<?php

declare(strict_types=1);

namespace Tests\Feature\Models\Musica;

use App\Enumeracoes\TipoLancamento;
use App\Models\Musica\Artista;
use App\Models\Musica\Lancamento;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa os contratos persistidos do modelo dos lançamentos musicais.
 *
 * @since 2.0.0
 */
final class LancamentoTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Confirma que um lançamento pode existir sem tipo conhecido.
     *
     * A ausência de tipo representa informação desconhecida ou não indicada e
     * não deve impedir a persistência do lançamento.
     *
     * @since 2.0.0
     */
    #[Test]
    public function permite_lancamento_sem_tipo(): void
    {
        $lancamento = Lancamento::factory()
            ->create([
                'titulo' => 'Lançamento sem tipo',
                'tipo' => null,
            ]);

        $lancamento->refresh();

        self::assertNull(
            $lancamento->tipo,
        );
    }

    /**
     * Confirma que o tipo persistido é convertido para a enumeração de domínio.
     *
     * @since 2.0.0
     */
    #[Test]
    public function converte_tipo_para_enumeracao(): void
    {
        $lancamento = Lancamento::factory()
            ->create([
                'titulo' => 'Master of Puppets',
                'tipo' => TipoLancamento::AlbumEstudio,
            ]);

        $lancamento->refresh();

        self::assertSame(
            TipoLancamento::AlbumEstudio,
            $lancamento->tipo,
        );
    }

    /**
     * Confirma que o ano original é opcional e convertido para inteiro.
     *
     * @since 2.0.0
     */
    #[Test]
    public function permite_ano_original_opcional(): void
    {
        $lancamento = Lancamento::factory()
            ->create([
                'titulo' => 'Load',
                'ano_original' => 1996,
            ]);

        $lancamento->refresh();

        self::assertSame(
            1996,
            $lancamento->ano_original,
        );

        $lancamento->update([
            'ano_original' => null,
        ]);

        self::assertNull(
            $lancamento
                ->fresh()
                ?->ano_original,
        );
    }

    /**
     * Confirma que lançamentos distintos podem possuir o mesmo título.
     *
     * O título não identifica univocamente um lançamento.
     *
     * @since 2.0.0
     */
    #[Test]
    public function permite_lancamentos_com_o_mesmo_titulo(): void
    {
        $primeiroLancamento = Lancamento::factory()
            ->create([
                'titulo' => 'Metal',
            ]);

        $segundoLancamento = Lancamento::factory()
            ->create([
                'titulo' => 'Metal',
            ]);

        self::assertNotSame(
            $primeiroLancamento->getKey(),
            $segundoLancamento->getKey(),
        );

        self::assertSame(
            2,
            Lancamento::query()
                ->where(
                    'titulo',
                    'Metal',
                )
                ->count(),
        );
    }

    /**
     * Confirma que a remoção de um lançamento preserva o registo histórico.
     *
     * @since 2.0.0
     */
    #[Test]
    public function elimina_lancamento_logicamente(): void
    {
        $lancamento = Lancamento::factory()
            ->create([
                'titulo' => 'Lançamento removido',
            ]);

        $lancamento->delete();

        self::assertSoftDeleted(
            'lancamentos',
            [
                'id' => $lancamento->getKey(),
            ],
        );
    }

    /**
     * Confirma que um artista pode estar associado a vários lançamentos.
     *
     * @since 2.0.0
     */
    #[Test]
    public function permite_artista_com_varios_lancamentos(): void
    {
        $artista = Artista::factory()
            ->create();

        $primeiroLancamento = Lancamento::factory()
            ->create([
                'titulo' => 'Primeiro lançamento',
            ]);

        $segundoLancamento = Lancamento::factory()
            ->create([
                'titulo' => 'Segundo lançamento',
            ]);

        $artista
            ->lancamentos()
            ->attach([
                $primeiroLancamento->getKey(),
                $segundoLancamento->getKey(),
            ]);

        $lancamentos = $artista
            ->lancamentos()
            ->get();

        self::assertCount(
            2,
            $lancamentos,
        );

        self::assertTrue(
            $lancamentos->contains(
                'id',
                $primeiroLancamento->getKey(),
            ),
        );

        self::assertTrue(
            $lancamentos->contains(
                'id',
                $segundoLancamento->getKey(),
            ),
        );
    }

    /**
     * Confirma que um lançamento pode estar associado a vários artistas.
     *
     * @since 2.0.0
     */
    #[Test]
    public function permite_lancamento_com_varios_artistas(): void
    {
        $lancamento = Lancamento::factory()
            ->create([
                'titulo' => 'Lançamento colaborativo',
            ]);

        $primeiroArtista = Artista::factory()
            ->create();

        $segundoArtista = Artista::factory()
            ->create();

        $lancamento
            ->artistas()
            ->attach([
                $primeiroArtista->getKey(),
                $segundoArtista->getKey(),
            ]);

        $artistas = $lancamento
            ->artistas()
            ->get();

        self::assertCount(
            2,
            $artistas,
        );

        self::assertTrue(
            $artistas->contains(
                'id',
                $primeiroArtista->getKey(),
            ),
        );

        self::assertTrue(
            $artistas->contains(
                'id',
                $segundoArtista->getKey(),
            ),
        );
    }

    /**
     * Confirma que um lançamento pode identificar uma edição concreta do Discogs.
     *
     * @since 2.0.0
     */
    #[Test]
    public function associa_edicao_concreta_do_discogs(): void
    {
        $lancamento = Lancamento::factory()
            ->create([
                'discogs_release_id' => 123456,
            ]);

        self::assertSame(
            123456,
            $lancamento->discogs_release_id,
        );

        self::assertSame(
            'https://www.discogs.com/release/123456',
            $lancamento->url_discogs,
        );
    }

    /**
     * Confirma que a mesma edição Discogs não pode identificar dois lançamentos.
     *
     * @since 2.0.0
     */
    #[Test]
    public function nao_permite_repetir_edicao_do_discogs(): void
    {
        Lancamento::factory()
            ->create([
                'discogs_release_id' => 123456,
            ]);

        $this->expectException(
            QueryException::class,
        );

        Lancamento::factory()
            ->create([
                'discogs_release_id' => 123456,
            ]);
    }
}
