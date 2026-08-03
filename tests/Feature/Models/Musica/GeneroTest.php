<?php

declare(strict_types=1);

namespace Tests\Feature\Models\Musica;

use App\Models\Musica\Genero;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use LogicException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa o modelo dos géneros musicais com persistência real.
 *
 * Os testes validam a travessia recursiva da hierarquia e garantem que a
 * obtenção dos descendentes não volta a introduzir uma consulta por género.
 *
 * @since 2.0.0
 *
 * @version 1.1.0
 */
final class GeneroTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Confirma que o género e todos os descendentes são obtidos numa consulta.
     *
     * Um descendente alcançável por vários caminhos deve surgir apenas uma
     * vez no resultado.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function obtem_genero_e_descendentes_numa_unica_consulta(): void
    {
        $raiz = $this->criarGenero(
            'Metal',
        );

        $filhoUm = $this->criarGenero(
            'Heavy Metal',
        );

        $filhoDois = $this->criarGenero(
            'Doom Metal',
        );

        $descendentePartilhado = $this->criarGenero(
            'Epic Doom Metal',
        );

        $raiz
            ->generosFilhos()
            ->attach([
                $filhoUm->getKey(),
                $filhoDois->getKey(),
            ]);

        $filhoUm
            ->generosFilhos()
            ->attach(
                $descendentePartilhado->getKey(),
            );

        $filhoDois
            ->generosFilhos()
            ->attach(
                $descendentePartilhado->getKey(),
            );

        DB::flushQueryLog();
        DB::enableQueryLog();

        try {
            $identificadores =
                $raiz->obterIdentificadoresComDescendentes();

            $consultas = DB::getQueryLog();
        } finally {
            DB::disableQueryLog();
        }

        $identificadoresEsperados = [
            (int) $raiz->getKey(),
            (int) $filhoUm->getKey(),
            (int) $filhoDois->getKey(),
            (int) $descendentePartilhado->getKey(),
        ];

        sort(
            $identificadoresEsperados,
        );

        self::assertSame(
            $identificadoresEsperados,
            $identificadores,
        );

        self::assertCount(
            1,
            $consultas,
        );
    }

    /**
     * Confirma que ciclos inválidos não provocam uma travessia infinita.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function termina_a_travessia_perante_um_ciclo_invalido(): void
    {
        $primeiro = $this->criarGenero(
            'Black Metal',
        );

        $segundo = $this->criarGenero(
            'Atmospheric Black Metal',
        );

        $primeiro
            ->generosFilhos()
            ->attach(
                $segundo->getKey(),
            );

        $segundo
            ->generosFilhos()
            ->attach(
                $primeiro->getKey(),
            );

        self::assertSame(
            [
                (int) $primeiro->getKey(),
                (int) $segundo->getKey(),
            ],
            $primeiro->obterIdentificadoresComDescendentes(),
        );
    }

    /**
     * Confirma que géneros eliminados logicamente interrompem a travessia.
     *
     * Os descendentes existentes apenas através de um género eliminado não
     * pertencem à hierarquia ativa.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function ignora_ramos_iniciados_por_generos_eliminados(): void
    {
        $raiz = $this->criarGenero(
            'Death Metal',
        );

        $filhoEliminado = $this->criarGenero(
            'Melodic Death Metal',
        );

        $netoAtivo = $this->criarGenero(
            'Gothenburg Metal',
        );

        $raiz
            ->generosFilhos()
            ->attach(
                $filhoEliminado->getKey(),
            );

        $filhoEliminado
            ->generosFilhos()
            ->attach(
                $netoAtivo->getKey(),
            );

        $filhoEliminado->deleteOrFail();

        self::assertSame(
            [
                (int) $raiz->getKey(),
            ],
            $raiz->obterIdentificadoresComDescendentes(),
        );
    }

    /**
     * Confirma que o nome de um género eliminado pode ser reutilizado.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function permite_reutilizar_nome_de_genero_eliminado(): void
    {
        $generoEliminado = $this->criarGenero(
            'Progressive Metal',
        );

        $generoEliminado->deleteOrFail();

        $generoAtivo = $this->criarGenero(
            'Progressive Metal',
        );

        self::assertNotSame(
            $generoEliminado->getKey(),
            $generoAtivo->getKey(),
        );

        $this->assertSoftDeleted(
            'generos',
            [
                'id' => $generoEliminado->getKey(),
            ],
        );

        $this->assertDatabaseHas(
            'generos',
            [
                'id' => $generoAtivo->getKey(),

                'nome' => 'Progressive Metal',

                'deleted_at' => null,
            ],
        );
    }

    /**
     * Confirma que dois géneros ativos não podem partilhar o mesmo nome.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function impede_nomes_repetidos_entre_generos_ativos(): void
    {
        $this->criarGenero(
            'Power Metal',
        );

        $this->expectException(
            QueryException::class,
        );

        $this->criarGenero(
            'Power Metal',
        );
    }

    /**
     * Confirma que um género não persistido não pode iniciar a travessia.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function rejeita_um_genero_nao_persistido(): void
    {
        $this->expectException(
            LogicException::class,
        );

        (new Genero)
            ->obterIdentificadoresComDescendentes();
    }

    /**
     * Cria um género persistido com o nome indicado.
     *
     * @param  string  $nome  Nome do género.
     * @return Genero Género criado.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function criarGenero(
        string $nome,
    ): Genero {
        return Genero::factory()
            ->comNome(
                $nome,
            )
            ->create();
    }
}
