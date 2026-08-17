{{--
    Apresenta o formulário de criação de um género musical.

    Os dados e os valores iniciais do formulário são preparados pelo
    App\Http\Controllers\Musica\ControladorGenero.

    @since 1.0.0
--}}

<x-layout-aplicacao>
    <x-slot name="titulo">
        Criar género
    </x-slot>

    <x-slot name="cabecalho">
        <h1 class="h4 mb-0 fw-bold">
            Criar novo género
        </h1>
    </x-slot>

    <x-estado-sessao class="mb-4" />

    <div class="card shadow-sm">
        <div class="card-body p-4">
            @include(
                'musica.generos._formulario'
            )
        </div>
    </div>

    @push('scripts-pagina')
        @vite(
            'resources/js/paginas/entidades.js'
        )
    @endpush
</x-layout-aplicacao>
