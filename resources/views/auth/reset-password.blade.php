<x-guest-layout>
    <x-slot name="title">
        Redefinir Palavra-passe
    </x-slot>

    <div class="text-center mb-4">
        <h2 class="h3 mb-3 fw-normal">Redefinir Palavra-passe</h2>
    </div>

    @if (isset($error))
        <div class="alert alert-danger">{{ $error }}</div>
        <div class="text-center mt-3"><a class="small text-decoration-none" href="{{ route('password.request') }}">Pedir novo link</a></div>
    @else
        <form method="POST" action="{{ route('password.store') }}" id="reset-password-form" novalidate>
            @csrf
            <input type="hidden" name="token" value="{{ $token ?? $request->route('token') }}">
            <input type="hidden" name="email" value="{{ $email ?? old('email') }}">

            <div class="form-field-group mb-3">
                <div class="input-group">
                    <div class="form-floating">
                        <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" placeholder="Nova palavra-passe" required minlength="8">
                        <label for="password">Nova palavra-passe <span class="text-danger">*</span></label>
                    </div>
                    <span class="input-group-text password-toggle-icon" data-target="password"><i class="bi bi-eye-slash-fill"></i></span>
                </div>
                <div class="invalid-feedback @error('password') d-block @enderror">@error('password') {{ $message }} @enderror</div>
            </div>

            <div class="form-field-group mb-3">
                <div class="input-group">
                    <div class="form-floating">
                        <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" placeholder="Confirmar nova palavra-passe" required>
                        <label for="password_confirmation">Confirmar nova palavra-passe <span class="text-danger">*</span></label>
                    </div>
                    <span class="input-group-text password-toggle-icon" data-target="password_confirmation"><i class="bi bi-eye-slash-fill"></i></span>
                </div>
                <div class="invalid-feedback"></div>
            </div>

            @error('token') <div class="invalid-feedback d-block mt-2">{{ $message }}</div> @enderror
            @error('email') <div class="invalid-feedback d-block mt-2">{{ $message }}</div> @enderror

            <button type="submit" class="w-100 btn btn-lg btn-primary mt-4">Redefinir palavra-passe</button>
        </form>
    @endif

    @push('page-scripts')
        @vite(['resources/js/pages/resetPassword.js'])
    @endpush
</x-guest-layout>
