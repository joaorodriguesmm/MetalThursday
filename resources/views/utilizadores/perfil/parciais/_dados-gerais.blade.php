{{--
    Apresenta o formulário de atualização dos dados gerais do perfil.

    Permite alterar o nome, o endereço de e-mail e a fotografia do
    utilizador autenticado.

    Os erros de validação são obtidos através do saco de erros "perfil".

    @since 1.0.0
--}}

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

        <p class="card-subtitle text-muted small mt-1 mb-0">
            Atualiza o teu nome, endereço de e-mail e fotografia.
        </p>
    </div>

    <div class="card-body">
        <form
            id="formulario-atualizar-perfil"
            method="POST"
            action="{{ route('perfil.atualizar') }}"
            enctype="multipart/form-data"
            novalidate
        >
            @csrf
            @method('PATCH')

            <div
                class="d-flex flex-column flex-md-row align-items-md-center gap-4 mb-4"
            >
                <div class="flex-shrink-0">
                    <div
                        id="circulo-avatar"
                        @class([
                            'circulo-avatar',
                            'd-none' =>
                                $utilizador->fotografia !== null,
                        ])
                        aria-hidden="{{
                            $utilizador->fotografia !== null
                                ? 'true'
                                : 'false'
                        }}"
                    >
                        <span id="iniciais-avatar">
                            {{ $utilizador->iniciais }}
                        </span>
                    </div>

                    <img
                        id="previsualizacao-fotografia"
                        @class([
                            'previsualizacao-avatar',
                            'd-none' =>
                                $utilizador->fotografia === null,
                        ])
                        @if ($utilizador->url_fotografia !== null)
                            src="{{ $utilizador->url_fotografia }}"
                        @endif
                        alt="Fotografia de {{ $utilizador->nome }}"
                        aria-hidden="{{
                            $utilizador->fotografia === null
                                ? 'true'
                                : 'false'
                        }}"
                    >
                </div>

                <div class="flex-grow-1 w-100">
                    <div class="grupo-campo-formulario mb-3">
                        <div class="form-floating">
                            <input
                                id="nome"
                                class="form-control @error('nome', 'perfil') is-invalid @enderror"
                                type="text"
                                name="nome"
                                value="{{
                                    old(
                                        'nome',
                                        $utilizador->nome,
                                    )
                                }}"
                                placeholder="Nome"
                                autocomplete="name"
                                minlength="{{ App\ObjetosValor\Utilizadores\NomeUtilizador::COMPRIMENTO_MINIMO }}"
                                maxlength="{{ App\ObjetosValor\Utilizadores\NomeUtilizador::COMPRIMENTO_MAXIMO }}"
                                aria-describedby="erro-nome"
                                required
                                @error('nome', 'perfil')
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
                            class="invalid-feedback @error('nome', 'perfil') d-block @enderror"
                            aria-live="polite"
                        >
                            @error('nome', 'perfil')
                                {{ $message }}
                            @enderror
                        </div>
                    </div>

                    <div class="grupo-campo-formulario">
                        <label
                            class="form-label small text-muted"
                            for="fotografia"
                        >
                            Fotografia

                            <span class="fw-normal">
                                (opcional, máximo de 10 MiB)
                            </span>
                        </label>

                        <div class="contentor-campo-ficheiro">
                            <input
                                id="fotografia"
                                class="campo-ficheiro @error('fotografia', 'perfil') is-invalid @enderror"
                                type="file"
                                name="fotografia"
                                accept="image/jpeg,image/png,image/webp"
                                aria-describedby="texto-fotografia erro-fotografia"
                                @error('fotografia', 'perfil')
                                    aria-invalid="true"
                                @enderror
                            >

                            <label
                                class="etiqueta-campo-ficheiro"
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
                            aria-live="polite"
                            @unless ($errors->perfil->has('fotografia'))
                                hidden
                            @endunless
                        >
                            @error('fotografia', 'perfil')
                                {{ $message }}
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="grupo-campo-formulario mb-3">
                <div class="form-floating">
                    <input
                        id="email"
                        class="form-control @error('email', 'perfil') is-invalid @enderror"
                        type="email"
                        name="email"
                        value="{{
                            old(
                                'email',
                                $utilizador->email,
                            )
                        }}"
                        placeholder="Endereço de e-mail"
                        autocomplete="email"
                        maxlength="{{ App\ObjetosValor\Utilizadores\EnderecoEmail::COMPRIMENTO_MAXIMO }}"
                        aria-describedby="ajuda-email erro-email"
                        required
                        @error('email', 'perfil')
                            aria-invalid="true"
                        @enderror
                    >

                    <label for="email">
                        Endereço de e-mail

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
                    class="invalid-feedback @error('email', 'perfil') d-block @enderror"
                    aria-live="polite"
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

            @if (! $utilizador->hasVerifiedEmail())
                <div
                    class="alert alert-warning py-2"
                    role="status"
                >
                    O endereço de e-mail atual ainda não foi verificado.
                </div>
            @endif

            <div class="d-flex justify-content-end">
                <button
                    class="btn btn-primary"
                    type="submit"
                >
                    Guardar alterações
                </button>
            </div>
        </form>
    </div>
</section>
