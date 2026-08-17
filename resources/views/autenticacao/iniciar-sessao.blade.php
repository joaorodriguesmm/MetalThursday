{{--
    Apresenta o formulário de autenticação de um utilizador.

    @since 1.0.0
--}}

<x-layout-convidado>
    <x-slot name="titulo">
        Iniciar sessão
    </x-slot>

    <x-estado-sessao class="mb-4" />

    <div class="mb-4 text-center">
        <h1 class="h3 mb-3 fw-normal">
            Iniciar sessão
        </h1>

        <p class="mb-0 text-muted">
            Introduz os teus dados para acederes à MetalThursday.
        </p>
    </div>

    <form
        id="formulario-iniciar-sessao"
        method="POST"
        action="{{ route('autenticacao.iniciar') }}"
        novalidate
    >
        @csrf

        <div class="grupo-campo-formulario mb-3">
            <div class="form-floating">
                <input
                    id="email"
                    class="form-control @error('email') is-invalid @enderror"
                    type="email"
                    name="email"
                    placeholder="E-mail"
                    value="{{ old('email') }}"
                    maxlength="255"
                    autocomplete="username"
                    autocapitalize="none"
                    spellcheck="false"
                    inputmode="email"
                    aria-describedby="erro-email"
                    required
                    autofocus
                    @error('email')
                        aria-invalid="true"
                    @enderror
                >

                <label for="email">
                    E-mail

                    <span
                        class="text-danger"
                        aria-hidden="true"
                    >
                        *
                    </span>
                </label>
            </div>

            <div
                id="erro-email"
                class="invalid-feedback @error('email') d-block @enderror"
                aria-live="polite"
            >
                @error('email')
                    {{ $message }}
                @enderror
            </div>
        </div>

        <div class="grupo-campo-formulario mb-3">
            <div class="input-group">
                <div class="form-floating">
                    <input
                        id="palavra-passe"
                        class="form-control @error('palavra_passe') is-invalid @enderror"
                        type="password"
                        name="palavra_passe"
                        placeholder="Palavra-passe"
                        maxlength="{{ $comprimentoMaximoPalavraPasse }}"
                        autocomplete="current-password"
                        aria-describedby="erro-palavra-passe"
                        required
                        @error('palavra_passe')
                            aria-invalid="true"
                        @enderror
                    >

                    <label for="palavra-passe">
                        Palavra-passe

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
                    data-alvo-palavra-passe="palavra-passe"
                    data-descricao-palavra-passe="a palavra-passe"
                    aria-label="Mostrar a palavra-passe"
                    aria-controls="palavra-passe"
                >
                    <i
                        class="bi bi-eye-fill"
                        data-icone-palavra-passe
                        aria-hidden="true"
                    ></i>
                </button>
            </div>

            <div
                id="erro-palavra-passe"
                class="invalid-feedback @error('palavra_passe') d-block @enderror"
                aria-live="polite"
            >
                @error('palavra_passe')
                    {{ $message }}
                @enderror
            </div>
        </div>

        <div
            class="d-flex justify-content-between align-items-start gap-3 my-3"
        >
            <div class="grupo-campo-formulario">
                <div class="form-check">
                    <input
                        id="manter-sessao-iniciada"
                        class="form-check-input @error('manter_sessao_iniciada') is-invalid @enderror"
                        type="checkbox"
                        name="manter_sessao_iniciada"
                        value="1"
                        @checked(old('manter_sessao_iniciada'))
                        @error('manter_sessao_iniciada')
                            aria-invalid="true"
                            aria-describedby="erro-manter-sessao-iniciada"
                        @enderror
                    >

                    <label
                        class="form-check-label"
                        for="manter-sessao-iniciada"
                    >
                        Manter a sessão iniciada
                    </label>
                </div>

                <div
                    id="erro-manter-sessao-iniciada"
                    class="invalid-feedback @error('manter_sessao_iniciada') d-block @enderror"
                    aria-live="polite"
                >
                    @error('manter_sessao_iniciada')
                        {{ $message }}
                    @enderror
                </div>
            </div>

            <a
                class="small text-decoration-none text-end"
                href="{{
                    route(
                        'autenticacao.recuperar-palavra-passe',
                    )
                }}"
            >
                Esqueceste-te da palavra-passe?
            </a>
        </div>

        <button
            class="w-100 btn btn-lg btn-primary"
            type="submit"
        >
            Iniciar sessão
        </button>
    </form>

    @push('scripts-pagina')
        @vite(
            'resources/js/paginas/iniciarSessao.js'
        )
    @endpush
</x-layout-convidado>
