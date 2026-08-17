{{--
    Apresenta o formulário de seleção das notificações recebidas por e-mail.

    As permissões são ordenadas e os valores selecionados são preparados
    pelo controlador responsável pelo perfil.

    Os erros de validação são obtidos através do saco de erros
    "permissoesEmail".

    @since 1.0.0
--}}

<section
    class="card shadow-sm mb-4"
    aria-labelledby="titulo-permissoes-email"
>
    <div class="card-header">
        <h2
            id="titulo-permissoes-email"
            class="h5 mb-0"
        >
            Permissões de e-mail
        </h2>

        <p class="card-subtitle text-muted small mt-1 mb-0">
            Escolhe as notificações que pretendes receber por e-mail.
        </p>
    </div>

    <div class="card-body">
        <form
            id="formulario-permissoes-email"
            method="POST"
            action="{{ route('perfil.permissoes-email.atualizar') }}"
            novalidate
        >
            @csrf
            @method('PATCH')

            @if ($permissoesEmailFormulario === [])
                <div
                    class="alert alert-info mb-0"
                    role="status"
                >
                    Não existem permissões de e-mail disponíveis.
                </div>
            @else
                <fieldset
                    aria-describedby="erro-permissoes-email"
                >
                    <legend class="visually-hidden">
                        Notificações por e-mail
                    </legend>

                    @foreach (
                        $permissoesEmailFormulario
                        as $permissaoEmail
                    )
                        <div
                            @class([
                                'form-check',
                                'mt-2' => ! $loop->first,
                            ])
                            data-item-permissao-email
                        >
                            <input
                                id="permissao-email-{{
                                    $permissaoEmail['identificador']
                                }}"
                                class="form-check-input"
                                type="checkbox"
                                name="permissoes_email[]"
                                value="{{
                                    $permissaoEmail['identificador']
                                }}"
                                data-permissao-todas="{{
                                    $permissaoEmail['ePermissaoTodas']
                                        ? 'true'
                                        : 'false'
                                }}"
                                @checked(
                                    $permissaoEmail['selecionada']
                                )
                            >

                            <label
                                class="form-check-label"
                                for="permissao-email-{{
                                    $permissaoEmail['identificador']
                                }}"
                            >
                                {{ $permissaoEmail['nome'] }}
                            </label>

                            @if ($permissaoEmail['descricao'] !== null)
                                <button
                                    class="btn btn-link btn-sm p-0 ms-1 tooltip-personalizado"
                                    type="button"
                                    data-bs-toggle="tooltip"
                                    data-bs-placement="right"
                                    data-bs-title="{{
                                        $permissaoEmail['descricao']
                                    }}"
                                    aria-label="Informação sobre {{
                                        $permissaoEmail['nome']
                                    }}"
                                >
                                    <i
                                        class="bi bi-info-circle-fill"
                                        aria-hidden="true"
                                    ></i>
                                </button>
                            @endif
                        </div>
                    @endforeach
                </fieldset>

                <div
                    id="erro-permissoes-email"
                    @class([
                        'invalid-feedback',
                        'd-block' =>
                            $errors->has(
                                'permissoes_email',
                                'permissoesEmail',
                            )
                            || $errors->has(
                                'permissoes_email.*',
                                'permissoesEmail',
                            ),
                        'mt-2',
                    ])
                    aria-live="polite"
                >
                    @error(
                        'permissoes_email',
                        'permissoesEmail'
                    )
                        {{ $message }}
                    @else
                        @error(
                            'permissoes_email.*',
                            'permissoesEmail'
                        )
                            {{ $message }}
                        @enderror
                    @enderror
                </div>

                <div class="d-flex justify-content-end mt-4">
                    <button
                        class="btn btn-primary"
                        type="submit"
                    >
                        Guardar permissões
                    </button>
                </div>
            @endif
        </form>
    </div>
</section>
