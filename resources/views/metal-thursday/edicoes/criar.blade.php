{{--
    Apresenta a página de criação de uma edição.

    Os dados necessários ao formulário são preparados pelo
    App\Http\Controllers\MetalThursday\ControladorEdicao.

    @since 1.0.0
    @version 3.0.0
--}}

<x-layout-aplicacao>
    <x-slot name="titulo">
        Criar edição
    </x-slot>

    <x-slot name="cabecalho">
        <h1 class="h4 mb-0 fw-bold">
            Criar nova edição
        </h1>
    </x-slot>

    <div class="card shadow-sm">
        <div class="card-body">
            @include(
                'metal-thursday.edicoes._formulario'
            )
        </div>
    </div>

    @push('scripts-pagina')
        @vite('resources/js/paginas/entidades.js')
    @endpush
</x-layout-aplicacao>
