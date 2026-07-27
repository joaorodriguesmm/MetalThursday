{{--
    Apresenta a lista paginada de edições.

    As operações disponíveis são condicionadas pelas políticas de autorização
    associadas ao modelo App\Models\MetalThursday\Edicao.

    @since 1.0.0
    @version 3.0.0
--}}

<x-layout-aplicacao>
    <x-slot name="titulo">
        Edições
    </x-slot>

    <x-slot name="cabecalho">
        <div
            class="d-flex justify-content-between align-items-center gap-3"
        >
            <h1 class="h4 mb-0 fw-bold">
                Edições
            </h1>

            @can(
                'create',
                App\Models\MetalThursday\Edicao::class
            )
                <a
                    class="btn btn-primary"
                    href="{{ route('edicoes.criar') }}"
                >
                    Adicionar edição
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="card shadow-sm bg-dark">
        <div class="card-body">
            <x-estado-sessao class="mb-4" />

            @error('edicao')
                <div
                    class="alert alert-danger"
                    role="alert"
                >
                    {{ $message }}
                </div>
            @enderror

            <div class="table-responsive">
                <table
                    class="table table-dark table-striped table-hover align-middle mb-0"
                >
                    <caption class="visually-hidden">
                        Lista de edições
                    </caption>

                    <thead>
                        <tr>
                            <th scope="col">
                                Nome
                            </th>

                            <th scope="col">
                                Data de início
                            </th>

                            <th scope="col">
                                Data de fim
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
                        @forelse ($edicoes as $edicao)
                            <tr id="edicao-{{ $edicao->getKey() }}">
                                <th scope="row">
                                    {{ $edicao->nome }}
                                </th>

                                <td>
                                    @if ($edicao->data_inicio !== null)
                                        <time
                                            datetime="{{
                                                $edicao->data_inicio->format(
                                                    'Y-m-d',
                                                )
                                            }}"
                                        >
                                            {{
                                                $edicao->data_inicio->format(
                                                    'd/m/Y',
                                                )
                                            }}
                                        </time>
                                    @else
                                        <span class="text-muted">
                                            Não definida
                                        </span>
                                    @endif
                                </td>

                                <td>
                                    @if ($edicao->data_fim !== null)
                                        <time
                                            datetime="{{
                                                $edicao->data_fim->format(
                                                    'Y-m-d',
                                                )
                                            }}"
                                        >
                                            {{
                                                $edicao->data_fim->format(
                                                    'd/m/Y',
                                                )
                                            }}
                                        </time>
                                    @else
                                        <span class="text-muted">
                                            Em curso
                                        </span>
                                    @endif
                                </td>

                                <td class="text-end text-nowrap">
                                    @can('view', $edicao)
                                        <a
                                            class="btn btn-sm btn-info"
                                            href="{{
                                                route(
                                                    'edicoes.detalhes',
                                                    $edicao,
                                                )
                                            }}"
                                            aria-label="Ver detalhes de {{ $edicao->nome }}"
                                            title="Ver detalhes"
                                        >
                                            <i
                                                class="bi bi-trophy-fill"
                                                aria-hidden="true"
                                            ></i>
                                        </a>
                                    @endcan

                                    @can('update', $edicao)
                                        <a
                                            class="btn btn-sm btn-secondary"
                                            href="{{
                                                route(
                                                    'edicoes.editar',
                                                    $edicao,
                                                )
                                            }}"
                                            aria-label="Editar {{ $edicao->nome }}"
                                            title="Editar"
                                        >
                                            <i
                                                class="bi bi-pencil-square"
                                                aria-hidden="true"
                                            ></i>
                                        </a>
                                    @endcan

                                    @can('delete', $edicao)
                                        <button
                                            class="btn btn-sm btn-danger"
                                            type="button"
                                            data-tipo-interacao="eliminar"
                                            data-endereco="{{
                                                route(
                                                    'edicoes.eliminar',
                                                    $edicao,
                                                )
                                            }}"
                                            data-seletor-elemento-removivel="#edicao-{{
                                                $edicao->getKey()
                                            }}"
                                            data-mensagem-confirmacao="Tens a certeza de que pretendes eliminar esta edição?"
                                            data-mensagem-sucesso="Edição eliminada com sucesso."
                                            data-mensagem-erro="Não foi possível eliminar a edição."
                                            aria-label="Eliminar {{ $edicao->nome }}"
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
                                    Ainda não foram criadas edições.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($edicoes->hasPages())
            <div class="card-footer bg-dark border-secondary">
                {{ $edicoes->links() }}
            </div>
        @endif
    </div>
</x-layout-aplicacao>
