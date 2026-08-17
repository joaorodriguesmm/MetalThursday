{{--
    Apresenta o formulário e a lista de comentários de uma entidade.

    A preparação dos identificadores, endereço, utilizador autenticado e
    comentários é efetuada pela classe
    App\View\Components\SeccaoComentarios.

    @since 1.0.0
--}}

<section
    {{
        $attributes
            ->except([
                'aria-label',
            ])
            ->class([
                'card',
                'card-body',
                'bg-dark',
                'border-secondary',
            ])
    }}
    aria-label="Comentários"
>
    <form
        id="{{ $identificadorFormulario }}"
        class="formulario-comentario mb-4"
        method="POST"
        action="{{ $enderecoGuardarComentario }}"
        data-endereco="{{ $enderecoGuardarComentario }}"
        data-mensagem-sucesso="Comentário publicado com sucesso."
        data-mensagem-erro="Não foi possível publicar o comentário."
        novalidate
    >
        @csrf

        <div class="d-flex align-items-start">
            <div class="me-3 flex-shrink-0">
                <x-avatar
                    :utilizador="$utilizadorAutenticado"
                    :tamanho="40"
                    descricao=""
                />
            </div>

            <div
                class="flex-grow-1 grupo-campo-formulario"
                data-grupo-campo
            >
                <label
                    class="visually-hidden"
                    for="{{ $identificadorConteudo }}"
                >
                    Comentário
                </label>

                <textarea
                    id="{{ $identificadorConteudo }}"
                    class="form-control bg-secondary text-white border-secondary"
                    name="conteudo"
                    rows="2"
                    maxlength="2000"
                    placeholder="Escreve o teu comentário"
                    aria-describedby="{{ $identificadorErro }}"
                    required
                ></textarea>

                <div
                    id="{{ $identificadorErro }}"
                    class="invalid-feedback mt-1"
                    aria-live="polite"
                    aria-atomic="true"
                ></div>

                <div class="mt-2 text-end">
                    <button
                        class="btn btn-sm btn-primary"
                        type="submit"
                    >
                        Comentar
                    </button>
                </div>
            </div>
        </div>
    </form>

    <div class="lista-comentarios">
        @forelse ($comentarios as $comentario)
            <x-comentario
                :comentario="$comentario"
            />
        @empty
            <p class="sem-comentarios small text-muted text-center">
                Ainda não existem comentários.
                Sê a primeira pessoa a comentar!
            </p>
        @endforelse
    </div>
</section>
