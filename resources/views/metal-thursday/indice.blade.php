{{--
    Apresenta a listagem principal de MetalThursdays.

    A página suporta vistas completa e simplificada, filtros dinâmicos,
    ordenação, paginação, avaliações e audições.

    Os dados dos filtros e das vistas são preparados pelo
    App\Http\Controllers\MetalThursday\ControladorMetalThursday.

    @since 1.0.0
--}}

<x-layout-aplicacao>
    <x-slot name="titulo">
        MetalThursday
    </x-slot>

    <x-slot name="cabecalho">
        <h1 class="h4 mb-0 fw-bold">
            MetalThursdays
        </h1>
    </x-slot>

    <div class="row g-4">
        <div class="col-lg-9">
            <x-estado-sessao class="mb-4" />

            <x-metal-thursday.reservas-pendentes />

            @include(
                'metal-thursday.parciais._filtros'
            )

            @if ($vistaAtual === $vistaSimplificada)
                <x-metal-thursday.tabela-vista-simplificada
                    :seccoes-simplificadas="$seccoesSimplificadas"
                />
            @else
                @if ($registosMetalThursday->hasPages())
                    <nav
                        class="mb-4"
                        aria-label="Paginação superior das MetalThursdays"
                    >
                        {{
                            $registosMetalThursday->links(
                                'vendor.pagination.bootstrap-5',
                            )
                        }}
                    </nav>
                @endif

                @forelse (
                    $registosMetalThursday
                    as $registoMetalThursday
                )
                    <x-metal-thursday.cartao-vista-completa
                        :registo-metal-thursday="$registoMetalThursday"
                    />
                @empty
                    <div
                        class="alert alert-info"
                        role="status"
                    >
                        Nenhum resultado encontrado.
                    </div>
                @endforelse

                @if ($registosMetalThursday->hasPages())
                    <nav
                        class="mt-4"
                        aria-label="Paginação inferior das MetalThursdays"
                    >
                        {{
                            $registosMetalThursday->links(
                                'vendor.pagination.bootstrap-5',
                            )
                        }}
                    </nav>
                @endif
            @endif
        </div>

        <aside
            class="col-lg-3"
            aria-label="Ações da página"
        >
            <x-metal-thursday.acoes-laterais />
        </aside>
    </div>

    @include(
        'metal-thursday.parciais._modal-avaliacao'
    )

    @push('scripts-pagina')
        <script
            id="configuracao-listagem-metal-thursday"
            type="application/json"
        >
            @json($configuracaoListagemMetalThursday)
        </script>

        @vite(
            'resources/js/paginas/inicio.js'
        )
    @endpush
</x-layout-aplicacao>
