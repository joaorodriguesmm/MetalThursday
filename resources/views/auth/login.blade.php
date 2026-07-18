<x-guest-layout>
    <x-slot name="title">
        Iniciar Sessão
    </x-slot>

    <x-session-status class="mb-4" />

    <div class="text-center mb-4">
        <h2 class="h3 mb-3 fw-normal">Iniciar Sessão</h2>
    </div>

    <form method="POST" action="{{ route('login') }}" id="login-form" novalidate>
        @csrf
        <div class="form-field-group mb-3">
            <div class="form-floating">
                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" placeholder="E-mail" value="{{ old('email') }}" required autofocus>
                <label for="email">E-mail <span class="text-danger">*</span></label>
            </div>
            <div class="invalid-feedback @error('email') d-block @enderror">@error('email') {{ $message }} @enderror</div>
        </div>
        <div class="form-field-group mb-3">
            <div class="input-group">
                <div class="form-floating">
                    <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" placeholder="Password" required>
                    <label for="password">Palavra-passe <span class="text-danger">*</span></label>
                </div>
                <span class="input-group-text password-toggle-icon" data-target="password"><i class="bi bi-eye-slash-fill"></i></span>
            </div>
            <div class="invalid-feedback @error('password') d-block @enderror">@error('password') {{ $message }} @enderror</div>
        </div>
        <div class="d-flex justify-content-between align-items-center my-3">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="remember" id="remember_me">
                <label class="form-check-label" for="remember_me">Lembrar-me</label>
            </div>
            @if (Route::has('password.request'))
                <a class="small text-decoration-none" href="{{ route('password.request') }}">Esqueceste a palavra-passe?</a>
            @endif
        </div>
        <button type="submit" class="w-100 btn btn-lg btn-primary">Entrar</button>
    </form>

    @push('page-scripts')
        @vite(['resources/js/pages/login.js'])
    @endpush
</x-guest-layout>
