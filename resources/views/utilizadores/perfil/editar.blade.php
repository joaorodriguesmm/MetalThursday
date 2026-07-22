<x-app-layout>
    <x-slot name="title">
        Editar perfil
    </x-slot>

    <x-slot name="header">
        <h1 class="h4 fw-bold mb-0">
            Editar perfil
        </h1>
    </x-slot>

    @php
        $mensagensEstado = [
            'perfil-atualizado' => 'As informações do perfil foram atualizadas com sucesso.',
            'permissoes-email-atualizadas' => 'As permissões de e-mail foram atualizadas com sucesso.',
            'palavra-passe-atualizada' => 'A palavra-passe foi atualizada com sucesso.',
        ];

        $estado = session('estado');

        $mensagemEstado = is_string($estado)
            ? ($mensagensEstado[$estado] ?? $estado)
            : null;
    @endphp

    <main class="container my-4">
        @if ($mensagemEstado !== null)
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div
                        class="alert alert-success alert-dismissible fade show"
                        role="status"
                    >
                        {{ $mensagemEstado }}

                        <button
                            type="button"
                            class="btn-close"
                            data-bs-dismiss="alert"
                            aria-label="Fechar mensagem"
                        ></button>
                    </div>
                </div>
            </div>
        @endif

        @if (session('erro'))
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div
                        class="alert alert-danger alert-dismissible fade show"
                        role="alert"
                    >
                        {{ session('erro') }}

                        <button
                            type="button"
                            class="btn-close"
                            data-bs-dismiss="alert"
                            aria-label="Fechar mensagem"
                        ></button>
                    </div>
                </div>
            </div>
        @endif

        <div class="row justify-content-center">
            <div class="col-lg-8">
                @include(
                    'utilizadores.perfil.parciais._dados-gerais',
                    [
                        'utilizador' => $utilizador,
                    ]
                )

                @include(
                    'utilizadores.perfil.parciais._permissoes-email',
                    [
                        'permissoesEmail' => $permissoesEmail,
                        'identificadoresPermissoesEmail' =>
                            $identificadoresPermissoesEmail,
                    ]
                )

                @include(
                    'utilizadores.perfil.parciais._palavra-passe'
                )
            </div>
        </div>
    </main>

    @push('page-scripts')
        @vite([
            'resources/js/paginas/perfil.js',
        ])
    @endpush
</x-app-layout>
