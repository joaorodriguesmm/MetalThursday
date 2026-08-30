{{--
    Apresenta um cartão completo de uma MetalThursday.

    A preparação dos títulos, secções, contagens, médias e descrições das
    interações é efetuada pela classe
    App\View\Components\MetalThursday\CartaoVistaCompleta.

    @since 1.0.0
--}}

<article
    {{
        $attributes
            ->except([
                'id',
                'data-identificador-metal-thursday',
            ])
            ->class([
                'card',
                'cartao-metal-thursday',
                'mb-4',
                'shadow-sm',
            ])
    }}
    id="metal-thursday-{{ $identificadorMetalThursday }}"
    data-identificador-metal-thursday="{{ $identificadorMetalThursday }}"
>
    <header class="card-header bg-dark text-white">
        <div
            class="d-flex justify-content-between align-items-start gap-3"
        >
            <div class="flex-grow-1">
                <h2 class="h4 card-title mb-1">
                    <a
                        class="text-white text-decoration-none"
                        href="{{
                            route(
                                'metal-thursday.detalhes',
                                $registoMetalThursday,
                            )
                        }}"
                    >
                        {{ $tituloMetalThursday }}
                    </a>
                </h2>

                <p class="mb-0 small text-muted">
                    Autor:
                    {{ $nomeAutor }}

                    <span aria-hidden="true">
                        |
                    </span>

                    Próximo nomeado:
                    {{ $nomeProximoNomeado }}
                </p>
            </div>

            <div
                class="d-flex align-items-center gap-2 flex-shrink-0"
                role="group"
                aria-label="Ações da MetalThursday"
            >
                @can('update', $registoMetalThursday)
                    <a
                        class="btn btn-sm btn-secondary"
                        href="{{
                            route(
                                'metal-thursday.editar',
                                $registoMetalThursday,
                            )
                        }}"
                        aria-label="Editar esta MetalThursday"
                        title="Editar"
                    >
                        <i
                            class="bi bi-pencil-square"
                            aria-hidden="true"
                        ></i>
                    </a>
                @endcan

                @can('delete', $registoMetalThursday)
                    <button
                        class="btn btn-sm btn-danger"
                        type="button"
                        data-tipo-interacao="eliminar"
                        data-endereco="{{
                            route(
                                'metal-thursday.eliminar',
                                $registoMetalThursday,
                            )
                        }}"
                        data-seletor-elemento-removivel="#metal-thursday-{{
                            $identificadorMetalThursday
                        }}"
                        data-mensagem-confirmacao="Tens a certeza de que pretendes eliminar esta MetalThursday?"
                        data-mensagem-sucesso="MetalThursday eliminada com sucesso."
                        data-mensagem-erro="Não foi possível eliminar a MetalThursday."
                        aria-label="Eliminar esta MetalThursday"
                        title="Eliminar"
                    >
                        <i
                            class="bi bi-trash"
                            aria-hidden="true"
                        ></i>
                    </button>
                @endcan
            </div>
        </div>
    </header>

    <div class="card-body">
        @forelse ($seccoesPreparadas as $seccaoPreparada)
            <section
                id="seccao-{{ $seccaoPreparada['identificador'] }}"
                class="seccao-metal-thursday"
            >
                @if (! $seccaoPreparada['temDetalhes'])
                    @if ($seccaoPreparada['titulo'] !== null)
                        <h3 class="h6 text-primary">
                            {{ $seccaoPreparada['titulo'] }}
                        </h3>
                    @endif

                    @if ($seccaoPreparada['descricao'] !== null)
                        <p class="mb-0">
                            {!! nl2br(e($seccaoPreparada['descricao'])) !!}
                        </p>
                    @endif
                @else
                    @if ($seccaoPreparada['descricao'] !== null)
                        <p class="mb-2">
                            {!! nl2br(e($seccaoPreparada['descricao'])) !!}
                        </p>
                    @endif

                    <h3 class="h6">
                        <strong>
                            {{ $seccaoPreparada['tituloApresentacao'] }}
                        </strong>
                    </h3>

                    @if ($seccaoPreparada['temLigacao'])
                        <div class="mt-3">
                            <x-incorporacao
                                :seccao="$seccaoPreparada['modelo']"
                            />
                        </div>
                    @endif

                    @if ($interacoesDisponiveis)
                        <div
                            class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-center gap-3 mt-3"
                            data-contentor-interacoes
                        >
                            <div
                                class="d-flex align-items-center flex-wrap gap-2"
                                role="group"
                                aria-label="Interações da secção"
                            >
                                <button
                                    class="btn btn-sm btn-primary"
                                    type="button"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#{{
                                        $seccaoPreparada['identificadorComentarios']
                                    }}"
                                    aria-controls="{{
                                        $seccaoPreparada['identificadorComentarios']
                                    }}"
                                    aria-expanded="false"
                                >
                                    <i
                                        class="bi bi-chat-dots"
                                        aria-hidden="true"
                                    ></i>

                                    Comentários
                                    (<span data-quantidade-comentarios>{{
                                        $seccaoPreparada['interacoes']['quantidadeComentarios']
                                    }}</span>)
                                </button>

                                <button
                                    class="btn btn-sm btn-warning"
                                    type="button"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modal-avaliacao"
                                    data-tipo-avaliavel="{{
                                        $seccaoPreparada['tipoInteracao']
                                    }}"
                                    data-identificador-avaliavel="{{
                                        $seccaoPreparada['identificador']
                                    }}"
                                    data-nome-avaliavel="{{
                                        $seccaoPreparada['nomeAvaliavel']
                                    }}"
                                    data-pontuacao-utilizador="{{
                                        $seccaoPreparada['interacoes']['pontuacaoUtilizador']
                                    }}"
                                    data-texto-sem-avaliacao="Avaliar"
                                    data-endereco-avaliacao="{{
                                        route(
                                            'avaliacoes.guardar',
                                            [
                                                'tipoAvaliavel' =>
                                                    $seccaoPreparada['tipoInteracao'],

                                                'identificadorAvaliavel' =>
                                                    $seccaoPreparada['identificador'],
                                            ],
                                        )
                                    }}"
                                >
                                    <i
                                        class="bi bi-star-fill"
                                        aria-hidden="true"
                                    ></i>

                                    <span data-texto-avaliacao>
                                        {{
                                            $seccaoPreparada['interacoes']['textoAvaliacao']
                                        }}
                                    </span>
                                </button>

                                <button
                                    class="btn btn-sm btn-success"
                                    type="button"
                                    data-tipo-interacao="alternar-audicao"
                                    data-tipo-audivel="{{
                                        $seccaoPreparada['tipoInteracao']
                                    }}"
                                    data-endereco="{{
                                        route(
                                            'audicoes.alternar',
                                            [
                                                'tipoAudivel' =>
                                                    $seccaoPreparada['tipoInteracao'],

                                                'identificadorAudivel' =>
                                                    $seccaoPreparada['identificador'],
                                            ],
                                        )
                                    }}"
                                >
                                    <i
                                        class="bi bi-headphones"
                                        aria-hidden="true"
                                    ></i>

                                    <span data-texto-interacao>
                                        {{
                                            $seccaoPreparada['interacoes']['textoAudicao']
                                        }}
                                    </span>
                                </button>
                            </div>

                            <div
                                class="d-flex align-items-center gap-3 text-muted small"
                                role="group"
                                aria-label="Resumo das interações da secção"
                            >
                                <button
                                    class="apresentacao-audicoes border-0 bg-transparent text-muted p-0"
                                    type="button"
                                    data-bs-toggle="tooltip"
                                    data-bs-html="true"
                                    data-bs-title="{!!
                                        $seccaoPreparada['interacoes']['descricaoAudicoes']
                                            ->toHtml()
                                    !!}"
                                    aria-label="Consultar detalhes das audições. Total: {{
                                        $seccaoPreparada['interacoes']['quantidadeAudicoes']
                                    }}."
                                >
                                    <i
                                        class="bi bi-headphones"
                                        aria-hidden="true"
                                    ></i>

                                    <span class="quantidade-audicoes">
                                        {{
                                            $seccaoPreparada['interacoes']['quantidadeAudicoes']
                                        }}
                                    </span>
                                </button>

                                <button
                                    class="apresentacao-avaliacoes border-0 bg-transparent text-muted p-0"
                                    type="button"
                                    data-bs-toggle="tooltip"
                                    data-bs-html="true"
                                    data-bs-title="{!!
                                        $seccaoPreparada['interacoes']['descricaoAvaliacoes']
                                            ->toHtml()
                                    !!}"
                                    aria-label="Consultar detalhes das avaliações. Média: {{
                                        $seccaoPreparada['interacoes']['mediaAvaliacoes']
                                    }}. Total: {{
                                        $seccaoPreparada['interacoes']['quantidadeAvaliacoes']
                                    }}."
                                >
                                    <i
                                        class="bi bi-star-fill text-warning"
                                        aria-hidden="true"
                                    ></i>

                                    <strong class="media-avaliacoes">
                                        {{
                                            $seccaoPreparada['interacoes']['mediaAvaliacoes']
                                        }}
                                    </strong>

                                    (<span class="quantidade-avaliacoes">{{
                                        $seccaoPreparada['interacoes']['quantidadeAvaliacoes']
                                    }}</span>)
                                </button>
                            </div>
                        </div>

                        <div
                            id="{{
                                $seccaoPreparada['identificadorComentarios']
                            }}"
                            class="collapse mt-3"
                        >
                            <x-seccao-comentarios
                                :comentavel="$seccaoPreparada['modelo']"
                            />
                        </div>
                    @endif
                @endif

                @if (! $loop->last)
                    <hr class="my-4 border-secondary">
                @endif
            </section>
        @empty
            <p class="mb-0 text-white-50">
                Esta MetalThursday ainda não tem secções.
            </p>
        @endforelse
    </div>

    @if ($interacoesDisponiveis)
        <footer class="card-footer bg-dark text-muted">
            <div
                class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-center gap-3"
                data-contentor-interacoes
            >
                <div
                    class="d-flex align-items-center flex-wrap gap-2"
                    role="group"
                    aria-label="Interações da MetalThursday"
                >
                    <button
                        class="btn btn-sm btn-primary"
                        type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#{{ $identificadorComentariosMetalThursday }}"
                        aria-controls="{{ $identificadorComentariosMetalThursday }}"
                        aria-expanded="false"
                    >
                        <i
                            class="bi bi-chat-dots"
                            aria-hidden="true"
                        ></i>

                        Comentários
                        (<span data-quantidade-comentarios>{{
                            $interacoesMetalThursday['quantidadeComentarios']
                        }}</span>)
                    </button>

                    <button
                        class="btn btn-sm btn-warning"
                        type="button"
                        data-bs-toggle="modal"
                        data-bs-target="#modal-avaliacao"
                        data-tipo-avaliavel="{{ $tipoInteracaoMetalThursday }}"
                        data-identificador-avaliavel="{{
                            $identificadorMetalThursday
                        }}"
                        data-nome-avaliavel="{{ $nomeAvaliavelMetalThursday }}"
                        data-pontuacao-utilizador="{{
                            $interacoesMetalThursday['pontuacaoUtilizador']
                        }}"
                        data-texto-sem-avaliacao="Avaliar MetalThursday"
                        data-endereco-avaliacao="{{
                            route(
                                'avaliacoes.guardar',
                                [
                                    'tipoAvaliavel' =>
                                        $tipoInteracaoMetalThursday,

                                    'identificadorAvaliavel' =>
                                        $identificadorMetalThursday,
                                ],
                            )
                        }}"
                    >
                        <i
                            class="bi bi-star-fill"
                            aria-hidden="true"
                        ></i>

                        <span data-texto-avaliacao>
                            {{ $interacoesMetalThursday['textoAvaliacao'] }}
                        </span>
                    </button>

                    <button
                        class="btn btn-sm btn-success"
                        type="button"
                        data-tipo-interacao="alternar-audicao"
                        data-tipo-audivel="{{ $tipoInteracaoMetalThursday }}"
                        data-endereco="{{
                            route(
                                'audicoes.alternar',
                                [
                                    'tipoAudivel' =>
                                        $tipoInteracaoMetalThursday,

                                    'identificadorAudivel' =>
                                        $identificadorMetalThursday,
                                ],
                            )
                        }}"
                    >
                        <i
                            class="bi bi-headphones"
                            aria-hidden="true"
                        ></i>

                        <span data-texto-interacao>
                            {{ $interacoesMetalThursday['textoAudicao'] }}
                        </span>
                    </button>
                </div>

                <div
                    class="d-flex align-items-center gap-3 text-muted small"
                    role="group"
                    aria-label="Resumo das interações da MetalThursday"
                >
                    <button
                        class="apresentacao-audicoes border-0 bg-transparent text-muted p-0"
                        type="button"
                        data-bs-toggle="tooltip"
                        data-bs-html="true"
                        data-bs-title="{!!
                            $interacoesMetalThursday['descricaoAudicoes']->toHtml()
                        !!}"
                        aria-label="Consultar detalhes das audições. Total: {{
                            $interacoesMetalThursday['quantidadeAudicoes']
                        }}."
                    >
                        <i
                            class="bi bi-headphones"
                            aria-hidden="true"
                        ></i>

                        <span class="quantidade-audicoes">
                            {{ $interacoesMetalThursday['quantidadeAudicoes'] }}
                        </span>
                    </button>

                    <button
                        class="apresentacao-avaliacoes border-0 bg-transparent text-muted p-0"
                        type="button"
                        data-bs-toggle="tooltip"
                        data-bs-html="true"
                        data-bs-title="{!!
                            $interacoesMetalThursday['descricaoAvaliacoes']->toHtml()
                        !!}"
                        aria-label="Consultar detalhes das avaliações. Média: {{
                            $interacoesMetalThursday['mediaAvaliacoes']
                        }}. Total: {{
                            $interacoesMetalThursday['quantidadeAvaliacoes']
                        }}."
                    >
                        <i
                            class="bi bi-star-fill text-warning"
                            aria-hidden="true"
                        ></i>

                        <strong class="media-avaliacoes">
                            {{ $interacoesMetalThursday['mediaAvaliacoes'] }}
                        </strong>

                        (<span class="quantidade-avaliacoes">{{
                            $interacoesMetalThursday['quantidadeAvaliacoes']
                        }}</span>)
                    </button>
                </div>
            </div>
        </footer>

        <div
            id="{{ $identificadorComentariosMetalThursday }}"
            class="collapse p-3"
        >
            <x-seccao-comentarios
                :comentavel="$registoMetalThursday"
            />
        </div>
    @endif
</article>
