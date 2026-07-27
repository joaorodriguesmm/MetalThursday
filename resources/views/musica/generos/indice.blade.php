{{--
    Apresenta a listagem paginada de géneros musicais.

    Permite pesquisar géneros pelo nome e aceder às operações autorizadas
    de consulta, edição e eliminação.

    Os dados da pesquisa e as relações dos géneros são preparados pelo
    App\Http\Controllers\Musica\ControladorGenero.

    @since 1.0.0
    @version 3.0.0
--}}

<x-layout-aplicacao>
    <x-slot name="titulo">
        Géneros
    </x-slot>

    <x-slot name="cabecalho">
        <div
            class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3"
        >
            <h1 class="h4 mb-0 fw-bold">
                Géneros
            </h1>

            @can(
                'create',
                App\Models\Musica\Genero::class
            )
                <a
                    class="btn btn-primary"
                    href="{{ route('generos.criar') }}"
                >
                    <i
                        class="bi bi-plus-lg"
                        aria-hidden="true"
                    ></i>

                    Adicionar género
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
                action="{{ route('generos.indice') }}"
                role="search"
            >
                <div class="input-group">
                    <label
                        class="visually-hidden"
                        for="pesquisa-generos"
                    >
                        Pesquisar géneros pelo nome
                    </label>

                    <input
                        id="pesquisa-generos"
                        class="form-control bg-dark text-white border-secondary"
                        type="search"
                        name="pesquisa"
                        value="{{ $pesquisaAtual ?? '' }}"
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
                            href="{{ route('generos.indice') }}"
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
                        Lista de géneros musicais
                    </caption>

                    <thead>
                        <tr>
                            <th scope="col">
                                Nome
                            </th>

                            <th scope="col">
                                Géneros pais
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
                        @forelse ($generos as $genero)
                            <tr id="genero-{{ $genero->getKey() }}">
                                <th scope="row">
                                    {{ $genero->nome }}
                                </th>

                                <td>
                                    @forelse (
                                        $genero->generosPais
                                        as $generoPai
                                    )
                                        {{ $generoPai->nome }}@unless($loop->last), @endunless
                                    @empty
                                        <span class="text-white-50">
                                            —
                                        </span>
                                    @endforelse
                                </td>

                                <td class="text-end text-nowrap">
                                    @can('view', $genero)
                                        <a
                                            class="btn btn-sm btn-info"
                                            href="{{
                                                route(
                                                    'generos.detalhes',
                                                    $genero,
                                                )
                                            }}"
                                            aria-label="Ver detalhes de {{ $genero->nome }}"
                                            title="Ver detalhes"
                                        >
                                            <i
                                                class="bi bi-eye"
                                                aria-hidden="true"
                                            ></i>
                                        </a>
                                    @endcan

                                    @can('update', $genero)
                                        <a
                                            class="btn btn-sm btn-secondary"
                                            href="{{
                                                route(
                                                    'generos.editar',
                                                    $genero,
                                                )
                                            }}"
                                            aria-label="Editar {{ $genero->nome }}"
                                            title="Editar"
                                        >
                                            <i
                                                class="bi bi-pencil-square"
                                                aria-hidden="true"
                                            ></i>
                                        </a>
                                    @endcan

                                    @can('delete', $genero)
                                        <form
                                            class="d-inline"
                                            method="POST"
                                            action="{{
                                                route(
                                                    'generos.eliminar',
                                                    $genero,
                                                )
                                            }}"
                                            onsubmit="return confirm(
                                                'Tens a certeza de que pretendes eliminar este género?'
                                            );"
                                        >
                                            @csrf
                                            @method('DELETE')

                                            <button
                                                class="btn btn-sm btn-danger"
                                                type="submit"
                                                aria-label="Eliminar {{ $genero->nome }}"
                                                title="Eliminar"
                                            >
                                                <i
                                                    class="bi bi-trash"
                                                    aria-hidden="true"
                                                ></i>
                                            </button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td
                                    class="py-4 text-center"
                                    colspan="3"
                                >
                                    @if ($pesquisaAtual !== null)
                                        Nenhum género corresponde à pesquisa.
                                    @else
                                        Ainda não foram criados géneros.
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($generos->hasPages())
            <div class="card-footer bg-dark border-secondary">
                <nav aria-label="Paginação dos géneros">
                    {{
                        $generos->links(
                            'vendor.pagination.bootstrap-5',
                        )
                    }}
                </nav>
            </div>
        @endif
    </div>
</x-layout-aplicacao>
