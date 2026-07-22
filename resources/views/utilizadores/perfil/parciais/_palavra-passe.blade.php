<section
    class="card shadow-sm"
    aria-labelledby="titulo-palavra-passe"
>
    <div class="card-header">
        <h2
            id="titulo-palavra-passe"
            class="h5 mb-0"
        >
            Alterar palavra-passe
        </h2>

        <p class="card-subtitle text-muted small mt-1">
            Define uma nova palavra-passe para proteger a tua conta.
        </p>
    </div>

    <div class="card-body">
        <form
            id="formulario-palavra-passe"
            method="post"
            action="{{ route('perfil.palavra-passe.atualizar') }}"
            novalidate
        >
            @csrf
            @method('put')

            <div class="form-field-group mb-3">
                <div class="input-group">
                    <div class="form-floating">
                        <input
                            type="password"
                            id="palavra-passe-atual"
                            name="palavra_passe_atual"
                            class="form-control @error('palavra_passe_atual', 'palavraPasse') is-invalid @enderror"
                            placeholder="Palavra-passe atual"
                            autocomplete="current-password"
                            aria-describedby="erro-palavra-passe-atual"
                            @error('palavra_passe_atual', 'palavraPasse')
                                aria-invalid="true"
                            @enderror
                            required
                        >

                        <label for="palavra-passe-atual">
                            Palavra-passe atual
                            <span
                                class="text-danger"
                                aria-hidden="true"
                            >*</span>
                        </label>
                    </div>

                    <button
                        type="button"
                        class="input-group-text password-toggle-icon"
                        data-alvo-palavra-passe="palavra-passe-atual"
                        aria-label="Mostrar palavra-passe atual"
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
                    id="erro-palavra-passe-atual"
                    class="invalid-feedback @error('palavra_passe_atual', 'palavraPasse') d-block @enderror"
                >
                    @error('palavra_passe_atual', 'palavraPasse')
                        {{ $message }}
                    @enderror
                </div>
            </div>

            <div class="form-field-group mb-3">
                <div class="input-group">
                    <div class="form-floating">
                        <input
                            type="password"
                            id="nova-palavra-passe"
                            name="nova_palavra_passe"
                            class="form-control @error('nova_palavra_passe', 'palavraPasse') is-invalid @enderror"
                            placeholder="Nova palavra-passe"
                            autocomplete="new-password"
                            aria-describedby="ajuda-nova-palavra-passe erro-nova-palavra-passe"
                            @error('nova_palavra_passe', 'palavraPasse')
                                aria-invalid="true"
                            @enderror
                            required
                        >

                        <label for="nova-palavra-passe">
                            Nova palavra-passe
                            <span
                                class="text-danger"
                                aria-hidden="true"
                            >*</span>
                        </label>
                    </div>

                    <button
                        type="button"
                        class="input-group-text password-toggle-icon"
                        data-alvo-palavra-passe="nova-palavra-passe"
                        aria-label="Mostrar nova palavra-passe"
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
                    id="erro-nova-palavra-passe"
                    class="invalid-feedback @error('nova_palavra_passe', 'palavraPasse') d-block @enderror"
                >
                    @error('nova_palavra_passe', 'palavraPasse')
                        {{ $message }}
                    @enderror
                </div>

                <div
                    id="ajuda-nova-palavra-passe"
                    class="form-text"
                >
                    Utiliza pelo menos 12 caracteres, incluindo maiúsculas,
                    minúsculas, números e símbolos.
                </div>
            </div>

            <div class="form-field-group mb-3">
                <div class="input-group">
                    <div class="form-floating">
                        <input
                            type="password"
                            id="confirmacao-nova-palavra-passe"
                            name="confirmacao_nova_palavra_passe"
                            class="form-control @error('confirmacao_nova_palavra_passe', 'palavraPasse') is-invalid @enderror"
                            placeholder="Confirmar nova palavra-passe"
                            autocomplete="new-password"
                            aria-describedby="erro-confirmacao-nova-palavra-passe"
                            @error('confirmacao_nova_palavra_passe', 'palavraPasse')
                                aria-invalid="true"
                            @enderror
                            required
                        >

                        <label for="confirmacao-nova-palavra-passe">
                            Confirmar nova palavra-passe
                            <span
                                class="text-danger"
                                aria-hidden="true"
                            >*</span>
                        </label>
                    </div>

                    <button
                        type="button"
                        class="input-group-text password-toggle-icon"
                        data-alvo-palavra-passe="confirmacao-nova-palavra-passe"
                        aria-label="Mostrar confirmação da nova palavra-passe"
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
                    id="erro-confirmacao-nova-palavra-passe"
                    class="invalid-feedback @error('confirmacao_nova_palavra_passe', 'palavraPasse') d-block @enderror"
                >
                    @error('confirmacao_nova_palavra_passe', 'palavraPasse')
                        {{ $message }}
                    @enderror
                </div>
            </div>

            <div class="d-flex justify-content-end">
                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Guardar palavra-passe
                </button>
            </div>
        </form>
    </div>
</section>
