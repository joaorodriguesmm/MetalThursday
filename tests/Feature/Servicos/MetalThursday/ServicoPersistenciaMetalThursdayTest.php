<?php

declare(strict_types=1);

namespace Tests\Feature\Servicos\MetalThursday;

use App\Enumeracoes\TipoIncorporacao;
use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\Edicao;
use App\Models\MetalThursday\TipoSeccao;
use App\Models\Musica\Banda;
use App\Servicos\MetalThursday\ServicoPersistenciaMetalThursday;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa a persistência transacional das MetalThursdays e das respetivas
 * secções.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
final class ServicoPersistenciaMetalThursdayTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Confirma que o serviço traduz o campo público do tipo para a coluna
     * física correta da secção.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function cria_metal_thursday_com_secao_detalhada(): void
    {
        $utilizador = Utilizador::factory()
            ->create();

        $this->actingAs(
            $utilizador,
            'sessao',
        );

        $edicao =
            $this->criarEdicao();

        $tipoSeccao = TipoSeccao::factory()
            ->comDetalhes()
            ->create();

        $banda = Banda::factory()
            ->create();

        $metalThursday = $this
            ->servico()
            ->criar([
                'edicao_id' => (int) $edicao->getKey(),

                'data' => '2026-01-08',

                'nome' => 'Especial de janeiro',

                'autor_id' => (int) $utilizador->getKey(),

                'proximo_nomeado_id' => (int) $utilizador->getKey(),

                'seccoes' => [
                    [
                        'id' => null,

                        'tipo_secao_id' => (int) $tipoSeccao->getKey(),

                        'titulo' => 'Faixa da semana',

                        'descricao' => 'Descrição conhecida da secção.',

                        'banda_id' => (int) $banda->getKey(),

                        'ligacao' => 'https://example.com/faixa',

                        'tipo_incorporacao' => TipoIncorporacao::Ligacao->value,

                        'ano' => 2026,
                    ],
                ],
            ]);

        self::assertSame(
            'Especial de janeiro',
            $metalThursday->nome,
        );

        $this->assertDatabaseHas(
            'seccoes_metal_thursday',
            [
                'metal_thursday_id' => $metalThursday->getKey(),

                'tipo_seccao_id' => $tipoSeccao->getKey(),

                'banda_id' => $banda->getKey(),

                'ordem' => 1,

                'titulo' => 'Faixa da semana',

                'tipo_incorporacao' => TipoIncorporacao::Ligacao->value,

                'ano' => 2026,

                'deleted_at' => null,
            ],
        );
    }

    /**
     * Confirma que a atualização permite trocar a ordem de secções sem violar
     * a restrição única das posições ativas.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function atualiza_e_reordena_seccoes_existentes(): void
    {
        $utilizador = Utilizador::factory()
            ->create();

        $this->actingAs(
            $utilizador,
            'sessao',
        );

        $edicao =
            $this->criarEdicao();

        $tipoSeccao = TipoSeccao::factory()
            ->semDetalhes()
            ->create();

        $servico =
            $this->servico();

        $metalThursday = $servico->criar([
            'edicao_id' => (int) $edicao->getKey(),

            'data' => '2026-01-08',

            'nome' => null,

            'autor_id' => (int) $utilizador->getKey(),

            'proximo_nomeado_id' => null,

            'seccoes' => [
                $this->dadosSeccaoSimples(
                    $tipoSeccao,
                    'Primeira descrição.',
                ),

                $this->dadosSeccaoSimples(
                    $tipoSeccao,
                    'Segunda descrição.',
                ),
            ],
        ]);

        $seccoes = $metalThursday
            ->seccoes
            ->values();

        $primeira =
            $seccoes->get(0);

        $segunda =
            $seccoes->get(1);

        self::assertNotNull(
            $primeira,
        );

        self::assertNotNull(
            $segunda,
        );

        $servico->atualizar(
            $metalThursday,
            [
                'edicao_id' => (int) $edicao->getKey(),

                'data' => '2026-01-08',

                'nome' => null,

                'autor_id' => (int) $utilizador->getKey(),

                'proximo_nomeado_id' => null,

                'seccoes' => [
                    [
                        ...$this->dadosSeccaoSimples(
                            $tipoSeccao,
                            'Segunda descrição atualizada.',
                        ),

                        'id' => (int) $segunda->getKey(),
                    ],
                    [
                        ...$this->dadosSeccaoSimples(
                            $tipoSeccao,
                            'Primeira descrição atualizada.',
                        ),

                        'id' => (int) $primeira->getKey(),
                    ],
                ],
            ],
        );

        $this->assertDatabaseHas(
            'seccoes_metal_thursday',
            [
                'id' => $segunda->getKey(),

                'ordem' => 1,

                'descricao' => 'Segunda descrição atualizada.',

                'deleted_at' => null,
            ],
        );

        $this->assertDatabaseHas(
            'seccoes_metal_thursday',
            [
                'id' => $primeira->getKey(),

                'ordem' => 2,

                'descricao' => 'Primeira descrição atualizada.',

                'deleted_at' => null,
            ],
        );
    }

    /**
     * Confirma que o serviço protege o período da edição sem depender do
     * Form Request.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function rejeita_data_fora_do_periodo_da_edicao(): void
    {
        $edicao =
            $this->criarEdicao();

        $tipoSeccao = TipoSeccao::factory()
            ->semDetalhes()
            ->create();

        try {
            $this
                ->servico()
                ->criar([
                    'edicao_id' => (int) $edicao->getKey(),

                    'data' => '2026-02-01',

                    'nome' => null,

                    'autor_id' => null,

                    'proximo_nomeado_id' => null,

                    'seccoes' => [
                        $this->dadosSeccaoSimples(
                            $tipoSeccao,
                            'Descrição válida.',
                        ),
                    ],
                ]);

            self::fail(
                'Era esperada uma exceção para uma data fora da edição.',
            );
        } catch (InvalidArgumentException $excecao) {
            self::assertSame(
                'A data da MetalThursday não pode ser posterior ao fim da edição.',
                $excecao->getMessage(),
            );
        }

        $this->assertDatabaseCount(
            'metal_thursdays',
            0,
        );
    }

    /**
     * Confirma que uma secção detalhada exige todos os respetivos campos.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function rejeita_secao_detalhada_incompleta(): void
    {
        $edicao =
            $this->criarEdicao();

        $tipoSeccao = TipoSeccao::factory()
            ->comDetalhes()
            ->create();

        try {
            $this
                ->servico()
                ->criar([
                    'edicao_id' => (int) $edicao->getKey(),

                    'data' => '2026-01-08',

                    'nome' => null,

                    'autor_id' => null,

                    'proximo_nomeado_id' => null,

                    'seccoes' => [
                        $this->dadosSeccaoSimples(
                            $tipoSeccao,
                            'Descrição válida.',
                        ),
                    ],
                ]);

            self::fail(
                'Era esperada uma exceção para uma secção incompleta.',
            );
        } catch (InvalidArgumentException $excecao) {
            self::assertSame(
                'O título é obrigatório numa secção detalhada.',
                $excecao->getMessage(),
            );
        }

        $this->assertDatabaseCount(
            'metal_thursdays',
            0,
        );
    }

    /**
     * Confirma que os tipos simples não aceitam detalhes musicais.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function rejeita_detalhes_num_tipo_simples(): void
    {
        $edicao =
            $this->criarEdicao();

        $tipoSeccao = TipoSeccao::factory()
            ->semDetalhes()
            ->create();

        try {
            $this
                ->servico()
                ->criar([
                    'edicao_id' => (int) $edicao->getKey(),

                    'data' => '2026-01-08',

                    'nome' => null,

                    'autor_id' => null,

                    'proximo_nomeado_id' => null,

                    'seccoes' => [
                        [
                            ...$this->dadosSeccaoSimples(
                                $tipoSeccao,
                                'Descrição válida.',
                            ),

                            'titulo' => 'Título incompatível',
                        ],
                    ],
                ]);

            self::fail(
                'Era esperada uma exceção para detalhes incompatíveis.',
            );
        } catch (InvalidArgumentException $excecao) {
            self::assertSame(
                'Uma secção sem detalhes não pode conter informação musical detalhada.',
                $excecao->getMessage(),
            );
        }

        $this->assertDatabaseCount(
            'metal_thursdays',
            0,
        );
    }

    /**
     * Cria a edição utilizada nos testes.
     *
     * @return Edicao Edição criada.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function criarEdicao(): Edicao
    {
        return Edicao::factory()
            ->comNome(
                'Edição de janeiro',
            )
            ->comPeriodo(
                CarbonImmutable::parse(
                    '2026-01-01',
                ),
                CarbonImmutable::parse(
                    '2026-01-31',
                ),
            )
            ->create();
    }

    /**
     * Obtém os dados de uma secção sem detalhes.
     *
     * @param  TipoSeccao  $tipoSeccao  Tipo relacionado.
     * @param  string  $descricao  Descrição da secção.
     * @return array<string, mixed> Dados da secção.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function dadosSeccaoSimples(
        TipoSeccao $tipoSeccao,
        string $descricao,
    ): array {
        return [
            'id' => null,

            'tipo_secao_id' => (int) $tipoSeccao->getKey(),

            'titulo' => null,

            'descricao' => $descricao,

            'banda_id' => null,

            'ligacao' => null,

            'tipo_incorporacao' => null,

            'ano' => null,
        ];
    }

    /**
     * Cria o serviço testado.
     *
     * @return ServicoPersistenciaMetalThursday Serviço criado.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function servico(): ServicoPersistenciaMetalThursday
    {
        return new ServicoPersistenciaMetalThursday;
    }
}
