<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\MetalThursday;

use App\Enumeracoes\PapelUtilizador;
use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\Edicao;
use App\Models\MetalThursday\MetalThursday;
use App\Models\MetalThursday\SeccaoMetalThursday;
use App\Models\Musica\Artista;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa a visibilidade temporal do acesso direto às MetalThursdays.
 *
 * Conteúdo já publicado permanece acessível aos utilizadores autenticados.
 * Antes da data de publicação, o acesso fica limitado às pessoas autorizadas
 * a acompanhar a preparação.
 *
 * @since 2.0.0
 */
final class VisibilidadeDetalhesMetalThursdayTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Prepara cada teste com uma referência temporal determinística.
     *
     * @since 2.0.0
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $this->travelTo(
            CarbonImmutable::parse(
                '2026-08-27 12:00:00',
                'Europe/Lisbon',
            ),
        );
    }

    /**
     * Confirma que uma MetalThursday publicada no próprio dia pode ser
     * consultada por outro utilizador autenticado.
     *
     * @since 2.0.0
     */
    #[Test]
    public function utilizador_autenticado_ve_metal_thursday_publicada(): void
    {
        $autor =
            Utilizador::factory()
                ->create();

        $utilizador =
            Utilizador::factory()
                ->create();

        $metalThursday =
            $this->criarMetalThursday(
                $autor,
                '2026-08-27',
                'MetalThursday Publicada',
            );

        $this->actingAs(
            $utilizador,
            'sessao',
        );

        $this
            ->get(
                route(
                    'metal-thursday.detalhes',
                    $metalThursday,
                ),
            )
            ->assertOk()
            ->assertSee(
                'MetalThursday Publicada',
            );
    }

    /**
     * Confirma que uma MetalThursday publicada continua a apresentar um artista
     * eliminado logicamente.
     *
     * @since 2.0.0
     */
    #[Test]
    public function metal_thursday_publicada_apresenta_artista_eliminado_logicamente(): void
    {
        $autor = Utilizador::factory()
            ->create();

        $utilizador = Utilizador::factory()
            ->create();

        $artista = Artista::factory()
            ->comNome(
                'Artista Histórico',
            )
            ->create();

        $metalThursday = $this->criarMetalThursday(
            $autor,
            '2026-08-20',
            'MetalThursday Histórica',
        );

        SeccaoMetalThursday::factory()
            ->paraMetalThursday(
                $metalThursday,
            )
            ->comDetalhes(
                $artista,
            )
            ->comConteudo(
                'Descrição histórica.',
                'Registo Histórico',
            )
            ->create();

        $artista->deleteOrFail();

        $this->actingAs(
            $utilizador,
            'sessao',
        );

        $this
            ->get(
                route(
                    'metal-thursday.detalhes',
                    $metalThursday,
                ),
            )
            ->assertOk()
            ->assertSee(
                'Artista Histórico',
            )
            ->assertSee(
                'Registo Histórico',
            );
    }

    /**
     * Confirma que o autor pode consultar a sua MetalThursday preparada antes
     * da publicação.
     *
     * @since 2.0.0
     */
    #[Test]
    public function autor_ve_metal_thursday_preparada(): void
    {
        $autor =
            Utilizador::factory()
                ->create();

        $metalThursday =
            $this->criarMetalThursday(
                $autor,
                '2026-09-03',
                'Preparada do Autor',
            );

        $this->actingAs(
            $autor,
            'sessao',
        );

        $this
            ->get(
                route(
                    'metal-thursday.detalhes',
                    $metalThursday,
                ),
            )
            ->assertOk()
            ->assertSee(
                'Preparada do Autor',
            );
    }

    /**
     * Confirma que o utilizador que criou o registo pode consultar uma
     * MetalThursday preparada mesmo quando não é o autor.
     *
     * @since 2.0.0
     */
    #[Test]
    public function criador_ve_metal_thursday_preparada(): void
    {
        $criador =
            Utilizador::factory()
                ->create();

        $autor =
            Utilizador::factory()
                ->create();

        $this->actingAs(
            $criador,
            'sessao',
        );

        $metalThursday =
            $this->criarMetalThursday(
                $autor,
                '2026-09-03',
                'Preparada do Criador',
            );

        self::assertSame(
            (int) $criador->getKey(),
            (int) $metalThursday->criado_por_id,
        );

        $this
            ->get(
                route(
                    'metal-thursday.detalhes',
                    $metalThursday,
                ),
            )
            ->assertOk()
            ->assertSee(
                'Preparada do Criador',
            );
    }

    /**
     * Confirma que um administrador pode acompanhar diretamente uma
     * MetalThursday ainda preparada.
     *
     * @since 2.0.0
     */
    #[Test]
    public function administrador_ve_metal_thursday_preparada(): void
    {
        $autor =
            Utilizador::factory()
                ->create();

        $administrador =
            Utilizador::factory()
                ->comPapel(
                    PapelUtilizador::Administrador,
                )
                ->create();

        $metalThursday =
            $this->criarMetalThursday(
                $autor,
                '2026-09-03',
                'Preparada Administrativa',
            );

        $this->actingAs(
            $administrador,
            'sessao',
        );

        $this
            ->get(
                route(
                    'metal-thursday.detalhes',
                    $metalThursday,
                ),
            )
            ->assertOk()
            ->assertSee(
                'Preparada Administrativa',
            );
    }

    /**
     * Confirma que um utilizador sem relação com uma MetalThursday preparada
     * recebe 404, evitando revelar a existência do conteúdo futuro.
     *
     * @since 2.0.0
     */
    #[Test]
    public function utilizador_sem_permissao_recebe_404_para_preparada(): void
    {
        $autor =
            Utilizador::factory()
                ->create();

        $utilizador =
            Utilizador::factory()
                ->create();

        $metalThursday =
            $this->criarMetalThursday(
                $autor,
                '2026-09-03',
                'Conteúdo Futuro Privado',
            );

        $this->actingAs(
            $utilizador,
            'sessao',
        );

        $this
            ->get(
                route(
                    'metal-thursday.detalhes',
                    $metalThursday,
                ),
            )
            ->assertNotFound();
    }

    /**
     * Cria uma MetalThursday abrangida pela edição temporal dos testes.
     *
     * @param  Utilizador  $autor  Autor associado.
     * @param  string  $data  Data da MetalThursday.
     * @param  string  $nome  Nome identificável.
     * @return MetalThursday MetalThursday criada.
     *
     * @since 2.0.0
     */
    private function criarMetalThursday(
        Utilizador $autor,
        string $data,
        string $nome,
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
            ->comNome(
                $nome,
            )
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
}
