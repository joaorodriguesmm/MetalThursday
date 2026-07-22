<section
    class="card shadow-sm mb-4"
    aria-labelledby="titulo-dados-perfil"
>
    <div class="card-header">
        <h2
            id="titulo-dados-perfil"
            class="h5 mb-0"
        >
            Informações do perfil
        </h2>

        <p class="card-subtitle text-muted small mt-1">
            Atualiza o teu nome, endereço de e-mail e fotografia.
        </p>
    </div>

    <div class="card-body">
        <form
            id="formulario-atualizar-perfil"
            method="post"
            action="{{ route('perfil.atualizar') }}"
            enctype="multipart/form-data"
            novalidate
        >
            @csrf
            @method('patch')

            <div class="d-flex align-items-center mb-4">
                <div class="me-3">
                    <div
                        id="circulo-avatar"
                        class="avatar-circle {{ $utilizador->fotografia !== null ? 'd-none' : '' }}"
                        aria-hidden="{{ $utilizador->fotografia !== null ? 'true' : 'false' }}"
                    >
                        <span id="iniciais-avatar">
                            {{ $utilizador->iniciais }}
                        </span>
                    </div>

                    <img
                        id="previsualizacao-fotografia"
                        @if ($utilizador->url_fotografia !== null)
                            src="{{ $utilizador->url_fotografia }}"
                        @endif
                        alt="Fotografia de {{ $utilizador->nome }}"
                        class="avatar-preview {{ $utilizador->fotografia === null ? 'd-none' : '' }}"
                        aria-hidden="{{ $utilizador->fotografia === null ? 'true' : 'false' }}"
                    >
                </div>

                <div class="flex-grow-1">
                    <div class="form-field-group mb-3">
                        <div class="form-floating">
                            <input
                                type="text"
                                id="nome"
                                name="nome"
                                class="form-control @error('nome', 'perfil') is-invalid @enderror"
                                placeholder="Nome"
                                value="{{ old('nome', $utilizador->nome) }}"
                                autocomplete="name"
                                minlength="3"
                                maxlength="255"
                                aria-describedby="erro-nome"
                                @error('nome', 'perfil')
                                    aria-invalid="true"
                                @enderror
                                required
                            >

                            <label for="nome">
                                Nome
                                <span
                                    class="text-danger"
                                    aria-hidden="true"
                                >*</span>
                            </label>
                        </div>

                        <div
                            id="erro-nome"
                            class="invalid-feedback @error('nome', 'perfil') d-block @enderror"
                        >
                            @error('nome', 'perfil')
                                {{ $message }}
                            @enderror
                        </div>
                    </div>

                    <div class="form-field-group">
                        <label
                            for="fotografia"
                            class="form-label small text-muted"
                        >
                            Fotografia
                            <span class="fw-normal">
                                (opcional, máximo de 10 MB)
                            </span>
                        </label>

                        <div class="custom-file-input-wrapper">
                            <input
                                type="file"
                                id="fotografia"
                                name="fotografia"
                                class="custom-file-input @error('fotografia', 'perfil') is-invalid @enderror"
                                accept="image/jpeg,image/png,image/webp"
                                aria-describedby="texto-fotografia erro-fotografia"
                                @error('fotografia', 'perfil')
                                    aria-invalid="true"
                                @enderror
                            >

                            <label
                                class="custom-file-label"
                                for="fotografia"
                            >
                                <span id="texto-fotografia">
                                    Selecionar fotografia
                                </span>
                            </label>
                        </div>

                        <div
                            id="erro-fotografia"
                            class="invalid-feedback @error('fotografia', 'perfil') d-block @enderror"
                            @if (!$errors->perfil->has('fotografia'))
                                hidden
                            @endif
                        >
                            @error('fotografia', 'perfil')
                                {{ $message }}
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-field-group mb-3">
                <div class="form-floating">
                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-control @error('email', 'perfil') is-invalid @enderror"
                        placeholder="Endereço de e-mail"
                        value="{{ old('email', $utilizador->email) }}"
                        autocomplete="email"
                        maxlength="255"
                        aria-describedby="ajuda-email erro-email"
                        @error('email', 'perfil')
                            aria-invalid="true"
                        @enderror
                        required
                    >

                    <label for="email">
                        Endereço de e-mail
                        <span
                            class="text-danger"
                            aria-hidden="true"
                        >*</span>
                    </label>
                </div>

                <div
                    id="erro-email"
                    class="invalid-feedback @error('email', 'perfil') d-block @enderror"
                >
                    @error('email', 'perfil')
                        {{ $message }}
                    @enderror
                </div>

                <div
                    id="ajuda-email"
                    class="form-text"
                >
                    A alteração do endereço termina a sessão e exige uma nova
                    verificação por e-mail.
                </div>
            </div>

            @if (!$utilizador->hasVerifiedEmail())
                <div
                    class="alert alert-warning py-2"
                    role="status"
                >
                    O endereço de e-mail atual ainda não foi verificado.
                </div>
            @endif

            <div class="d-flex justify-content-end">
                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Guardar alterações
                </button>
            </div>
        </form>
    </div>
</section>
