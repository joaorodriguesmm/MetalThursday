{{--
    Apresenta o formulário para solicitar uma ligação de redefinição
    da palavra-passe.

    @since 1.0.0
    @version 2.1.0
--}}

<x-layout-convidado>
    <x-slot name="titulo">
        Recuperar palavra-passe
    </x-slot>

    <div class="mb-4 text-center">
        <h1 class="h3 mb-3 fw-normal">
            Esqueceste-te da palavra-passe?
        </h1>

        <p class="mb-0 text-muted">
            Indica o teu endereço de e-mail e enviaremos uma ligação
            para redefinires a palavra-passe.
        </p>
    </div>

    <x-estado-sessao class="mb-4" />

    <form
        id="formulario-recuperar-palavra-passe"
        method="POST"
        action="{{
            route(
                'autenticacao.enviar-ligacao-redefinicao',
            )
        }}"
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
                    autocomplete="email"
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

        <button
            class="w-100 btn btn-lg btn-primary"
            type="submit"
        >
            Enviar ligação de redefinição
        </button>

        <div class="mt-3 text-center">
            <a
                class="small text-decoration-none"
                href="{{
                    route(
                        'autenticacao.iniciar-sessao',
                    )
                }}"
            >
                Voltar ao início de sessão
            </a>
        </div>
    </form>

    @push('scripts-pagina')
        @vite(
            'resources/js/paginas/recuperarPalavraPasse.js'
        )
    @endpush
</x-layout-convidado>
