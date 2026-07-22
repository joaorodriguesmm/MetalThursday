<x-guest-layout>
    <x-slot name="title">
        Concluir Registo
    </x-slot>

    <div class="text-center mb-4">
        <h2 class="h3 mb-3 fw-normal">Olá {{ $user->first_name }}!</h2>
        <p class="text-muted">Completa o teu registo para acederes à MetalThursday.</p>
    </div>

    <form method="POST" action="{{ route('registo.finalizar') }}" id="invite-register-form" enctype="multipart/form-data" novalidate>
        @csrf
        <input type="hidden" name="invite_code" value="{{ $user->invite_code }}">

        <div class="d-flex align-items-center mb-4">
            <div class="me-3">
                <div class="avatar-circle @if(old('photo')) d-none @endif" id="avatar-circle">
                    <span id="avatar-initials">{{ $user->initials }}</span>
                </div>
                <img id="profile-photo-preview" src="{{ old('photo') ? Storage::url('tmp/' . old('photo')) : '#' }}" alt="Fotografia" class="avatar-preview @if(!old('photo')) d-none @endif">
            </div>
            <div class="flex-grow-1">
                <div class="form-field-group mb-2">
                    <div class="form-floating">
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" placeholder="Nome" value="{{ old('name', $user->name) }}" required>
                        <label for="name">Nome <span class="text-danger">*</span></label>
                    </div>
                    <div class="invalid-feedback @error('name') d-block @enderror">@error('name') {{ $message }} @enderror</div>
                </div>
                <div class="form-field-group mt-2">
                    <label for="profile-photo-input" class="form-label small text-muted d-block">Fotografia (opcional, máx 10MB)</label>
                    <div class="custom-file-input-wrapper">
                        <input class="custom-file-input @error('photo') is-invalid @enderror" type="file" id="profile-photo-input" name="photo" accept="image/*">
                        <label class="custom-file-label" for="profile-photo-input">
                            <span id="custom-file-text">{{ old('photo') ? 'Alterar ficheiro' : 'Escolher ficheiro' }}</span>
                        </label>
                    </div>
                    <div id="photo-js-error" class="invalid-feedback @error('photo') d-block @enderror">@error('photo') {{ $message }} @enderror</div>
                </div>
            </div>
        </div>

        <div class="form-field-group mb-3">
            <div class="form-floating">
                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" placeholder="E-mail" value="{{ old('email') }}" required>
                <label for="email">E-mail <span class="text-danger">*</span></label>
            </div>
            <div class="invalid-feedback @error('email') d-block @enderror">@error('email') {{ $message }} @enderror</div>
        </div>

        <div class="form-field-group mb-3">
            <div class="input-group">
                <div class="form-floating">
                    <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" placeholder="Palavra-passe" required minlength="8">
                    <label for="password">Palavra-passe <span class="text-danger">*</span></label>
                </div>
                <span class="input-group-text password-toggle-icon" data-target="password"><i class="bi bi-eye-slash-fill"></i></span>
            </div>
            <div class="invalid-feedback @error('password') d-block @enderror">@error('password') {{ $message }} @enderror</div>
        </div>

        <div class="form-field-group mb-3">
            <div class="input-group">
                <div class="form-floating">
                    <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" placeholder="Confirmar Palavra-passe" required>
                    <label for="password_confirmation">Confirmar Palavra-passe <span class="text-danger">*</span></label>
                </div>
                <span class="input-group-text password-toggle-icon" data-target="password_confirmation"><i class="bi bi-eye-slash-fill"></i></span>
            </div>
            <div class="invalid-feedback @error('password_confirmation') d-block @enderror"></div>
        </div>

        <hr class="my-4">

        <div class="mb-3">
            <h4 class="h5">Permissões de E-mail</h4>
            <p class="small text-muted">Escolhe as notificações que queres receber por e-mail.</p>
            @php
                $allPermission = $permissions->firstWhere('slug', 'all');
                $otherPermissions = $permissions->where('slug', '!=', 'all');
            @endphp
            @if ($allPermission)
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="email_permissions[]" value="{{ $allPermission->id }}" id="perm-all">
                    <label class="form-check-label" for="perm-all">{{ $allPermission->name }}</label>
                    <i class="bi bi-info-circle-fill custom-tooltip" role="button" data-bs-toggle="tooltip" data-bs-placement="right" data-bs-title="{{ $allPermission->description }}"></i>
                </div>
            @endif
            @foreach ($otherPermissions as $permission)
                <div class="form-check other-permission-item mt-2">
                    <input class="form-check-input" type="checkbox" name="email_permissions[]" value="{{ $permission->id }}" id="perm-{{ $permission->id }}">
                    <label class="form-check-label" for="perm-{{ $permission->id }}">{{ $permission->name }}</label>
                    <i class="bi bi-info-circle-fill custom-tooltip" role="button" data-bs-toggle="tooltip" data-bs-placement="right" data-bs-title="{{ $permission->description }}"></i>
                </div>
            @endforeach
            @error('email_permissions.*') <div class="invalid-feedback d-block mt-2">{{ $message }}</div> @enderror
            @error('invite_code') <div class="invalid-feedback d-block mt-2">{{ $message }}</div> @enderror
        </div>

        <button class="w-100 btn btn-lg btn-primary mt-4" type="submit">Finalizar registo</button>
    </form>

    @push('page-scripts')
        @vite('resources/js/paginas/registoConvite.js')
    @endpush
</x-guest-layout>
