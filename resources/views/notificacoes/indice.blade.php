{{--
    Apresenta a listagem paginada das notificações do utilizador autenticado.

    A existência de notificações não lidas é determinada pelo controlador.
    Cada notificação é preparada e apresentada pelo componente
    App\View\Components\Notificacoes\ItemNotificacao.

    @since 1.0.0
    @version 3.0.0
--}}

<x-layout-aplicacao>
    <x-slot name="titulo">
        Notificações
    </x-slot>

    <x-slot name="cabecalho">
        <h1 class="h4 mb-0 fw-bold">
            As minhas notificações
        </h1>
    </x-slot>

    <x-estado-sessao class="mb-4" />

    <div class="card shadow-sm bg-dark text-white">
        <div
            class="card-header d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 border-secondary"
        >
            <h2 class="h5 mb-0">
                Lista de notificações
            </h2>

            @if ($existemNotificacoesNaoLidas)
                <form
                    method="POST"
                    action="{{
                        route(
                            'notificacoes.marcar-todas-como-lidas',
                        )
                    }}"
                >
                    @csrf

                    <button
                        class="btn btn-sm btn-outline-secondary"
                        type="submit"
                    >
                        <i
                            class="bi bi-check2-all"
                            aria-hidden="true"
                        ></i>

                        Marcar todas como lidas
                    </button>
                </form>
            @endif
        </div>

        <div class="card-body p-0">
            @forelse ($notificacoes as $notificacao)
                <x-notificacoes.item-notificacao
                    :notificacao="$notificacao"
                />
            @empty
                <p
                    class="p-4 mb-0 text-center text-muted"
                    role="status"
                >
                    Não tens notificações.
                </p>
            @endforelse
        </div>

        @if ($notificacoes->hasPages())
            <div class="card-footer bg-dark border-secondary">
                <nav aria-label="Paginação das notificações">
                    {{
                        $notificacoes->links(
                            'vendor.pagination.bootstrap-5',
                        )
                    }}
                </nav>
            </div>
        @endif
    </div>
</x-layout-aplicacao>
