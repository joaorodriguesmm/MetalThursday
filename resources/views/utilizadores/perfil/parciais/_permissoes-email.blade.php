@php
    $identificadoresSelecionados = old(
        'permissoes_email',
        $identificadoresPermissoesEmail
    );

    if (!is_array($identificadoresSelecionados)) {
        $identificadoresSelecionados = [];
    }

    $identificadoresSelecionados = array_map(
        static fn (mixed $identificador): int =>
            (int) $identificador,
        $identificadoresSelecionados
    );

    $permissoesOrdenadas = $permissoesEmail
        ->sortBy([
            static fn ($permissao): int =>
                $permissao->slug === 'all' ? 0 : 1,

            static fn ($permissao): string =>
                mb_strtolower($permissao->name),
        ])
        ->values();
@endphp

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

        <p class="card-subtitle text-muted small mt-1">
            Escolhe as notificações que pretendes receber por e-mail.
        </p>
    </div>

    <div class="card-body">
        <form
            id="formulario-permissoes-email"
            method="post"
            action="{{ route('perfil.permissoes-email.atualizar') }}"
            novalidate
        >
            @csrf
            @method('patch')

            @if ($permissoesOrdenadas->isEmpty())
                <div
                    class="alert alert-info mb-0"
                    role="status"
                >
                    Não existem permissões de e-mail disponíveis.
                </div>
            @else
                <fieldset>
                    <legend class="visually-hidden">
                        Notificações por e-mail
                    </legend>

                    @foreach ($permissoesOrdenadas as $permissao)
                        @php
                            $identificador = (int) $permissao->id;
                            $ePermissaoTodas = $permissao->slug === 'all';
                        @endphp

                        <div
                            class="form-check {{ !$loop->first ? 'mt-2' : '' }}"
                            data-item-permissao-email
                        >
                            <input
                                type="checkbox"
                                id="permissao-email-{{ $identificador }}"
                                name="permissoes_email[]"
                                value="{{ $identificador }}"
                                class="form-check-input"
                                data-permissao-todas="{{ $ePermissaoTodas ? 'true' : 'false' }}"
                                @checked(
                                    in_array(
                                        $identificador,
                                        $identificadoresSelecionados,
                                        true
                                    )
                                )
                            >

                            <label
                                class="form-check-label"
                                for="permissao-email-{{ $identificador }}"
                            >
                                {{ $permissao->name }}
                            </label>

                            @if (
                                is_string($permissao->description)
                                && trim($permissao->description) !== ''
                            )
                                <button
                                    type="button"
                                    class="btn btn-link btn-sm p-0 ms-1 custom-tooltip"
                                    data-bs-toggle="tooltip"
                                    data-bs-placement="right"
                                    data-bs-title="{{ $permissao->description }}"
                                    aria-label="Informação sobre {{ $permissao->name }}"
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
            @endif

            @error('permissoes_email', 'permissoesEmail')
                <div class="invalid-feedback d-block mt-2">
                    {{ $message }}
                </div>
            @enderror

            @error('permissoes_email.*', 'permissoesEmail')
                <div class="invalid-feedback d-block mt-2">
                    {{ $message }}
                </div>
            @enderror

            @if ($permissoesOrdenadas->isNotEmpty())
                <div class="d-flex justify-content-end mt-4">
                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        Guardar permissões
                    </button>
                </div>
            @endif
        </form>
    </div>
</section>
