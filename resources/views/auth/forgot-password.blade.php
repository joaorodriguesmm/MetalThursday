<x-guest-layout>
    <x-slot name="title">
        Recuperar Palavra-passe
    </x-slot>

    <div class="text-center mb-4">
        <h2 class="h3 mb-3 fw-normal">Esqueceste-te da Palavra-passe?</h2>
        <p class="text-muted small">Não há problema. Indica o teu e-mail e será enviado um link para a poderes redefinir.</p>
    </div>

    <x-session-status class="mb-4" />

    <form method="POST" action="{{ route('password.email') }}" id="forgot-password-form" novalidate>
        @csrf
        <div class="form-field-group mb-3">
            <div class="form-floating">
                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" placeholder="E-mail" value="{{ old('email') }}" required autofocus>
                <label for="email">E-mail <span class="text-danger">*</span></label>
            </div>
            <div class="invalid-feedback @error('email') d-block @enderror">@error('email') {{ $message }} @enderror</div>
        </div>
        <button type="submit" class="w-100 btn btn-lg btn-primary">Enviar Link de Redefinição</button>
        <div class="text-center mt-3">
            <a class="small text-decoration-none" href="{{ route('login') }}">Voltar à página de início de sessão</a>
        </div>
    </form>

    @push('page-scripts')
        @vite(['resources/js/paginas/recuperarPalavraPasse.js'])
    @endpush
</x-guest-layout>
