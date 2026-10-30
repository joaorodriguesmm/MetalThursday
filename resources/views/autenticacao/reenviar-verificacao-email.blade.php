<x-layout-convidado>
    <x-slot name="titulo">
        Reenviar verificação de e-mail
    </x-slot>

    <div class="mb-4 text-center">
        <h1 class="h3 mb-3 fw-normal">Reenviar verificação de e-mail</h1>
        <p class="mb-0 text-muted">
            Se não recebeste a mensagem ou a ligação expirou, indica o teu
            endereço de e-mail para pedires uma nova ligação.
        </p>
    </div>

    <x-estado-sessao class="mb-4" />

    <form method="POST" action="{{ route('verification.send') }}">
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
                    @error('email') aria-invalid="true" @enderror
                >
                <label for="email">
                    E-mail <span class="text-danger" aria-hidden="true">*</span>
                </label>
            </div>
            <div
                id="erro-email"
                class="invalid-feedback @error('email') d-block @enderror"
                aria-live="polite"
            >
                @error('email') {{ $message }} @enderror
            </div>
        </div>

        <button class="w-100 btn btn-lg btn-primary" type="submit">
            Enviar nova ligação de verificação
        </button>
    </form>

    <div class="mt-3 text-center">
        <a class="small text-decoration-none" href="{{ route('login') }}">
            Voltar ao início de sessão
        </a>
    </div>
</x-layout-convidado>
