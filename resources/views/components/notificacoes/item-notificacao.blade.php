{{--
    Apresenta uma notificação individual.

    Todos os valores e endereços utilizados na apresentação são preparados
    pela classe App\View\Components\Notificacoes\ItemNotificacao.

    @since 3.0.0
    @version 2.0.0
--}}

<article
    @class([
        'list-group-item',
        'bg-dark',
        'text-white',
        'px-3',
        'py-3',
        'border-secondary',
        'text-muted' =>
            $dados['lida'],
        'fw-semibold' =>
            ! $dados['lida'],
    ])
>
    <div
        class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3"
    >
        <div class="d-flex align-items-start">
            <i
                class="bi {{ $dados['icone'] }} fs-4 me-3 {{ $dados['cor'] }}"
                aria-hidden="true"
            ></i>

            <div>
                <h3 class="h6 mb-1">
                    {{ $dados['titulo'] }}
                </h3>

                <p class="mb-1 small">
                    {{ $dados['mensagem'] }}
                </p>

                @if ($dados['dataCriacao'] !== null)
                    <time
                        class="small text-muted"
                        datetime="{{ $dados['dataCriacao'] }}"
                    >
                        {{ $dados['tempoRelativo'] }}
                    </time>
                @else
                    <small class="text-muted">
                        {{ $dados['tempoRelativo'] }}
                    </small>
                @endif
            </div>
        </div>

        <div class="d-flex align-items-center gap-2">
            @if ($dados['enderecoMarcarComoLida'] !== null)
                <form
                    method="POST"
                    action="{{ $dados['enderecoMarcarComoLida'] }}"
                >
                    @csrf

                    <button
                        class="btn btn-sm btn-outline-success"
                        type="submit"
                        aria-label="Marcar a notificação como lida"
                        title="Marcar como lida"
                    >
                        <i
                            class="bi bi-check-circle"
                            aria-hidden="true"
                        ></i>
                    </button>
                </form>
            @endif

            @if ($dados['ligacao'] !== null)
                <a
                    class="btn btn-sm btn-outline-info"
                    href="{{ $dados['ligacao'] }}"
                    aria-label="Ver detalhes da notificação"
                    title="Ver detalhes"
                >
                    <i
                        class="bi bi-eye"
                        aria-hidden="true"
                    ></i>
                </a>
            @endif
        </div>
    </div>
</article>
