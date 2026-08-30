<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\MetalThursday;

use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\Edicao;
use App\Models\MetalThursday\MetalThursday;
use App\Models\MetalThursday\SeccaoMetalThursday;
use App\Models\MetalThursday\TipoSeccao;
use Carbon\CarbonImmutable;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa a visibilidade das interações nos detalhes de uma MetalThursday.
 *
 * @since 2.0.0
 */
final class VisibilidadeInteracoesDetalhesMetalThursdayTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Prepara uma referência temporal determinística.
     *
     * @since 2.0.0
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(
            CarbonImmutable::parse(
                '2026-08-27 12:00:00',
                'Europe/Lisbon',
            ),
        );
    }

    /**
     * Confirma que uma MetalThursday preparada continua visível ao autor sem
     * expor nem consultar qualquer interação social.
     *
     * @since 2.0.0
     */
    #[Test]
    public function autor_ve_metal_thursday_preparada_sem_interacoes(): void
    {
        $autor =
            Utilizador::factory()
                ->create();

        $utilizadorSocial =
            Utilizador::factory()
                ->create([
                    'nome' => 'Utilizador Social Futuro',
                ]);

        $metalThursday =
            $this->criarMetalThursday(
                '2026-09-03',
                $autor,
            );

        $this->criarSeccaoComDetalhes(
            $metalThursday,
        );

        $this->criarInteracoes(
            $metalThursday,
            $utilizadorSocial,
        );

        $consultasSociais = [];

        DB::listen(
            static function (
                QueryExecuted $consulta,
            ) use (
                &$consultasSociais,
            ): void {
                $sql =
                    mb_strtolower(
                        $consulta->sql,
                    );

                foreach (
                    [
                        'comentarios',
                        'avaliacoes',
                        'audicoes',
                    ] as $tabela
                ) {
                    if (
                        str_contains(
                            $sql,
                            $tabela,
                        )
                    ) {
                        $consultasSociais[] = $sql;

                        break;
                    }
                }
            },
        );

        $this
            ->actingAs(
                $autor,
                'sessao',
            )
            ->get(
                route(
                    'metal-thursday.detalhes',
                    $metalThursday,
                ),
            )
            ->assertOk()
            ->assertSee(
                'Conteúdo musical preparado visível.',
            )
            ->assertDontSee(
                'Comentário social confidencial.',
            )
            ->assertDontSee(
                'Utilizador Social Futuro',
            )
            ->assertDontSee(
                'data-contentor-interacoes',
                false,
            )
            ->assertDontSee(
                'data-tipo-avaliavel',
                false,
            )
            ->assertDontSee(
                'data-tipo-interacao="alternar-audicao"',
                false,
            );

        self::assertSame(
            [],
            $consultasSociais,
        );
    }

    /**
     * Confirma que as interações continuam normalmente disponíveis depois da
     * publicação.
     *
     * @since 2.0.0
     */
    #[Test]
    public function metal_thursday_publicada_continua_a_apresentar_interacoes(): void
    {
        $autor =
            Utilizador::factory()
                ->create();

        $utilizadorSocial =
            Utilizador::factory()
                ->create([
                    'nome' => 'Utilizador Social Publicado',
                ]);

        $metalThursday =
            $this->criarMetalThursday(
                '2026-08-27',
                $autor,
            );

        $this->criarSeccaoComDetalhes(
            $metalThursday,
        );

        $this->criarInteracoes(
            $metalThursday,
            $utilizadorSocial,
            'Comentário social publicado.',
        );

        $this
            ->actingAs(
                $autor,
                'sessao',
            )
            ->get(
                route(
                    'metal-thursday.detalhes',
                    $metalThursday,
                ),
            )
            ->assertOk()
            ->assertSee(
                'Conteúdo musical preparado visível.',
            )
            ->assertSee(
                'Comentário social publicado.',
            )
            ->assertSee(
                'Utilizador Social Publicado',
            )
            ->assertSee(
                'data-contentor-interacoes',
                false,
            )
            ->assertSee(
                'data-tipo-avaliavel',
                false,
            )
            ->assertSee(
                'data-tipo-interacao="alternar-audicao"',
                false,
            );
    }

    /**
     * Cria uma MetalThursday na data indicada.
     *
     * @param  string  $data  Data da MetalThursday.
     * @param  Utilizador  $autor  Autor.
     * @return MetalThursday MetalThursday criada.
     *
     * @since 2.0.0
     */
    private function criarMetalThursday(
        string $data,
        Utilizador $autor,
    ): MetalThursday {
        $edicao =
            Edicao::factory()
                ->comPeriodo(
                    CarbonImmutable::parse(
                        '2026-08-01',
                    ),
                    CarbonImmutable::parse(
                        '2026-09-30',
                    ),
                )
                ->create();

        return MetalThursday::factory()
            ->comData(
                CarbonImmutable::parse(
                    $data,
                ),
            )
            ->comEdicao(
                $edicao,
            )
            ->comAutor(
                $autor,
            )
            ->create();
    }

    /**
     * Cria uma secção com conteúdo que deve continuar visível durante a
     * preparação.
     *
     * @param  MetalThursday  $metalThursday  MetalThursday associada.
     *
     * @since 2.0.0
     */
    private function criarSeccaoComDetalhes(
        MetalThursday $metalThursday,
    ): void {
        $tipoSeccao =
            TipoSeccao::factory()
                ->comDetalhes()
                ->create();

        SeccaoMetalThursday::factory()
            ->paraMetalThursday(
                $metalThursday,
            )
            ->doTipo(
                $tipoSeccao,
            )
            ->create([
                'titulo' => 'Secção musical preparada',

                'descricao' => 'Conteúdo musical preparado visível.',
            ]);
    }

    /**
     * Cria dados sociais diretamente para validar que não são expostos antes
     * da publicação.
     *
     * @param  MetalThursday  $metalThursday  MetalThursday associada.
     * @param  Utilizador  $utilizador  Utilizador das interações.
     * @param  string  $conteudoComentario  Conteúdo do comentário.
     *
     * @since 2.0.0
     */
    private function criarInteracoes(
        MetalThursday $metalThursday,
        Utilizador $utilizador,
        string $conteudoComentario = 'Comentário social confidencial.',
    ): void {
        $metalThursday
            ->comentarios()
            ->create([
                'utilizador_id' => $utilizador->getKey(),

                'conteudo' => $conteudoComentario,

                'comentario_pai_id' => null,
            ]);

        $metalThursday
            ->audicoes()
            ->create([
                'utilizador_id' => $utilizador->getKey(),
            ]);

        $metalThursday
            ->avaliacoes()
            ->create([
                'utilizador_id' => $utilizador->getKey(),

                'pontuacao' => 8.5,
            ]);
    }
}
