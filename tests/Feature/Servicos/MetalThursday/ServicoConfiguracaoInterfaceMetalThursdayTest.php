<?php

declare(strict_types=1);

namespace Tests\Feature\Servicos\MetalThursday;

use App\Servicos\MetalThursday\ServicoConfiguracaoInterfaceMetalThursday;
use App\Servicos\MetalThursday\ServicoParametrosListagemMetalThursday;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa a configuração de frontend utilizada pelo MetalThursday.
 *
 * @since 2.0.0
 */
final class ServicoConfiguracaoInterfaceMetalThursdayTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function prepara_enderecos_do_formulario(): void
    {
        $configuracao = $this->app
            ->make(
                ServicoConfiguracaoInterfaceMetalThursday::class,
            )
            ->obterConfiguracaoFormulario();

        self::assertSame(
            [
                'enderecos' => [
                    'guardarEdicao' => route(
                        'edicoes.guardar',
                    ),

                    'guardarArtista' => route(
                        'artistas.guardar',
                    ),

                    'guardarGenero' => route(
                        'generos.guardar',
                    ),

                    'pesquisarLancamentos' => route(
                        'lancamentos.importacao.pesquisar',
                    ),

                    'importarLancamento' => route(
                        'lancamentos.importacao.importar',
                        [
                            'identificadorDiscogs' => '__IDENTIFICADOR_DISCOGS__',
                        ],
                    ),

                    'obterUtilizadorHaMaisTempoSemNomeacao' => route(
                        'utilizadores.ha-mais-tempo-sem-nomeacao',
                    ),
                ],
            ],
            $configuracao,
        );
    }

    #[Test]
    public function prepara_configuracao_da_listagem(): void
    {
        $gruposFiltrosDisponiveis = [
            [
                'rotulo' => 'Geral',
                'filtros' => [
                    [
                        'chave' => 'edicao',
                        'rotulo' => 'Edição',
                        'parametro' => 'edicao',
                        'tipo' => 'selecao',
                        'chaveDados' => 'edicoes',
                    ],
                    [
                        'chave' => 'publicado',
                        'rotulo' => 'Publicado',
                        'parametro' => 'publicado',
                        'tipo' => 'sim_nao',
                        'chaveDados' => null,
                    ],
                ],
            ],
        ];

        $configuracao = $this->app
            ->make(
                ServicoConfiguracaoInterfaceMetalThursday::class,
            )
            ->obterConfiguracaoListagemMetalThursday(
                $gruposFiltrosDisponiveis,
            );

        self::assertSame(
            [
                'dadosFiltros' => [
                    'edicoes' => [],
                    'utilizadores' => [],
                    'artistas' => [],
                    'generos' => [],
                ],
                'filtrosDisponiveis' => [
                    'edicao' => [
                        'chave' => 'edicao',
                        'rotulo' => 'Edição',
                        'parametro' => 'edicao',
                        'tipo' => 'selecao',
                        'chaveDados' => 'edicoes',
                    ],
                    'publicado' => [
                        'chave' => 'publicado',
                        'rotulo' => 'Publicado',
                        'parametro' => 'publicado',
                        'tipo' => 'sim_nao',
                        'chaveDados' => null,
                    ],
                ],
                'vistas' => [
                    'completa' => ServicoParametrosListagemMetalThursday::VISTA_COMPLETA,
                    'simplificada' => ServicoParametrosListagemMetalThursday::VISTA_SIMPLIFICADA,
                ],
            ],
            $configuracao,
        );
    }
}
