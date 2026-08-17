{{--
    Apresenta os detalhes de um género musical e as bandas associadas.

    Os nomes dos géneros relacionados e os dados de apresentação das bandas
    são preparados pelo App\Http\Controllers\Musica\ControladorGenero.

    @since 1.0.0
--}}

<x-layout-aplicacao>
    <x-slot name="titulo">
        {{ $genero->nome }}
    </x-slot>

    <x-slot name="cabecalho">
        <div>
            <h1 class="h4 mb-1 fw-bold">
                {{ $genero->nome }}
            </h1>

            @if ($nomesGenerosPais !== null)
                <p class="mb-1 text-muted">
                    Subgénero de: {{ $nomesGenerosPais }}
                </p>
            @endif

            @if ($nomesGenerosFilhos !== null)
                <p class="mb-0 text-muted">
                    Géneros derivados: {{ $nomesGenerosFilhos }}
                </p>
            @endif
        </div>
    </x-slot>

    <x-estado-sessao class="mb-4" />

    <div
        class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-3"
    >
        <h2 class="h5 mb-0">
            Bandas associadas
            ({{ $bandas->total() }})
        </h2>

        <a
            class="btn btn-sm btn-secondary"
            href="{{ route('generos.indice') }}"
        >
            Voltar aos géneros
        </a>
    </div>

    <div class="card shadow-sm bg-dark">
        <div class="card-body">
            <div class="table-responsive">
                <table
                    class="table table-dark table-striped table-hover align-middle mb-0"
                >
                    <caption class="visually-hidden">
                        Bandas associadas ao género {{ $genero->nome }}
                    </caption>

                    <thead>
                        <tr>
                            <th scope="col">
                                Banda
                            </th>

                            <th scope="col">
                                Origem geográfica
                            </th>

                            <th scope="col">
                                Outros géneros
                            </th>

                            <th
                                class="text-end"
                                scope="col"
                            >
                                Ações
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($bandas as $bandaApresentacao)
                            <tr
                                id="banda-{{ $bandaApresentacao['identificador'] }}"
                            >
                                <th scope="row">
                                    {{ $bandaApresentacao['nome'] }}
                                </th>

                                <td>
                                    {{
                                        $bandaApresentacao[
                                            'nomeOrigemGeografica'
                                        ]
                                    }}
                                </td>

                                <td>
                                    @if (
                                        $bandaApresentacao['nomesOutrosGeneros']
                                        !== null
                                    )
                                        {{
                                            $bandaApresentacao[
                                                'nomesOutrosGeneros'
                                            ]
                                        }}
                                    @else
                                        <span class="text-white-50">
                                            —
                                        </span>
                                    @endif
                                </td>

                                <td class="text-end text-nowrap">
                                    @can(
                                        'view',
                                        $bandaApresentacao['modelo']
                                    )
                                        <a
                                            class="btn btn-sm btn-info"
                                            href="{{
                                                route(
                                                    'bandas.detalhes',
                                                    $bandaApresentacao['modelo'],
                                                )
                                            }}"
                                            aria-label="Ver detalhes de {{ $bandaApresentacao['nome'] }}"
                                            title="Ver detalhes da banda"
                                        >
                                            <i
                                                class="bi bi-eye"
                                                aria-hidden="true"
                                            ></i>
                                        </a>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td
                                    class="py-4 text-center"
                                    colspan="4"
                                >
                                    Nenhuma banda está associada a este género.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($bandas->hasPages())
            <div class="card-footer bg-dark border-secondary">
                <nav aria-label="Paginação das bandas associadas">
                    {{
                        $bandas->links(
                            'vendor.pagination.bootstrap-5',
                        )
                    }}
                </nav>
            </div>
        @endif
    </div>
</x-layout-aplicacao>
