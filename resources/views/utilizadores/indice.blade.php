{{--
    Apresenta a listagem administrativa dos utilizadores.

    Permite pesquisar pelo nome ou endereço de e-mail, filtrar pelo papel e
    pelo estado atual do acesso e consultar os detalhes de cada utilizador.

    Os utilizadores, os filtros reconhecidos e as opções disponíveis são
    preparados por App\Http\Controllers\Utilizadores\ControladorUtilizador.

    @since 2.0.0
--}}

<x-layout-aplicacao>
    <x-slot name="titulo">
        Utilizadores
    </x-slot>

    <x-slot name="cabecalho">
        <h1 class="h4 mb-0 fw-bold">
            Gestão de utilizadores
        </h1>
    </x-slot>

    <x-estado-sessao class="mb-4" />

    <div class="card shadow-sm bg-dark">
        <div class="card-body">
            <form
                class="mb-4"
                method="GET"
                action="{{ route('utilizadores.indice') }}"
                role="search"
            >
                <div class="row g-3 align-items-end">
                    <div class="col-12 col-lg-5">
                        <label
                            class="form-label"
                            for="pesquisa-utilizadores"
                        >
                            Nome ou endereço de e-mail
                        </label>

                        <input
                            id="pesquisa-utilizadores"
                            class="form-control"
                            type="search"
                            name="pesquisa"
                            value="{{ $pesquisaAtual }}"
                            placeholder="Pesquisar utilizadores"
                            maxlength="100"
                            autocomplete="off"
                        >
                    </div>

                    <div class="col-12 col-sm-6 col-lg-3">
                        <label
                            class="form-label"
                            for="papel-utilizadores"
                        >
                            Papel
                        </label>

                        <select
                            id="papel-utilizadores"
                            class="form-select"
                            name="papel"
                        >
                            <option value="">
                                Todos os papéis
                            </option>

                            @foreach ($papeisDisponiveis as $papel)
                                <option
                                    value="{{ $papel->value }}"
                                    @selected($papelAtual === $papel)
                                >
                                    {{ $papel->etiqueta() }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 col-sm-6 col-lg-2">
                        <label
                            class="form-label"
                            for="estado-utilizadores"
                        >
                            Estado
                        </label>

                        <select
                            id="estado-utilizadores"
                            class="form-select"
                            name="estado"
                        >
                            <option value="">
                                Todos
                            </option>

                            @foreach ($estadosDisponiveis as $valor => $etiqueta)
                                <option
                                    value="{{ $valor }}"
                                    @selected($estadoAtual === $valor)
                                >
                                    {{ $etiqueta }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 col-lg-2">
                        <div class="d-grid gap-2">
                            <button
                                class="btn btn-secondary"
                                type="submit"
                            >
                                <i
                                    class="bi bi-search"
                                    aria-hidden="true"
                                ></i>

                                Filtrar
                            </button>

                            @if ($filtrosAtivos)
                                <a
                                    class="btn btn-outline-secondary"
                                    href="{{ route('utilizadores.indice') }}"
                                >
                                    Limpar
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table
                    class="table table-dark table-striped table-hover align-middle mb-0"
                >
                    <caption class="visually-hidden">
                        Lista administrativa dos utilizadores
                    </caption>

                    <thead>
                        <tr>
                            <th scope="col">
                                Utilizador
                            </th>

                            <th scope="col">
                                Endereço de e-mail
                            </th>

                            <th scope="col">
                                Papel
                            </th>

                            <th scope="col">
                                Acesso
                            </th>

                            <th scope="col">
                                E-mail
                            </th>

                            <th scope="col">
                                Registo
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
                        @forelse ($utilizadores as $utilizador)
                            <tr id="utilizador-{{ $utilizador->getKey() }}">
                                <th scope="row">
                                    <div class="d-flex align-items-center gap-2">
                                        <x-avatar
                                            :utilizador="$utilizador"
                                            :tamanho="40"
                                            descricao=""
                                        />

                                        <span>
                                            {{ $utilizador->nome }}
                                        </span>
                                    </div>
                                </th>

                                <td>
                                    {{ $utilizador->email }}
                                </td>

                                <td>
                                    {{ $utilizador->papel->etiqueta() }}
                                </td>

                                <td>
                                    @if ($utilizador->estaSuspenso())
                                        <span class="badge text-bg-danger">
                                            Suspenso
                                        </span>
                                    @else
                                        <span class="badge text-bg-success">
                                            Ativo
                                        </span>
                                    @endif
                                </td>

                                <td>
                                    @if ($utilizador->email_verified_at !== null)
                                        <span class="badge text-bg-success">
                                            Verificado
                                        </span>
                                    @else
                                        <span class="badge text-bg-warning">
                                            Por verificar
                                        </span>
                                    @endif
                                </td>

                                <td class="text-nowrap">
                                    {{
                                        $utilizador
                                            ->created_at
                                            ?->format(
                                                'd/m/Y H:i',
                                            )
                                        ?? 'Indisponível'
                                    }}
                                </td>

                                <td class="text-end text-nowrap">
                                    @can('view', $utilizador)
                                        <a
                                            class="btn btn-sm btn-info"
                                            href="{{
                                                route(
                                                    'utilizadores.detalhes',
                                                    $utilizador,
                                                )
                                            }}"
                                            aria-label="Ver detalhes de {{ $utilizador->nome }}"
                                            title="Ver detalhes"
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
                                    colspan="7"
                                >
                                    @if ($filtrosAtivos)
                                        Nenhum utilizador corresponde aos filtros.
                                    @else
                                        Ainda não existem utilizadores.
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($utilizadores->hasPages())
            <div class="card-footer bg-dark border-secondary">
                <nav aria-label="Paginação dos utilizadores">
                    {{
                        $utilizadores->links(
                            'vendor.pagination.bootstrap-5',
                        )
                    }}
                </nav>
            </div>
        @endif
    </div>
</x-layout-aplicacao>
