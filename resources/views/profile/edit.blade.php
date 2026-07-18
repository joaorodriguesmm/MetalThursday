<x-app-layout>
    <x-slot name="title">
        Editar Perfil
    </x-slot>

    <x-slot name="header">
        <h2 class="h4 font-weight-bold">
            Editar Perfil
        </h2>
    </x-slot>

    <div class="container my-4">
        @if (session('status'))
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        @if (session('status') === 'profile-updated')
                            Informações do perfil atualizadas com sucesso!
                        @elseif (session('status') === 'password-updated')
                            Palavra-passe atualizada com sucesso!
                        @elseif (session('status') === 'email-permissions-updated')
                            Permissões de email atualizadas com sucesso!
                        @endif
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                </div>
            </div>
        @endif

        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h3 class="h5 mb-0">Informações do Perfil</h3>
                        <p class="card-subtitle text-muted small mt-1">Atualiza o nome, e-mail e foto da tua conta.</p>
                    </div>
                    <div class="card-body">
                        <form id="update-profile-form" method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data">
                            @csrf
                            @method('patch')

                            <div class="d-flex align-items-center mb-4">
                                <div class="me-3">
                                    <div class="avatar-circle @if($user->photo) d-none @endif" id="avatar-circle">
                                        <span id="avatar-initials">{{ $user->initials }}</span>
                                    </div>
                                    <img
                                        id="profile-photo-preview"
                                        src="{{ $user->photo ? $user->photo_url : '#' }}"
                                        alt="{{ $user->name }}"
                                        class="avatar-preview @if(!$user->photo) d-none @endif"
                                    >
                                </div>

                                <div class="flex-grow-1">
                                    <div class="form-field-group mb-2">
                                        <div class="form-floating">
                                            <input
                                                type="text"
                                                class="form-control @error('name') is-invalid @enderror"
                                                id="name"
                                                name="name"
                                                placeholder="Nome"
                                                value="{{ old('name', $user->name) }}"
                                                required
                                            >
                                            <label for="name">Nome <span class="text-danger">*</span></label>
                                        </div>
                                        <div class="invalid-feedback @error('name') d-block @enderror">
                                            @error('name') {{ $message }} @enderror
                                        </div>
                                    </div>

                                    <div class="form-field-group mt-2">
                                        <label for="profile-photo-input" class="form-label small text-muted d-block">Fotografia (opcional, máx 10MB)</label>
                                        <div class="custom-file-input-wrapper">
                                            <input class="custom-file-input @error('photo') is-invalid @enderror" type="file" id="profile-photo-input" name="photo" accept="image/*">
                                            <label class="custom-file-label" for="profile-photo-input">
                                                <span id="custom-file-text"></span>
                                            </label>
                                        </div>
                                        <div id="photo-js-error" class="invalid-feedback @error('photo') d-block @enderror">
                                            @error('photo') {{ $message }} @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-field-group mb-3">
                                <div class="form-floating">
                                    <input
                                        type="email"
                                        class="form-control @error('email') is-invalid @enderror"
                                        id="email"
                                        name="email"
                                        placeholder="E-mail"
                                        value="{{ old('email', $user->email) }}"
                                        required
                                    >
                                    <label for="email">E-mail <span class="text-danger">*</span></label>
                                </div>
                                <div class="invalid-feedback @error('email') d-block @enderror">
                                    @error('email') {{ $message }} @enderror
                                </div>
                            </div>

                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary">Guardar Alterações</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h3 class="h5 mb-0">Permissões de E-mail</h3>
                        <p class="card-subtitle text-muted small mt-1">Escolhe as notificações que queres receber por e-mail.</p>
                    </div>
                    <div class="card-body">
                        <form id="update-email-permissions-form" method="post" action="{{ route('profile.email_permissions.update') }}">
                            @csrf
                            @method('patch')

                            @php
                                $allPermission = $allEmailPermissions->firstWhere('slug', 'all');
                                $otherPermissions = $allEmailPermissions->where('slug', '!=', 'all');
                            @endphp

                            @if ($allPermission)
                                <div class="form-check">
                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        name="email_permissions[]"
                                        value="{{ $allPermission->id }}"
                                        id="perm-all"
                                        {{ in_array($allPermission->id, $userEmailPermissions) ? 'checked' : '' }}
                                    >
                                    <label class="form-check-label" for="perm-all">{{ $allPermission->name }}</label>
                                    <i class="bi bi-info-circle-fill custom-tooltip" role="button" data-bs-toggle="tooltip" data-bs-placement="right" data-bs-title="{{ $allPermission->description }}"></i>
                                </div>
                            @endif

                            @foreach ($otherPermissions as $permission)
                                <div class="form-check other-permission-item mt-2">
                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        name="email_permissions[]" value="{{ $permission->id }}"
                                        id="perm-{{ $permission->id }}"
                                        {{ in_array($permission->id, $userEmailPermissions) ? 'checked' : '' }}
                                    >
                                    <label class="form-check-label" for="perm-{{ $permission->id }}">{{ $permission->name }}</label>
                                    <i class="bi bi-info-circle-fill custom-tooltip" role="button" data-bs-toggle="tooltip" data-bs-placement="right" data-bs-title="{{ $permission->description }}"></i>
                                </div>
                            @endforeach

                            @error('email_permissions.*')
                                <div class="invalid-feedback d-block mt-2">{{ $message }}</div>
                            @enderror

                            <div class="d-flex justify-content-end mt-4">
                                <button type="submit" class="btn btn-primary">Guardar Permissões</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-header">
                        <h3 class="h5 mb-0">Atualizar Palavra-passe</h3>
                        <p class="card-subtitle text-muted small mt-1">Atualiza a tua palavra-passe.</p>
                    </div>
                    <div class="card-body">
                        <form id="update-password-form" method="post" action="{{ route('profile.password.update') }}">
                            @csrf
                            @method('put')

                            <div class="form-field-group mb-3">
                                <div class="input-group">
                                    <div class="form-floating">
                                        <input
                                            type="password"
                                            class="form-control @error('current_password') is-invalid @enderror"
                                            id="current_password"
                                            name="current_password"
                                            placeholder="Palavra-passe Atual"
                                            required
                                        >
                                        <label for="current_password">Palavra-passe Atual <span class="text-danger">*</span></label>
                                    </div>
                                    <span class="input-group-text password-toggle-icon" data-target="current_password"><i class="bi bi-eye-slash-fill"></i></span>
                                </div>
                                <div class="invalid-feedback @error('current_password') d-block @enderror">
                                    @error('current_password') {{ $message }} @enderror
                                </div>
                            </div>

                            <div class="form-field-group mb-3">
                                <div class="input-group">
                                    <div class="form-floating">
                                        <input
                                            type="password"
                                            class="form-control @error('password') is-invalid @enderror"
                                            id="password"
                                            name="password"
                                            placeholder="Nova Palavra-passe"
                                            required
                                        >
                                        <label for="password">Nova Palavra-passe <span class="text-danger">*</span></label>
                                    </div>
                                    <span class="input-group-text password-toggle-icon" data-target="password"><i class="bi bi-eye-slash-fill"></i></span>
                                </div>
                                <div class="invalid-feedback @error('password') d-block @enderror">
                                    @error('password') {{ $message }} @enderror
                                </div>
                            </div>

                            <div class="form-field-group mb-3">
                                <div class="input-group">
                                    <div class="form-floating">
                                        <input
                                            type="password"
                                            class="form-control"
                                            id="password_confirmation"
                                            name="password_confirmation"
                                            placeholder="Confirmar Nova Palavra-passe"
                                            required
                                        >
                                        <label for="password_confirmation">Confirmar Nova Palavra-passe <span class="text-danger">*</span></label>
                                    </div>
                                    <span class="input-group-text password-toggle-icon" data-target="password_confirmation"><i class="bi bi-eye-slash-fill"></i></span>
                                </div>
                                <div class="invalid-feedback @error('password_confirmation') d-block @enderror">
                                    @error('password_confirmation') {{ $message }} @enderror
                                </div>
                            </div>

                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary">Guardar Password</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('page-scripts')
        @vite(['resources/js/pages/profile.js'])
    @endpush
</x-app-layout>
