{{--
    Apresenta a página de edição do perfil do utilizador autenticado.

    A página reúne os formulários de atualização dos dados gerais,
    das permissões de e-mail e da palavra-passe.

    Os dados necessários são preparados pelos respetivos controladores.

    @since 1.0.0
    @version 3.0.0
--}}

<x-layout-aplicacao>
    <x-slot name="titulo">
        Editar perfil
    </x-slot>

    <x-slot name="cabecalho">
        <h1 class="h4 mb-0 fw-bold">
            Editar perfil
        </h1>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <x-estado-sessao class="mb-4" />

            @include(
                'utilizadores.perfil.parciais._dados-gerais'
            )

            @include(
                'utilizadores.perfil.parciais._permissoes-email'
            )

            @include(
                'utilizadores.perfil.parciais._palavra-passe'
            )
        </div>
    </div>

    @push('scripts-pagina')
        @vite(
            'resources/js/paginas/perfil.js'
        )
    @endpush
</x-layout-aplicacao>
