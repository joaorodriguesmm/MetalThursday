{{--
    Apresenta o formulário de edição de uma MetalThursday.

    Os campos principais, as secções existentes e os elementos auxiliares
    do formulário são preparados pelo
    App\Http\Controllers\MetalThursday\ControladorMetalThursday.

    @since 1.0.0
--}}

<x-layout-aplicacao>
    <x-slot name="titulo">
        Editar MetalThursday
    </x-slot>

    <x-slot name="cabecalho">
        <div>
            <h1 class="h4 mb-1 fw-bold">
                Editar MetalThursday
            </h1>

            @if (filled($metalThursday->nome))
                <p class="mb-0 text-muted">
                    {{ $metalThursday->nome }}
                </p>
            @endif
        </div>
    </x-slot>

    <x-estado-sessao class="mb-4" />

    <div class="card shadow-sm">
        <div class="card-body p-4">
            <form
                id="formulario-editar-metal-thursday"
                method="POST"
                action="{{
                    route(
                        'metal-thursday.atualizar',
                        $metalThursday,
                    )
                }}"
                autocomplete="off"
                novalidate
            >
                @csrf
                @method('PATCH')

                @include(
                    'metal-thursday.parciais._campos-principais-formulario'
                )

                <hr class="my-4">

                <section aria-labelledby="titulo-seccoes-metal-thursday">
                    <h2
                        id="titulo-seccoes-metal-thursday"
                        class="h5"
                    >
                        Secções da MetalThursday
                    </h2>

                    <p class="text-muted">
                        Altera, adiciona ou remove as secções que constituem
                        esta MetalThursday.
                    </p>

                    <div
                        id="contentor-seccoes"
                        aria-describedby="erro-seccoes"
                    >
                        @foreach (
                            $seccoesFormulario
                            as $indice => $seccao
                        )
                            <x-metal-thursday.item-seccao-formulario
                                :indice="$indice"
                                :seccao="$seccao"
                                :tipos-seccao="$tiposSeccao"
                                :artistas="$artistas"
                            />
                        @endforeach
                    </div>

                    <div
                        id="erro-seccoes"
                        @class([
                            'invalid-feedback',
                            'd-block' =>
                                $errors->has('seccoes')
                                || $errors->has('seccoes.*'),
                        ])
                        aria-live="assertive"
                    >
                        {{
                            $errors->first('seccoes')
                            ?: $errors->first('seccoes.*')
                        }}
                    </div>

                    <button
                        id="botao-adicionar-seccao"
                        class="btn btn-secondary mt-2"
                        type="button"
                        aria-controls="contentor-seccoes"
                    >
                        <i
                            class="bi bi-plus-lg"
                            aria-hidden="true"
                        ></i>

                        Adicionar secção
                    </button>
                </section>

                <div
                    class="d-flex flex-column flex-sm-row justify-content-end gap-2 mt-4"
                >
                    <a
                        class="btn btn-secondary btn-lg"
                        href="{{
                            route(
                                'metal-thursday.detalhes',
                                $metalThursday,
                            )
                        }}"
                    >
                        Cancelar
                    </a>

                    <button
                        class="btn btn-primary btn-lg"
                        type="submit"
                    >
                        Guardar alterações
                    </button>
                </div>
            </form>
        </div>
    </div>

    <template id="modelo-item-seccao">
        <x-metal-thursday.item-seccao-formulario
            indice="__INDICE_SECCAO__"
            :tipos-seccao="$tiposSeccao"
            :artistas="$artistas"
        />
    </template>

    @include(
        'metal-thursday.parciais._modal-criar-edicao'
    )

    <x-metal-thursday.modal-criar-artista
        :origens-geograficas="$origensGeograficas"
        :generos="$generos"
    />

    @include(
        'musica.generos._modal-criar'
    )

    @push('scripts-pagina')
        <script>
            window.configuracaoFormularioMetalThursday =
                {{ Js::from($configuracaoFormularioMetalThursday) }};
        </script>

        @vite(
            'resources/js/paginas/editarMetalThursday.js'
        )
    @endpush
</x-layout-aplicacao>
