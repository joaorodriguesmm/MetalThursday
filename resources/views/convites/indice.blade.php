{{--
    Apresenta a listagem administrativa dos convites.

    Permite pesquisar, filtrar pelo estado e revogar convites ainda não
    utilizados. O código original nunca é recuperado nem apresentado nesta
    listagem.

    @since 2.0.0
--}}

<x-layout-aplicacao>
    <x-slot name="titulo">
        Convites
    </x-slot>

    <x-slot name="cabecalho">
        <div
            class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3"
        >
            <h1 class="h4 mb-0 fw-bold">
                Gestão de convites
            </h1>

            @can('create', App\Models\Autenticacao\Convite::class)
                <a
                    class="btn btn-primary"
                    href="{{ route('convites.criar') }}"
                >
                    <i
                        class="bi bi-person-plus me-2"
                        aria-hidden="true"
                    ></i>

                    Criar convite
                </a>
            @endcan
        </div>
    </x-slot>

    <x-estado-sessao class="mb-4" />

    @if ($errors->revogacao_convite->any())
        <div
            class="alert alert-danger mb-4"
            role="alert"
            aria-live="assertive"
        >
            {{ $errors->revogacao_convite->first() }}
        </div>
    @endif

    <div class="card shadow-sm bg-dark">
        <div class="card-body">
            <form
                class="mb-4"
                method="GET"
                action="{{ route('convites.indice') }}"
                role="search"
            >
                <div class="row g-3 align-items-end">
                    <div class="col-12 col-lg-6">
                        <label
                            class="form-label"
                            for="pesquisa-convites"
                        >
                            Nome ou endereço de e-mail
                        </label>

                        <input
                            id="pesquisa-convites"
                            class="form-control"
                            type="search"
                            name="pesquisa"
                            value="{{ $pesquisaAtual }}"
                            placeholder="Pesquisar convites"
                            maxlength="100"
                            autocomplete="off"
                        >
                    </div>

                    <div class="col-12 col-sm-6 col-lg-3">
                        <label
                            class="form-label"
                            for="estado-convites"
                        >
                            Estado
                        </label>

                        <select
                            id="estado-convites"
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

                    <div class="col-12 col-sm-6 col-lg-3">
                        <div class="d-flex flex-wrap gap-2">
                            <button
                                class="btn btn-primary flex-grow-1"
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
                                    class="btn btn-secondary flex-grow-1"
                                    href="{{ route('convites.indice') }}"
                                >
                                    <i
                                        class="bi bi-arrow-counterclockwise me-1"
                                        aria-hidden="true"
                                    ></i>

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
                        Lista administrativa dos convites
                    </caption>

                    <thead>
                        <tr>
                            <th scope="col">
                                Convidado
                            </th>

                            <th scope="col">
                                Estado
                            </th>

                            <th scope="col">
                                Criado por
                            </th>

                            <th scope="col">
                                Datas
                            </th>

                            <th scope="col">
                                Utilização ou revogação
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
                        @forelse ($convites as $convite)
                            <tr id="convite-{{ $convite->getKey() }}">
                                <th scope="row">
                                    <span class="d-block">
                                        {{ $convite->nome_convidado }}
                                    </span>

                                    <span class="small text-muted text-break">
                                        {{
                                            $convite->email_destino
                                            ?? 'Sem destinatário específico'
                                        }}
                                    </span>
                                </th>

                                <td>
                                    @if ($convite->foiUtilizado())
                                        <span class="badge text-bg-success">
                                            Utilizado
                                        </span>
                                    @elseif ($convite->foiRevogado())
                                        <span class="badge text-bg-danger">
                                            Revogado
                                        </span>
                                    @elseif ($convite->estaExpirado())
                                        <span class="badge text-bg-warning">
                                            Expirado
                                        </span>
                                    @else
                                        <span class="badge text-bg-info">
                                            Disponível
                                        </span>
                                    @endif
                                </td>

                                <td>
                                    @if ($convite->criador !== null)
                                        <span class="d-block">
                                            {{ $convite->criador->nome }}
                                        </span>

                                        <span class="small text-muted text-break">
                                            {{ $convite->criador->email }}
                                        </span>
                                    @else
                                        Sistema
                                    @endif
                                </td>

                                <td class="small">
                                    <span class="d-block">
                                        Criado:
                                        {{
                                            $convite
                                                ->created_at
                                                ?->format(
                                                    'd/m/Y H:i',
                                                )
                                            ?? 'Indisponível'
                                        }}
                                    </span>

                                    <span class="d-block">
                                        Expira:
                                        {{
                                            $convite
                                                ->expira_em
                                                ?->format(
                                                    'd/m/Y H:i',
                                                )
                                            ?? 'Sem expiração'
                                        }}
                                    </span>
                                </td>

                                <td>
                                    @if ($convite->foiUtilizado())
                                        <span class="d-block">
                                            {{
                                                $convite
                                                    ->utilizado_em
                                                    ?->format(
                                                        'd/m/Y H:i',
                                                    )
                                                ?? 'Data indisponível'
                                            }}
                                        </span>

                                        @if ($convite->utilizador !== null)
                                            <span class="small text-muted d-block">
                                                {{ $convite->utilizador->nome }}
                                            </span>

                                            @can('view', $convite->utilizador)
                                                <a
                                                    class="small"
                                                    href="{{
                                                        route(
                                                            'utilizadores.detalhes',
                                                            $convite->utilizador,
                                                        )
                                                    }}"
                                                >
                                                    Ver utilizador
                                                </a>
                                            @endcan
                                        @else
                                            <span class="small text-muted">
                                                Utilizador eliminado
                                            </span>
                                        @endif
                                    @elseif ($convite->foiRevogado())
                                        <span class="d-block">
                                            {{
                                                $convite
                                                    ->revogado_em
                                                    ?->format(
                                                        'd/m/Y H:i',
                                                    )
                                                ?? 'Data indisponível'
                                            }}
                                        </span>

                                        @if ($convite->responsavelRevogacao !== null)
                                            <span class="small text-muted d-block">
                                                {{ $convite->responsavelRevogacao->nome }}
                                            </span>
                                        @else
                                            <span class="small text-muted">
                                                Responsável indisponível
                                            </span>
                                        @endif
                                    @else
                                        Ainda não utilizado
                                    @endif
                                </td>

                                <td class="text-end">
                                    @can('revogar', $convite)
                                        @if ($convite->estaDisponivel())
                                            <button
                                                class="btn btn-sm btn-danger text-nowrap"
                                                type="button"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modal-revogar-convite-{{ $convite->getKey() }}"
                                                aria-label="Revogar convite de {{ $convite->nome_convidado }}"
                                            >
                                                <i
                                                    class="bi bi-x-circle me-1"
                                                    aria-hidden="true"
                                                ></i>

                                                Revogar
                                            </button>
                                        @endif
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td
                                    class="py-4 text-center"
                                    colspan="6"
                                >
                                    @if ($filtrosAtivos)
                                        Nenhum convite corresponde aos filtros.
                                    @else
                                        Ainda não existem convites.
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @foreach ($convites as $convite)
                @can('revogar', $convite)
                    @if ($convite->estaDisponivel())
                        <div
                            id="modal-revogar-convite-{{ $convite->getKey() }}"
                            class="modal fade"
                            tabindex="-1"
                            aria-labelledby="titulo-revogar-convite-{{ $convite->getKey() }}"
                            aria-describedby="aviso-revogar-convite-{{ $convite->getKey() }}"
                            aria-hidden="true"
                        >
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content bg-dark text-light border-secondary">
                                    <div class="modal-header">
                                        <h2
                                            id="titulo-revogar-convite-{{ $convite->getKey() }}"
                                            class="modal-title fs-5"
                                        >
                                            Revogar convite
                                        </h2>

                                        <button
                                            class="btn btn-secondary btn-sm bi bi-x-lg ms-auto"
                                            type="button"
                                            data-bs-dismiss="modal"
                                            aria-label="Fechar"
                                        ></button>
                                    </div>

                                    <div class="modal-body">
                                        <p>
                                            Tens a certeza de que queres revogar o convite de
                                            <strong>{{ $convite->nome_convidado }}</strong>?
                                        </p>

                                        <p
                                            id="aviso-revogar-convite-{{ $convite->getKey() }}"
                                            class="text-warning mb-0"
                                        >
                                            Esta ação impede a utilização da ligação de convite
                                            e não pode ser anulada.
                                        </p>
                                    </div>

                                    <div class="modal-footer">
                                        <button
                                            class="btn btn-secondary"
                                            type="button"
                                            data-bs-dismiss="modal"
                                        >
                                            Cancelar
                                        </button>

                                        <form
                                            method="POST"
                                            action="{{ route('convites.revogar', $convite) }}"
                                        >
                                            @csrf
                                            @method('PATCH')

                                            <input
                                                type="hidden"
                                                name="confirmar_revogacao"
                                                value="1"
                                            >

                                            <button
                                                class="btn btn-danger"
                                                type="submit"
                                            >
                                                Confirmar revogação
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                @endcan
            @endforeach
        </div>

        @if ($convites->hasPages())
            <div class="card-footer bg-dark border-secondary">
                <nav aria-label="Paginação dos convites">
                    {{
                        $convites->links(
                            'vendor.pagination.bootstrap-5',
                        )
                    }}
                </nav>
            </div>
        @endif
    </div>
</x-layout-aplicacao>
