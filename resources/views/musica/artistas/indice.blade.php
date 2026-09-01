{{--
    Apresenta a listagem paginada de artistas.

    Permite pesquisar artistas pelo nome e aceder às operações autorizadas
    de consulta, edição e eliminação.

    Os dados da pesquisa e as relações dos artistas são preparados pelo
    App\Http\Controllers\Musica\ControladorArtista.

    @since 1.0.0
--}}

<x-layout-aplicacao>
    <x-slot name="titulo">
        Artistas
    </x-slot>

    <x-slot name="cabecalho">
        <div
            class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3"
        >
            <h1 class="h4 mb-0 fw-bold">
                Artistas
            </h1>

            @can(
                'create',
                App\Models\Musica\Artista::class
            )
                <a
                    class="btn btn-primary"
                    href="{{ route('artistas.criar') }}"
                >
                    <i
                        class="bi bi-plus-lg"
                        aria-hidden="true"
                    ></i>

                    Adicionar artista
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
                action="{{ route('artistas.indice') }}"
                role="search"
            >
                <div class="input-group">
                    <label
                        class="visually-hidden"
                        for="pesquisa-artistas"
                    >
                        Pesquisar artistas pelo nome
                    </label>

                    <input
                        id="pesquisa-artistas"
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
                            href="{{ route('artistas.indice') }}"
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
                        Lista de artistas musicais
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
                        @forelse ($artistas as $artista)
                            <tr id="artista-{{ $artista->getKey() }}">
                                <th scope="row">
                                    {{ $artista->nome }}
                                </th>

                                <td>
                                    {{
                                        $artista->origemGeografica?->nome
                                        ?? 'Não indicada'
                                    }}
                                </td>

                                <td>
                                    @forelse ($artista->generos as $genero)
                                        {{ $genero->nome }}@unless($loop->last), @endunless
                                    @empty
                                        <span class="text-white-50">
                                            Sem géneros
                                        </span>
                                    @endforelse
                                </td>

                                <td class="text-end text-nowrap">
                                    @can('view', $artista)
                                        <a
                                            class="btn btn-sm btn-info"
                                            href="{{
                                                route(
                                                    'artistas.detalhes',
                                                    $artista,
                                                )
                                            }}"
                                            aria-label="Ver detalhes de {{ $artista->nome }}"
                                            title="Ver detalhes"
                                        >
                                            <i
                                                class="bi bi-eye"
                                                aria-hidden="true"
                                            ></i>
                                        </a>
                                    @endcan

                                    @can('update', $artista)
                                        <a
                                            class="btn btn-sm btn-secondary"
                                            href="{{
                                                route(
                                                    'artistas.editar',
                                                    $artista,
                                                )
                                            }}"
                                            aria-label="Editar {{ $artista->nome }}"
                                            title="Editar"
                                        >
                                            <i
                                                class="bi bi-pencil-square"
                                                aria-hidden="true"
                                            ></i>
                                        </a>
                                    @endcan

                                    @can('delete', $artista)
                                        <button
                                            class="btn btn-sm btn-danger"
                                            type="button"
                                            data-tipo-interacao="eliminar"
                                            data-endereco="{{
                                                route(
                                                    'artistas.eliminar',
                                                    $artista,
                                                )
                                            }}"
                                            data-seletor-elemento-removivel="#artista-{{
                                                $artista->getKey()
                                            }}"
                                            data-mensagem-confirmacao="Tens a certeza de que pretendes eliminar este artista?"
                                            data-mensagem-sucesso="Artista eliminado com sucesso."
                                            data-mensagem-erro="Não foi possível eliminar o artista."
                                            aria-label="Eliminar {{ $artista->nome }}"
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
                                        Nenhum artista corresponde à pesquisa.
                                    @else
                                        Ainda não foram criados artistas.
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($artistas->hasPages())
            <div class="card-footer bg-dark border-secondary">
                <nav aria-label="Paginação dos artistas">
                    {{
                        $artistas->links(
                            'vendor.pagination.bootstrap-5',
                        )
                    }}
                </nav>
            </div>
        @endif
    </div>
</x-layout-aplicacao>
