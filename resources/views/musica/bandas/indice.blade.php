{{--
    Apresenta a listagem paginada de bandas.

    Permite pesquisar bandas pelo nome e aceder às operações autorizadas
    de consulta, edição e eliminação.

    Os dados da pesquisa e as relações das bandas são preparados pelo
    App\Http\Controllers\Musica\ControladorBanda.

    @since 1.0.0
--}}

<x-layout-aplicacao>
    <x-slot name="titulo">
        Bandas
    </x-slot>

    <x-slot name="cabecalho">
        <div
            class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3"
        >
            <h1 class="h4 mb-0 fw-bold">
                Bandas
            </h1>

            @can(
                'create',
                App\Models\Musica\Banda::class
            )
                <a
                    class="btn btn-primary"
                    href="{{ route('bandas.criar') }}"
                >
                    <i
                        class="bi bi-plus-lg"
                        aria-hidden="true"
                    ></i>

                    Adicionar banda
                </a>
            @endcan
        </div>
    </x-slot>

    <x-estado-sessao class="mb-4" />

    <div class="card shadow-sm bg-dark">
        <div class="card-body">
            <form
                class="mb-4"
                method="GET"
                action="{{ route('bandas.indice') }}"
                role="search"
            >
                <div class="input-group">
                    <label
                        class="visually-hidden"
                        for="pesquisa-bandas"
                    >
                        Pesquisar bandas pelo nome
                    </label>

                    <input
                        id="pesquisa-bandas"
                        class="form-control"
                        type="search"
                        name="pesquisa"
                        value="{{ $pesquisaAtual }}"
                        placeholder="Pesquisar por nome"
                        maxlength="100"
                        autocomplete="off"
                    >

                    <button
                        class="btn btn-secondary"
                        type="submit"
                    >
                        <i
                            class="bi bi-search"
                            aria-hidden="true"
                        ></i>

                        Pesquisar
                    </button>

                    @if ($pesquisaAtual !== null)
                        <a
                            class="btn btn-outline-secondary"
                            href="{{ route('bandas.indice') }}"
                        >
                            Limpar
                        </a>
                    @endif
                </div>
            </form>

            <div class="table-responsive">
                <table
                    class="table table-dark table-striped table-hover align-middle mb-0"
                >
                    <caption class="visually-hidden">
                        Lista de bandas musicais
                    </caption>

                    <thead>
                        <tr>
                            <th scope="col">
                                Nome
                            </th>

                            <th scope="col">
                                Origem geográfica
                            </th>

                            <th scope="col">
                                Géneros
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
                        @forelse ($bandas as $banda)
                            <tr id="banda-{{ $banda->getKey() }}">
                                <th scope="row">
                                    {{ $banda->nome }}
                                </th>

                                <td>
                                    {{
                                        $banda->origemGeografica?->nome
                                        ?? 'Origem geográfica indisponível'
                                    }}
                                </td>

                                <td>
                                    @forelse ($banda->generos as $genero)
                                        {{ $genero->nome }}@unless($loop->last), @endunless
                                    @empty
                                        <span class="text-white-50">
                                            Sem géneros
                                        </span>
                                    @endforelse
                                </td>

                                <td class="text-end text-nowrap">
                                    @can('view', $banda)
                                        <a
                                            class="btn btn-sm btn-info"
                                            href="{{
                                                route(
                                                    'bandas.detalhes',
                                                    $banda,
                                                )
                                            }}"
                                            aria-label="Ver detalhes de {{ $banda->nome }}"
                                            title="Ver detalhes"
                                        >
                                            <i
                                                class="bi bi-eye"
                                                aria-hidden="true"
                                            ></i>
                                        </a>
                                    @endcan

                                    @can('update', $banda)
                                        <a
                                            class="btn btn-sm btn-secondary"
                                            href="{{
                                                route(
                                                    'bandas.editar',
                                                    $banda,
                                                )
                                            }}"
                                            aria-label="Editar {{ $banda->nome }}"
                                            title="Editar"
                                        >
                                            <i
                                                class="bi bi-pencil-square"
                                                aria-hidden="true"
                                            ></i>
                                        </a>
                                    @endcan

                                    @can('delete', $banda)
                                        <button
                                            class="btn btn-sm btn-danger"
                                            type="button"
                                            data-tipo-interacao="eliminar"
                                            data-endereco="{{
                                                route(
                                                    'bandas.eliminar',
                                                    $banda,
                                                )
                                            }}"
                                            data-seletor-elemento-removivel="#banda-{{
                                                $banda->getKey()
                                            }}"
                                            data-mensagem-confirmacao="Tens a certeza de que pretendes eliminar esta banda?"
                                            data-mensagem-sucesso="Banda eliminada com sucesso."
                                            data-mensagem-erro="Não foi possível eliminar a banda."
                                            aria-label="Eliminar {{ $banda->nome }}"
                                            title="Eliminar"
                                        >
                                            <i
                                                class="bi bi-trash"
                                                aria-hidden="true"
                                            ></i>
                                        </button>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td
                                    class="py-4 text-center"
                                    colspan="4"
                                >
                                    @if ($pesquisaAtual !== null)
                                        Nenhuma banda corresponde à pesquisa.
                                    @else
                                        Ainda não foram criadas bandas.
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($bandas->hasPages())
            <div class="card-footer bg-dark border-secondary">
                <nav aria-label="Paginação das bandas">
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
