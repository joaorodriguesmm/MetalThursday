{{--
    Apresenta o formulário de criação de uma MetalThursday.

    O formulário permite criar dinamicamente secções, edições, bandas
    e géneros sem abandonar a página.

    As secções anteriores e a configuração necessária ao JavaScript são
    preparadas pelo
    App\Http\Controllers\MetalThursday\ControladorMetalThursday.

    @since 1.0.0
    @version 3.0.0
--}}

<x-layout-aplicacao>
    <x-slot name="titulo">
        Criar MetalThursday
    </x-slot>

    <x-slot name="cabecalho">
        <h1 class="h4 mb-0 fw-bold">
            Criar MetalThursday
        </h1>
    </x-slot>

    <x-estado-sessao class="mb-4" />

    <div class="card shadow-sm">
        <div class="card-body p-4">
            <form
                id="formulario-criar-metal-thursday"
                method="POST"
                action="{{ route('metal-thursday.guardar') }}"
                novalidate
            >
                @csrf

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
                        Adiciona as secções que constituem esta
                        MetalThursday.
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
                                :bandas="$bandas"
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
                    class="d-flex justify-content-end gap-2 mt-4"
                >
                    <a
                        class="btn btn-secondary btn-lg"
                        href="{{ route('inicio') }}"
                    >
                        Cancelar
                    </a>

                    <button
                        class="btn btn-primary btn-lg"
                        type="submit"
                    >
                        Criar MetalThursday
                    </button>
                </div>
            </form>
        </div>
    </div>

    <template id="modelo-item-seccao">
        <x-metal-thursday.item-seccao-formulario
            indice="__INDICE_SECCAO__"
            :tipos-seccao="$tiposSeccao"
            :bandas="$bandas"
        />
    </template>

    @include(
        'metal-thursday.parciais._modal-criar-edicao'
    )

    <x-metal-thursday.modal-criar-banda
        :paises="$paises"
        :generos="$generos"
    />

    @include(
        'metal-thursday.parciais._modal-criar-genero'
    )

    @push('scripts-pagina')
        <script>
            window.configuracaoCriacaoMetalThursday =
                @json($configuracaoCriacaoMetalThursday);
        </script>

        @vite(
            'resources/js/paginas/criarMetalThursday.js'
        )
    @endpush
</x-layout-aplicacao>
