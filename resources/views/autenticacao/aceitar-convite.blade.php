{{--
    Apresenta o formulário de aceitação de um convite e conclusão do
    registo de um novo utilizador.

    @since 1.0.0
    @version 2.0.0
--}}

<x-layout-convidado>
    <x-slot name="titulo">
        Concluir registo
    </x-slot>

    <div class="mb-4 text-center">
        <h1 class="h3 mb-3 fw-normal">
            Olá, {{ $convite->nome_convidado }}!
        </h1>

        <p class="mb-0 text-muted">
            Completa o teu registo para acederes à MetalThursday.
        </p>
    </div>

    @error('codigo_convite')
        <div
            class="alert alert-danger"
            role="alert"
        >
            {{ $message }}
        </div>
    @enderror

    <form
        id="formulario-aceitar-convite"
        method="POST"
        action="{{ route('convites.registar') }}"
        enctype="multipart/form-data"
        novalidate
    >
        @csrf

        <input
            id="codigo-convite"
            type="hidden"
            name="codigo_convite"
            value="{{ $codigoConvite }}"
        >

        <div class="d-flex align-items-center mb-4">
            <div class="contentor-avatar-registo me-3">
                <div
                    id="avatar-iniciais-registo"
                    class="circulo-avatar-registo"
                    role="img"
                    aria-label="Avatar com as iniciais {{ $iniciaisConvidado }}"
                >
                    <span
                        id="iniciais-avatar-registo"
                        aria-hidden="true"
                    >
                        {{ $iniciaisConvidado }}
                    </span>
                </div>

                <img
                    id="previsualizacao-fotografia-perfil"
                    class="previsualizacao-fotografia-perfil"
                    alt="Pré-visualização da fotografia de perfil selecionada"
                    hidden
                >
            </div>

            <div class="flex-grow-1">
                <div class="grupo-campo-formulario">
                    <div class="form-floating">
                        <input
                            id="nome"
                            class="form-control @error('nome') is-invalid @enderror"
                            type="text"
                            name="nome"
                            placeholder="Nome"
                            value="{{ old('nome', $convite->nome_convidado) }}"
                            minlength="3"
                            maxlength="255"
                            autocomplete="name"
                            aria-describedby="erro-nome"
                            required
                            autofocus
                            @error('nome')
                                aria-invalid="true"
                            @enderror
                        >

                        <label for="nome">
                            Nome

                            <span
                                class="text-danger"
                                aria-hidden="true"
                            >
                                *
                            </span>
                        </label>
                    </div>

                    <div
                        id="erro-nome"
                        class="invalid-feedback @error('nome') d-block @enderror"
                        aria-live="polite"
                    >
                        @error('nome')
                            {{ $message }}
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="grupo-campo-formulario mb-3">
            <label
                class="form-label"
                for="fotografia-perfil"
            >
                Fotografia de perfil

                <span class="fw-normal text-muted">
                    (opcional)
                </span>
            </label>

            <input
                id="fotografia-perfil"
                class="form-control @error('fotografia') is-invalid @enderror"
                type="file"
                name="fotografia"
                accept="image/jpeg,image/png,image/webp"
                aria-describedby="ajuda-fotografia-perfil erro-fotografia"
                @error('fotografia')
                    aria-invalid="true"
                @enderror
            >

            <div
                id="ajuda-fotografia-perfil"
                class="form-text"
            >
                São aceites ficheiros JPEG, PNG ou WebP até 10 MiB.
            </div>

            <div
                id="erro-fotografia"
                class="invalid-feedback @error('fotografia') d-block @enderror"
                aria-live="polite"
            >
                @error('fotografia')
                    {{ $message }}
                @enderror
            </div>
        </div>

        <div class="grupo-campo-formulario mb-3">
            <div class="form-floating">
                <input
                    id="email"
                    class="form-control @error('email') is-invalid @enderror"
                    type="email"
                    name="email"
                    placeholder="E-mail"
                    value="{{ old('email', $emailConvite) }}"
                    maxlength="255"
                    autocomplete="email"
                    autocapitalize="none"
                    spellcheck="false"
                    inputmode="email"
                    aria-describedby="{{
                        $emailBloqueado
                            ? 'ajuda-email erro-email'
                            : 'erro-email'
                    }}"
                    required
                    @readonly($emailBloqueado)
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

            @if ($emailBloqueado)
                <div
                    id="ajuda-email"
                    class="form-text"
                >
                    Este convite está associado a este endereço de e-mail.
                </div>
            @endif

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
                        minlength="{{ $comprimentoMinimoPalavraPasse }}"
                        maxlength="{{ $comprimentoMaximoPalavraPasse }}"
                        autocomplete="new-password"
                        aria-describedby="ajuda-palavra-passe erro-palavra-passe"
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
                id="ajuda-palavra-passe"
                class="form-text"
            >
                Utiliza pelo menos 12 caracteres, incluindo uma letra
                maiúscula, uma letra minúscula, um número e um símbolo.
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

        <div class="grupo-campo-formulario mb-3">
            <div class="input-group">
                <div class="form-floating">
                    <input
                        id="confirmacao-palavra-passe"
                        class="form-control @error('confirmacao_palavra_passe') is-invalid @enderror"
                        type="password"
                        name="confirmacao_palavra_passe"
                        placeholder="Confirmar palavra-passe"
                        minlength="{{ $comprimentoMinimoPalavraPasse }}"
                        maxlength="{{ $comprimentoMaximoPalavraPasse }}"
                        autocomplete="new-password"
                        aria-describedby="erro-confirmacao-palavra-passe"
                        required
                        @error('confirmacao_palavra_passe')
                            aria-invalid="true"
                        @enderror
                    >

                    <label for="confirmacao-palavra-passe">
                        Confirmar palavra-passe

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
                    data-alvo-palavra-passe="confirmacao-palavra-passe"
                    data-descricao-palavra-passe="a confirmação da palavra-passe"
                    aria-label="Mostrar a confirmação da palavra-passe"
                    aria-controls="confirmacao-palavra-passe"
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
                id="erro-confirmacao-palavra-passe"
                class="invalid-feedback @error('confirmacao_palavra_passe') d-block @enderror"
                aria-live="polite"
            >
                @error('confirmacao_palavra_passe')
                    {{ $message }}
                @enderror
            </div>
        </div>

        <hr class="my-4">

        <fieldset
            class="mb-3"
            aria-describedby="descricao-permissoes-email erro-permissoes-email"
        >
            <legend class="h5">
                Permissões de e-mail
            </legend>

            <p
                id="descricao-permissoes-email"
                class="small text-muted"
            >
                Escolhe as notificações que queres receber por e-mail.
            </p>

            @if ($permissaoTodas !== null)
                <div class="form-check">
                    <input
                        id="permissao-email-todas"
                        class="form-check-input"
                        type="checkbox"
                        name="permissoes_email[]"
                        value="{{ $permissaoTodas->getKey() }}"
                        data-permissao-email-todas
                        @checked(
                            $permissoesSelecionadas->contains(
                                (string) $permissaoTodas->getKey(),
                            )
                        )
                    >

                    <label
                        class="form-check-label"
                        for="permissao-email-todas"
                    >
                        {{ $permissaoTodas->nome }}
                    </label>

                    @if (filled($permissaoTodas->descricao))
                        <button
                            class="btn btn-link p-0 ms-1 border-0 align-baseline"
                            type="button"
                            data-bs-toggle="tooltip"
                            data-bs-placement="right"
                            data-bs-title="{{ $permissaoTodas->descricao }}"
                            aria-label="Informação sobre {{ $permissaoTodas->nome }}"
                        >
                            <i
                                class="bi bi-info-circle-fill"
                                aria-hidden="true"
                            ></i>
                        </button>
                    @endif
                </div>
            @endif

            @foreach ($outrasPermissoes as $permissao)
                <div class="form-check mt-2">
                    <input
                        id="permissao-email-{{ $permissao->getKey() }}"
                        class="form-check-input"
                        type="checkbox"
                        name="permissoes_email[]"
                        value="{{ $permissao->getKey() }}"
                        data-permissao-email-individual
                        @checked(
                            $permissoesSelecionadas->contains(
                                (string) $permissao->getKey(),
                            )
                        )
                    >

                    <label
                        class="form-check-label"
                        for="permissao-email-{{ $permissao->getKey() }}"
                    >
                        {{ $permissao->nome }}
                    </label>

                    @if (filled($permissao->descricao))
                        <button
                            class="btn btn-link p-0 ms-1 border-0 align-baseline"
                            type="button"
                            data-bs-toggle="tooltip"
                            data-bs-placement="right"
                            data-bs-title="{{ $permissao->descricao }}"
                            aria-label="Informação sobre {{ $permissao->nome }}"
                        >
                            <i
                                class="bi bi-info-circle-fill"
                                aria-hidden="true"
                            ></i>
                        </button>
                    @endif
                </div>
            @endforeach

            <div
                id="erro-permissoes-email"
                aria-live="polite"
            >
                @error('permissoes_email')
                    <div class="invalid-feedback d-block">
                        {{ $message }}
                    </div>
                @enderror

                @error('permissoes_email.*')
                    <div class="invalid-feedback d-block">
                        {{ $message }}
                    </div>
                @enderror
            </div>
        </fieldset>

        <button
            class="w-100 btn btn-lg btn-primary mt-4"
            type="submit"
        >
            Finalizar registo
        </button>
    </form>

    @push('scripts-pagina')
        @vite(
            'resources/js/paginas/aceitarConvite.js'
        )
    @endpush
</x-layout-convidado>
