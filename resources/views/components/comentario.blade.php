{{--
    Apresenta um comentário e as respetivas respostas.

    A preparação do comentário, relações, utilizadores, contagens e
    identificadores principais é efetuada pela classe
    App\View\Components\Comentario.

    @since 1.0.0
    @version 3.0.0
--}}

<article
    {{
        $attributes
            ->except([
                'id',
                'data-identificador-comentario',
                'data-identificador-comentario-principal',
            ])
            ->class([
                'comentario',
                'mt-3',
            ])
    }}
    id="comentario-{{ $identificadorComentario }}"
    data-identificador-comentario="{{ $identificadorComentario }}"
    data-identificador-comentario-principal="{{ $identificadorPrincipal }}"
>
    <div class="d-flex align-items-start">
        <div class="me-3 flex-shrink-0">
            <x-avatar
                :utilizador="$utilizador"
                :tamanho="40"
                descricao=""
            />
        </div>

        <div class="flex-grow-1">
            <div class="p-3 rounded bg-secondary">
                <header
                    class="d-flex justify-content-between align-items-start gap-3"
                >
                    <p class="mb-0 fw-semibold text-white">
                        {{ $nomeUtilizador }}
                    </p>

                    @if ($momentoCriacao !== null)
                        <time
                            class="small text-white-50 flex-shrink-0"
                            datetime="{{ $momentoCriacao->toIso8601String() }}"
                            title="{{ $momentoCriacao->format('d/m/Y H:i') }}"
                        >
                            {{ $momentoCriacao->diffForHumans() }}
                        </time>
                    @endif
                </header>

                <div
                    class="conteudo-comentario mt-2"
                    data-conteudo-comentario
                >
                    <p class="mb-0 text-white-50">
                        {!! nl2br(e($comentario->conteudo)) !!}
                    </p>
                </div>

                <div
                    id="contentor-edicao-comentario-{{ $identificadorComentario }}"
                    class="contentor-edicao-comentario mt-2"
                    aria-hidden="true"
                    hidden
                >
                    <form
                        id="formulario-edicao-comentario-{{ $identificadorComentario }}"
                        class="formulario-edicao-comentario"
                        method="POST"
                        action="{{
                            route(
                                'comentarios.atualizar',
                                $comentario,
                            )
                        }}"
                        data-formulario-edicao-comentario
                        data-endereco="{{
                            route(
                                'comentarios.atualizar',
                                $comentario,
                            )
                        }}"
                        data-identificador-comentario="{{
                            $identificadorComentario
                        }}"
                        novalidate
                    >
                        @csrf
                        @method('PATCH')

                        <div
                            class="grupo-campo-formulario"
                            data-grupo-campo
                        >
                            <label
                                class="visually-hidden"
                                for="conteudo-edicao-comentario-{{ $identificadorComentario }}"
                            >
                                Editar comentário
                            </label>

                            <textarea
                                id="conteudo-edicao-comentario-{{ $identificadorComentario }}"
                                class="form-control form-control-sm bg-dark text-white border-secondary"
                                name="conteudo"
                                rows="3"
                                maxlength="2000"
                                aria-describedby="erro-edicao-comentario-{{ $identificadorComentario }}"
                                data-campo-conteudo-comentario
                                required
                            >{{ $comentario->conteudo }}</textarea>

                            <div
                                id="erro-edicao-comentario-{{ $identificadorComentario }}"
                                class="invalid-feedback mt-1"
                                aria-live="polite"
                            ></div>
                        </div>

                        <div class="mt-2 text-end">
                            <button
                                class="btn btn-sm btn-outline-light"
                                type="button"
                                data-tipo-interacao="cancelar-edicao-comentario"
                                aria-controls="contentor-edicao-comentario-{{ $identificadorComentario }}"
                            >
                                Cancelar
                            </button>

                            <button
                                class="btn btn-sm btn-primary"
                                type="submit"
                            >
                                Guardar alterações
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div
                class="acoes-comentario small mt-2 d-flex align-items-center flex-wrap gap-1"
                aria-label="Ações do comentário"
            >
                <button
                    class="btn btn-sm btn-link text-muted text-decoration-none p-0"
                    type="button"
                    data-tipo-interacao="alternar-gosto"
                    data-endereco="{{
                        route(
                            'gostos.alternar',
                            $comentario,
                        )
                    }}"
                    data-endereco-utilizadores-gosto="{{
                        route(
                            'comentarios.utilizadores-com-gosto',
                            $comentario,
                        )
                    }}"
                    data-identificador-comentario="{{
                        $identificadorComentario
                    }}"
                    data-bs-toggle="tooltip"
                    data-bs-html="true"
                    data-bs-title="A carregar..."
                    aria-label="{{
                        $temGosto
                            ? "Remover gosto. {$quantidadeGostos} gostos."
                            : "Adicionar gosto. {$quantidadeGostos} gostos."
                    }}"
                    aria-pressed="{{ $temGosto ? 'true' : 'false' }}"
                >
                    <i
                        class="bi {{
                            $temGosto
                                ? 'bi-heart-fill text-danger'
                                : 'bi-heart'
                        }}"
                        data-icone-gosto
                        aria-hidden="true"
                    ></i>

                    <span data-texto-gosto>
                        Gosto
                    </span>

                    <span aria-hidden="true">
                        (
                    </span>

                    <span
                        class="quantidade-gostos"
                        data-quantidade-gostos
                    >
                        {{ $quantidadeGostos }}
                    </span>

                    <span aria-hidden="true">
                        )
                    </span>
                </button>

                <span
                    class="text-muted mx-1"
                    aria-hidden="true"
                >
                    &middot;
                </span>

                <button
                    class="btn btn-sm btn-link text-muted text-decoration-none p-0"
                    type="button"
                    data-tipo-interacao="alternar-resposta-comentario"
                    aria-controls="contentor-resposta-comentario-{{ $identificadorComentario }}"
                    aria-expanded="false"
                >
                    Responder
                </button>

                @can('update', $comentario)
                    <span
                        class="text-muted mx-1"
                        aria-hidden="true"
                    >
                        &middot;
                    </span>

                    <button
                        class="btn btn-sm btn-link text-muted text-decoration-none p-0"
                        type="button"
                        data-tipo-interacao="iniciar-edicao-comentario"
                        aria-controls="contentor-edicao-comentario-{{ $identificadorComentario }}"
                        aria-expanded="false"
                    >
                        Editar
                    </button>
                @endcan

                @can('delete', $comentario)
                    <span
                        class="text-muted mx-1"
                        aria-hidden="true"
                    >
                        &middot;
                    </span>

                    <button
                        class="btn btn-sm btn-link text-danger text-decoration-none p-0"
                        type="button"
                        data-tipo-interacao="eliminar"
                        data-endereco="{{
                            route(
                                'comentarios.eliminar',
                                $comentario,
                            )
                        }}"
                        data-seletor-elemento-removivel="#comentario-{{
                            $identificadorComentario
                        }}"
                        data-mensagem-confirmacao="Tens a certeza de que pretendes eliminar este comentário?"
                        data-mensagem-sucesso="Comentário eliminado com sucesso."
                        data-mensagem-erro="Não foi possível eliminar o comentário."
                    >
                        Eliminar
                    </button>
                @endcan
            </div>

            <div
                id="contentor-resposta-comentario-{{ $identificadorComentario }}"
                class="contentor-formulario-resposta mt-3"
                aria-hidden="true"
                hidden
            >
                <form
                    id="formulario-resposta-comentario-{{ $identificadorComentario }}"
                    class="formulario-resposta-comentario"
                    method="POST"
                    action="{{
                        route(
                            'comentarios.respostas.guardar',
                            $comentario,
                        )
                    }}"
                    data-formulario-resposta-comentario
                    data-endereco="{{
                        route(
                            'comentarios.respostas.guardar',
                            $comentario,
                        )
                    }}"
                    data-mensagem-sucesso="Resposta publicada com sucesso."
                    data-mensagem-erro="Não foi possível publicar a resposta."
                    data-identificador-comentario-pai="{{
                        $identificadorComentario
                    }}"
                    data-identificador-comentario-principal="{{
                        $identificadorPrincipal
                    }}"
                    novalidate
                >
                    @csrf

                    <div class="d-flex align-items-start">
                        <div class="me-3 flex-shrink-0">
                            <x-avatar
                                :utilizador="$utilizadorAutenticado"
                                :tamanho="30"
                                descricao=""
                            />
                        </div>

                        <div
                            class="flex-grow-1 grupo-campo-formulario"
                            data-grupo-campo
                        >
                            <label
                                class="visually-hidden"
                                for="conteudo-resposta-comentario-{{ $identificadorComentario }}"
                            >
                                Resposta
                            </label>

                            <textarea
                                id="conteudo-resposta-comentario-{{ $identificadorComentario }}"
                                class="form-control form-control-sm bg-secondary text-white border-secondary"
                                name="conteudo"
                                rows="2"
                                maxlength="2000"
                                placeholder="Escreve a tua resposta"
                                aria-describedby="erro-resposta-comentario-{{ $identificadorComentario }}"
                                required
                            ></textarea>

                            <div
                                id="erro-resposta-comentario-{{ $identificadorComentario }}"
                                class="invalid-feedback mt-1"
                                aria-live="polite"
                            ></div>

                            <div class="mt-2 text-end">
                                <button
                                    class="btn btn-sm btn-outline-light"
                                    type="button"
                                    data-tipo-interacao="cancelar-resposta-comentario"
                                    aria-controls="contentor-resposta-comentario-{{ $identificadorComentario }}"
                                >
                                    Cancelar
                                </button>

                                <button
                                    class="btn btn-sm btn-primary"
                                    type="submit"
                                >
                                    Publicar resposta
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div
                class="respostas-comentario ms-4 border-start ps-3 mt-3"
                data-respostas-comentario
                @if ($respostas->isEmpty())
                    hidden
                @endif
            >
                @foreach ($respostas as $resposta)
                    <x-comentario
                        :comentario="$resposta"
                        :identificador-comentario-principal="$identificadorPrincipal"
                    />
                @endforeach
            </div>
        </div>
    </div>
</article>
