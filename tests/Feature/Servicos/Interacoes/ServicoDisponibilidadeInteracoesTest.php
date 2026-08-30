<?php

declare(strict_types=1);

namespace Tests\Feature\Servicos\Interacoes;

use App\Models\Autenticacao\Utilizador;
use App\Models\Interacoes\Comentario;
use App\Models\MetalThursday\Edicao;
use App\Models\MetalThursday\MetalThursday;
use App\Models\MetalThursday\SeccaoMetalThursday;
use App\Models\MetalThursday\TipoSeccao;
use App\Servicos\Interacoes\ServicoDisponibilidadeInteracoes;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

/**
 * Testa a disponibilidade temporal comum das interações.
 *
 * @since 2.0.0
 */
final class ServicoDisponibilidadeInteracoesTest extends TestCase
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
     * Confirma que uma MetalThursday do próprio dia já aceita interações.
     *
     * @since 2.0.0
     */
    #[Test]
    public function aceita_metal_thursday_publicada(): void
    {
        $metalThursday =
            $this->criarMetalThursday(
                '2026-08-27',
            );

        $resultado =
            $this->obterServico()
                ->obterMetalThursdayPublicada(
                    $metalThursday,
                );

        self::assertTrue(
            $resultado->is(
                $metalThursday,
            ),
        );
    }

    /**
     * Confirma que uma MetalThursday publicada pode ser validada e bloqueada
     * dentro de uma transação.
     *
     * @since 2.0.0
     */
    #[Test]
    public function aceita_metal_thursday_publicada_com_bloqueio(): void
    {
        $metalThursday =
            $this->criarMetalThursday(
                '2026-08-27',
            );

        $resultado =
            DB::transaction(
                fn (): MetalThursday => $this->obterServico()
                    ->obterMetalThursdayPublicadaComBloqueio(
                        $metalThursday,
                    ),
            );

        self::assertTrue(
            $resultado->is(
                $metalThursday,
            ),
        );
    }

    /**
     * Confirma que uma MetalThursday futura ainda não aceita interações.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_metal_thursday_preparada(): void
    {
        $metalThursday =
            $this->criarMetalThursday(
                '2026-09-03',
            );

        $this->expectException(
            NotFoundHttpException::class,
        );

        $this->obterServico()
            ->obterMetalThursdayPublicada(
                $metalThursday,
            );
    }

    /**
     * Confirma que uma secção herda a disponibilidade temporal da respetiva
     * MetalThursday.
     *
     * @since 2.0.0
     */
    #[Test]
    public function resolve_metal_thursday_publicada_a_partir_da_seccao(): void
    {
        $metalThursday =
            $this->criarMetalThursday(
                '2026-08-20',
            );

        $seccao =
            $this->criarSeccao(
                $metalThursday,
            );

        $resultado =
            $this->obterServico()
                ->obterMetalThursdayPublicada(
                    $seccao,
                );

        self::assertTrue(
            $resultado->is(
                $metalThursday,
            ),
        );
    }

    /**
     * Confirma que uma secção de uma MetalThursday futura não aceita
     * interações.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_seccao_de_metal_thursday_preparada(): void
    {
        $metalThursday =
            $this->criarMetalThursday(
                '2026-09-03',
            );

        $seccao =
            $this->criarSeccao(
                $metalThursday,
            );

        $this->expectException(
            NotFoundHttpException::class,
        );

        $this->obterServico()
            ->obterMetalThursdayPublicada(
                $seccao,
            );
    }

    /**
     * Confirma que um comentário é associado à mesma MetalThursday raiz da
     * entidade comentada.
     *
     * @since 2.0.0
     */
    #[Test]
    public function resolve_metal_thursday_publicada_a_partir_do_comentario(): void
    {
        $metalThursday =
            $this->criarMetalThursday(
                '2026-08-20',
            );

        $seccao =
            $this->criarSeccao(
                $metalThursday,
            );

        $comentario =
            $this->criarComentario(
                $seccao,
            );

        $resultado =
            $this->obterServico()
                ->obterMetalThursdayPublicada(
                    $comentario,
                );

        self::assertTrue(
            $resultado->is(
                $metalThursday,
            ),
        );
    }

    /**
     * Confirma que um comentário ligado a conteúdo futuro permanece
     * indisponível.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_comentario_de_metal_thursday_preparada(): void
    {
        $metalThursday =
            $this->criarMetalThursday(
                '2026-09-03',
            );

        $comentario =
            $this->criarComentario(
                $metalThursday,
            );

        $this->expectException(
            NotFoundHttpException::class,
        );

        $this->obterServico()
            ->obterMetalThursdayPublicada(
                $comentario,
            );
    }

    /**
     * Confirma que uma MetalThursday eliminada logicamente deixa de poder
     * servir de raiz a interações.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_metal_thursday_eliminada(): void
    {
        $metalThursday =
            $this->criarMetalThursday(
                '2026-08-20',
            );

        $metalThursday->deleteOrFail();

        $this->expectException(
            NotFoundHttpException::class,
        );

        $this->obterServico()
            ->obterMetalThursdayPublicada(
                $metalThursday,
            );
    }

    /**
     * Obtém o serviço em teste.
     *
     * @return ServicoDisponibilidadeInteracoes Serviço testado.
     *
     * @since 2.0.0
     */
    private function obterServico(): ServicoDisponibilidadeInteracoes
    {
        return app(
            ServicoDisponibilidadeInteracoes::class,
        );
    }

    /**
     * Cria uma MetalThursday numa data determinada.
     *
     * @param  string  $data  Data pretendida.
     * @return MetalThursday MetalThursday criada.
     *
     * @since 2.0.0
     */
    private function criarMetalThursday(
        string $data,
    ): MetalThursday {
        $autor =
            Utilizador::factory()
                ->create();

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
     * Cria uma secção associada à MetalThursday indicada.
     *
     * @param  MetalThursday  $metalThursday  MetalThursday associada.
     * @return SeccaoMetalThursday Secção criada.
     *
     * @since 2.0.0
     */
    private function criarSeccao(
        MetalThursday $metalThursday,
    ): SeccaoMetalThursday {
        $tipoSeccao =
            TipoSeccao::factory()
                ->semDetalhes()
                ->create();

        return SeccaoMetalThursday::factory()
            ->paraMetalThursday(
                $metalThursday,
            )
            ->doTipo(
                $tipoSeccao,
            )
            ->create();
    }

    /**
     * Cria um comentário associado à entidade indicada.
     *
     * @param  MetalThursday|SeccaoMetalThursday  $comentavel  Entidade
     *                                                         comentada.
     * @return Comentario Comentário criado.
     *
     * @since 2.0.0
     */
    private function criarComentario(
        MetalThursday|SeccaoMetalThursday $comentavel,
    ): Comentario {
        $utilizador =
            Utilizador::factory()
                ->create();

        return $comentavel
            ->comentarios()
            ->create([
                'utilizador_id' => $utilizador->getKey(),

                'conteudo' => 'Comentário de teste.',

                'comentario_pai_id' => null,
            ]);
    }
}
