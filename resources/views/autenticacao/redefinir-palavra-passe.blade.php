{{--
    Apresenta o formulário para definir uma nova palavra-passe através
    de uma ligação de redefinição válida.

    @since 1.0.0
    @version 3.0.0
--}}

<x-layout-convidado>
    <x-slot name="titulo">
        Redefinir palavra-passe
    </x-slot>

    <div class="mb-4 text-center">
        <h1 class="h3 mb-3 fw-normal">
            Redefinir palavra-passe
        </h1>

        <p class="mb-0 text-muted">
            Define uma nova palavra-passe para a tua conta.
        </p>
    </div>

    <x-estado-sessao class="mb-4" />

    @error('ligacao_redefinicao')
        <div
            class="alert alert-danger"
            role="alert"
            aria-live="assertive"
        >
            {{ $message }}
        </div>
    @enderror

    <form
        id="formulario-redefinir-palavra-passe"
        method="POST"
        action="{{
            route(
                'autenticacao.atualizar-palavra-passe',
            )
        }}"
        novalidate
    >
        @csrf

        <input
            id="codigo-redefinicao"
            type="hidden"
            name="codigo_redefinicao"
            value="{{ $codigoRedefinicao }}"
        >

        <input
            id="email"
            type="hidden"
            name="email"
            value="{{ old('email', $email) }}"
        >

        <div class="grupo-campo-formulario mb-3">
            <div class="input-group">
                <div class="form-floating">
                    <input
                        id="nova-palavra-passe"
                        class="form-control @error('palavra_passe') is-invalid @enderror"
                        type="password"
                        name="palavra_passe"
                        placeholder="Nova palavra-passe"
                        minlength="{{ $comprimentoMinimoPalavraPasse }}"
                        maxlength="{{ $comprimentoMaximoPalavraPasse }}"
                        autocomplete="new-password"
                        aria-describedby="requisitos-nova-palavra-passe erro-nova-palavra-passe"
                        required
                        autofocus
                        @error('palavra_passe')
                            aria-invalid="true"
                        @enderror
                    >

                    <label for="nova-palavra-passe">
                        Nova palavra-passe

                        <span
                            class="text-danger"
                            aria-hidden="true"
                        >
                            *
                        </span>
                    </label>
                </div>

                <button
                    class="input-group-text"
                    type="button"
                    data-alvo-palavra-passe="nova-palavra-passe"
                    data-descricao-palavra-passe="a nova palavra-passe"
                    aria-label="Mostrar a nova palavra-passe"
                    aria-controls="nova-palavra-passe"
                    aria-pressed="false"
                >
                    <i
                        class="bi bi-eye-fill"
                        data-icone-palavra-passe
                        aria-hidden="true"
                    ></i>
                </button>
            </div>

            <div
                id="requisitos-nova-palavra-passe"
                class="form-text"
            >
                Utiliza pelo menos
                {{ $comprimentoMinimoPalavraPasse }}
                caracteres, incluindo uma letra maiúscula, uma letra
                minúscula, um número e um símbolo.
            </div>

            <div
                id="erro-nova-palavra-passe"
                class="invalid-feedback @error('palavra_passe') d-block @enderror"
                aria-live="polite"
            >
                @error('palavra_passe')
                    {{ $message }}
                @enderror
            </div>
        </div>

        <div class="grupo-campo-formulario mb-3">
            <div class="input-group">
                <div class="form-floating">
                    <input
                        id="confirmacao-nova-palavra-passe"
                        class="form-control @error('confirmacao_palavra_passe') is-invalid @enderror"
                        type="password"
                        name="confirmacao_palavra_passe"
                        placeholder="Confirmar nova palavra-passe"
                        minlength="{{ $comprimentoMinimoPalavraPasse }}"
                        maxlength="{{ $comprimentoMaximoPalavraPasse }}"
                        autocomplete="new-password"
                        aria-describedby="erro-confirmacao-nova-palavra-passe"
                        required
                        @error('confirmacao_palavra_passe')
                            aria-invalid="true"
                        @enderror
                    >

                    <label for="confirmacao-nova-palavra-passe">
                        Confirmar nova palavra-passe

                        <span
                            class="text-danger"
                            aria-hidden="true"
                        >
                            *
                        </span>
                    </label>
                </div>

                <button
                    class="input-group-text"
                    type="button"
                    data-alvo-palavra-passe="confirmacao-nova-palavra-passe"
                    data-descricao-palavra-passe="a confirmação da nova palavra-passe"
                    aria-label="Mostrar a confirmação da nova palavra-passe"
                    aria-controls="confirmacao-nova-palavra-passe"
                    aria-pressed="false"
                >
                    <i
                        class="bi bi-eye-fill"
                        data-icone-palavra-passe
                        aria-hidden="true"
                    ></i>
                </button>
            </div>

            <div
                id="erro-confirmacao-nova-palavra-passe"
                class="invalid-feedback @error('confirmacao_palavra_passe') d-block @enderror"
                aria-live="polite"
            >
                @error('confirmacao_palavra_passe')
                    {{ $message }}
                @enderror
            </div>
        </div>

        <button
            class="w-100 btn btn-lg btn-primary mt-4"
            type="submit"
        >
            Redefinir palavra-passe
        </button>

        <div class="mt-3 text-center">
            <a
                class="small text-decoration-none"
                href="{{
                    route(
                        'password.request',
                    )
                }}"
            >
                Pedir uma nova ligação
            </a>
        </div>
    </form>

    @push('scripts-pagina')
        @vite(
            'resources/js/paginas/redefinirPalavraPasse.js'
        )
    @endpush
</x-layout-convidado>
