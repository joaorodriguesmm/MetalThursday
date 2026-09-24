<?php

declare(strict_types=1);

namespace Tests\Feature\Servicos\MetalThursday;

use App\Servicos\MetalThursday\ServicoConfiguracaoFiltrosMetalThursday;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa a normalização da configuração dos filtros MetalThursday.
 *
 * @since 2.0.0
 */
final class ServicoConfiguracaoFiltrosMetalThursdayTest extends TestCase
{
    /**
     * Confirma que apenas grupos e filtros válidos são disponibilizados.
     *
     * @since 2.0.0
     */
    #[Test]
    public function normaliza_configuracao_e_ignora_entradas_invalidas(): void
    {
        config([
            'filtros.metal_thursday' => [
                [
                    'rotulo' => '  Grupo válido  ',
                    'filtros' => [
                        [
                            'chave' => ' autor ',
                            'rotulo' => ' Autor ',
                            'parametro' => ' autor ',
                            'tipo' => 'selecao',
                            'chaveDados' => 'utilizadores',
                        ],
                        [
                            'chave' => 'data',
                            'rotulo' => 'Data',
                            'parametro' => 'data',
                            'tipo' => 'data',
                            'chaveDados' => 'ignorado',
                        ],
                        [
                            'chave' => 'autor',
                            'rotulo' => 'Duplicado',
                            'parametro' => 'duplicado',
                            'tipo' => 'data',
                            'chaveDados' => null,
                        ],
                        [
                            'chave' => 'selecao_invalida',
                            'rotulo' => 'Seleção inválida',
                            'parametro' => 'selecao_invalida',
                            'tipo' => 'selecao',
                            'chaveDados' => 'desconhecida',
                        ],
                        [
                            'chave' => 'tipo_invalido',
                            'rotulo' => 'Tipo inválido',
                            'parametro' => 'tipo_invalido',
                            'tipo' => 'texto',
                            'chaveDados' => null,
                        ],
                    ],
                ],
                [
                    'rotulo' => '',
                    'filtros' => [
                        [
                            'chave' => 'audicao',
                            'rotulo' => 'Que ouvi',
                            'parametro' => 'audicao',
                            'tipo' => 'sim_nao',
                            'chaveDados' => null,
                        ],
                    ],
                ],
                [
                    'rotulo' => 'Sem filtros válidos',
                    'filtros' => [
                        'invalido',
                    ],
                ],
                'grupo_invalido',
            ],
        ]);

        self::assertSame(
            [
                [
                    'rotulo' => 'Grupo válido',
                    'filtros' => [
                        [
                            'chave' => 'autor',
                            'rotulo' => 'Autor',
                            'parametro' => 'autor',
                            'tipo' => 'selecao',
                            'chaveDados' => 'utilizadores',
                        ],
                        [
                            'chave' => 'data',
                            'rotulo' => 'Data',
                            'parametro' => 'data',
                            'tipo' => 'data',
                            'chaveDados' => null,
                        ],
                    ],
                ],
                [
                    'rotulo' => 'Filtros',
                    'filtros' => [
                        [
                            'chave' => 'audicao',
                            'rotulo' => 'Que ouvi',
                            'parametro' => 'audicao',
                            'tipo' => 'sim_nao',
                            'chaveDados' => null,
                        ],
                    ],
                ],
            ],
            app(
                ServicoConfiguracaoFiltrosMetalThursday::class,
            )->obterGruposDisponiveis(),
        );
    }

    /**
     * Confirma que uma configuração global inválida produz uma lista vazia.
     *
     * @since 2.0.0
     */
    #[Test]
    public function devolve_lista_vazia_perante_configuracao_invalida(): void
    {
        config([
            'filtros.metal_thursday' => 'invalido',
        ]);

        self::assertSame(
            [],
            app(
                ServicoConfiguracaoFiltrosMetalThursday::class,
            )->obterGruposDisponiveis(),
        );
    }
}
