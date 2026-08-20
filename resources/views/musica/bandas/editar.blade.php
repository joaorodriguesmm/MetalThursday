{{--
    Apresenta o formulário de edição de uma banda.

    Os dados e os valores iniciais do formulário são preparados pelo
    App\Http\Controllers\Musica\ControladorBanda.

    @since 1.0.0
--}}

<x-layout-aplicacao>
    <x-slot name="titulo">
        Editar banda
    </x-slot>

    <x-slot name="cabecalho">
        <h1 class="h4 mb-0 fw-bold">
            Editar banda: {{ $banda->nome }}
        </h1>
    </x-slot>

    <x-estado-sessao class="mb-4" />

    <div class="card shadow-sm">
        <div class="card-body p-4">
            @include(
                'musica.bandas._formulario'
            )
        </div>
    </div>

    @include(
        'musica.generos._modal-criar'
    )

    @push('scripts-pagina')
        @vite(
            'resources/js/paginas/entidades.js'
        )
    @endpush
</x-layout-aplicacao>
