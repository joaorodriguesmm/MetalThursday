<?php

declare(strict_types=1);

namespace App\Servicos\MetalThursday;

/**
 * Normaliza a configuração dos filtros dinâmicos da listagem MetalThursday.
 *
 * @since 2.0.0
 */
final class ServicoConfiguracaoFiltrosMetalThursday
{
    /** @var list<string> Tipos de filtros reconhecidos pela interface. */
    private const TIPOS_FILTROS = [
        'selecao',
        'data',
        'sim_nao',
    ];

    /** @var list<string> Coleções que podem alimentar filtros de seleção. */
    private const CHAVES_DADOS_FILTROS = [
        'edicoes',
        'utilizadores',
        'artistas',
        'generos',
    ];

    /**
     * Obtém os grupos de filtros válidos para apresentação.
     *
     * @return array<int, array{
     *     rotulo: string,
     *     filtros: array<int, array{
     *         chave: string,
     *         rotulo: string,
     *         parametro: string,
     *         tipo: 'selecao'|'data'|'sim_nao',
     *         chaveDados: string|null
     *     }>
     * }> Grupos normalizados.
     *
     * @since 2.0.0
     */
    public function obterGruposDisponiveis(): array
    {
        $configuracao = config(
            'filtros.metal_thursday',
            [],
        );

        if (! is_array($configuracao)) {
            return [];
        }

        $gruposNormalizados = [];
        $chavesUtilizadas = [];

        foreach ($configuracao as $grupo) {
            if (! is_array($grupo)) {
                continue;
            }

            $rotuloGrupo = is_string(
                $grupo['rotulo'] ?? null,
            )
                ? trim($grupo['rotulo'])
                : '';

            $filtrosConfigurados =
                $grupo['filtros']
                ?? [];

            if (! is_array($filtrosConfigurados)) {
                continue;
            }

            $filtrosNormalizados = [];

            foreach ($filtrosConfigurados as $filtro) {
                if (! is_array($filtro)) {
                    continue;
                }

                $chave = is_string(
                    $filtro['chave'] ?? null,
                )
                    ? trim($filtro['chave'])
                    : '';

                $rotulo = is_string(
                    $filtro['rotulo'] ?? null,
                )
                    ? trim($filtro['rotulo'])
                    : '';

                $parametro = is_string(
                    $filtro['parametro'] ?? null,
                )
                    ? trim($filtro['parametro'])
                    : '';

                $tipo = is_string(
                    $filtro['tipo'] ?? null,
                )
                    ? trim($filtro['tipo'])
                    : '';

                $chaveDadosRecebida =
                    $filtro['chaveDados']
                    ?? null;

                $chaveDados =
                    is_string($chaveDadosRecebida)
                    ? trim($chaveDadosRecebida)
                    : null;

                if (
                    $chave === ''
                    || $rotulo === ''
                    || $parametro === ''
                    || isset($chavesUtilizadas[$chave])
                    || ! in_array(
                        $tipo,
                        self::TIPOS_FILTROS,
                        true,
                    )
                ) {
                    continue;
                }

                if ($tipo === 'selecao') {
                    if (
                        $chaveDados === null
                        || ! in_array(
                            $chaveDados,
                            self::CHAVES_DADOS_FILTROS,
                            true,
                        )
                    ) {
                        continue;
                    }
                } else {
                    $chaveDados = null;
                }

                $chavesUtilizadas[$chave] = true;

                $filtrosNormalizados[] = [
                    'chave' => $chave,
                    'rotulo' => $rotulo,
                    'parametro' => $parametro,
                    'tipo' => $tipo,
                    'chaveDados' => $chaveDados,
                ];
            }

            if ($filtrosNormalizados === []) {
                continue;
            }

            $gruposNormalizados[] = [
                'rotulo' => $rotuloGrupo !== ''
                    ? $rotuloGrupo
                    : 'Filtros',
                'filtros' => $filtrosNormalizados,
            ];
        }

        return $gruposNormalizados;
    }
}
