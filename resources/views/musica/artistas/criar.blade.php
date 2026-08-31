{{--
    Apresenta o formulário de criação de um artista.

    Os dados e os valores iniciais do formulário são preparados pelo
    App\Http\Controllers\Musica\ControladorArtista.

    @since 1.0.0
--}}

<x-layout-aplicacao>
    <x-slot name="titulo">
        Criar artista
    </x-slot>

    <x-slot name="cabecalho">
        <h1 class="h4 mb-0 fw-bold">
            Criar novo artista
        </h1>
    </x-slot>

    <x-estado-sessao class="mb-4" />

    <div class="card shadow-sm">
        <div class="card-body p-4">
            @include(
                'musica.artistas._formulario'
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
