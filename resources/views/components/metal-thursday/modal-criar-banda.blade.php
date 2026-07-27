{{--
    Apresenta o formulário modal para criação de uma banda sem abandonar
    o formulário principal da MetalThursday.

    Os valores antigos e as opções disponíveis são preparados pela classe
    App\View\Components\MetalThursday\ModalCriarBanda.

    @since 1.0.0
    @version 3.0.0
--}}

@can(
    'create',
    App\Models\Musica\Banda::class
)
    <div
        id="modal-criar-banda"
        class="modal fade"
        tabindex="-1"
        aria-labelledby="titulo-modal-criar-banda"
        aria-hidden="true"
    >
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-dark text-white">
                <form
                    id="formulario-criar-banda"
                    method="POST"
                    action="{{ route('bandas.guardar') }}"
                    data-ajax-form
                    data-formulario-criar-banda
                    data-endereco="{{ route('bandas.guardar') }}"
                    data-mensagem-sucesso="Banda criada com sucesso."
                    data-mensagem-erro="Não foi possível criar a banda."
                    novalidate
                >
                    @csrf

                    <div class="modal-header border-secondary">
                        <h2
                            id="titulo-modal-criar-banda"
                            class="h5 modal-title"
                        >
                            Criar nova banda
                        </h2>

                        <button
                            class="btn-close btn-close-white"
                            type="button"
                            data-bs-dismiss="modal"
                            aria-label="Fechar"
                        ></button>
                    </div>

                    <div class="modal-body">
                        <div
                            class="grupo-campo-formulario mb-3"
                            data-grupo-campo
                        >
                            <label
                                class="form-label"
                                for="nome-nova-banda"
                            >
                                Nome

                                <span
                                    class="text-danger"
                                    aria-hidden="true"
                                >
                                    *
                                </span>
                            </label>

                            <input
                                id="nome-nova-banda"
                                class="form-control @error('nome') is-invalid @enderror"
                                type="text"
                                name="nome"
                                value="{{ $nomeBanda }}"
                                placeholder="Nome da banda"
                                maxlength="255"
                                autocomplete="organization"
                                aria-describedby="erro-nome-nova-banda"
                                required
                                @error('nome')
                                    aria-invalid="true"
                                @enderror
                            >

                            <div
                                id="erro-nome-nova-banda"
                                class="invalid-feedback @error('nome') d-block @enderror"
                                aria-live="polite"
                                data-erro-campo="nome"
                            >
                                @error('nome')
                                    {{ $message }}
                                @enderror
                            </div>
                        </div>

                        <div
                            class="grupo-campo-formulario mb-3"
                            data-grupo-campo
                        >
                            <label
                                class="form-label"
                                for="pais-nova-banda"
                            >
                                País

                                <span
                                    class="text-danger"
                                    aria-hidden="true"
                                >
                                    *
                                </span>
                            </label>

                            <select
                                id="pais-nova-banda"
                                class="form-select tom-select-unico @error('pais_id') is-invalid @enderror"
                                name="pais_id"
                                placeholder="Seleciona um país"
                                aria-describedby="erro-pais-nova-banda"
                                required
                                @error('pais_id')
                                    aria-invalid="true"
                                @enderror
                            >
                                <option value="">
                                    Seleciona um país
                                </option>

                                @foreach ($paises as $pais)
                                    <option
                                        value="{{ $pais->getKey() }}"
                                        @selected(
                                            $identificadorPaisSelecionado
                                            === (string) $pais->getKey()
                                        )
                                    >
                                        {{ $pais->nome }}
                                    </option>
                                @endforeach
                            </select>

                            <div
                                id="erro-pais-nova-banda"
                                class="invalid-feedback @error('pais_id') d-block @enderror"
                                aria-live="polite"
                                data-erro-campo="pais_id"
                            >
                                @error('pais_id')
                                    {{ $message }}
                                @enderror
                            </div>
                        </div>

                        <div
                            class="grupo-campo-formulario mb-3"
                            data-grupo-campo
                        >
                            <label
                                class="form-label"
                                for="generos-nova-banda"
                            >
                                Géneros

                                <span
                                    class="text-danger"
                                    aria-hidden="true"
                                >
                                    *
                                </span>
                            </label>

                            <div class="input-group has-validation">
                                <select
                                    id="generos-nova-banda"
                                    class="form-select tom-select-multiplo {{
                                        (
                                            $errors->has('generos')
                                            || $errors->has('generos.*')
                                        )
                                            ? 'is-invalid'
                                            : ''
                                    }}"
                                    name="generos[]"
                                    placeholder="Seleciona um ou mais géneros"
                                    aria-describedby="erro-generos-nova-banda"
                                    multiple
                                    required
                                    @if (
                                        $errors->has('generos')
                                        || $errors->has('generos.*')
                                    )
                                        aria-invalid="true"
                                    @endif
                                >
                                    @foreach ($generos as $genero)
                                        <option
                                            value="{{ $genero->getKey() }}"
                                            @selected(
                                                in_array(
                                                    (string) $genero->getKey(),
                                                    $identificadoresGenerosSelecionados,
                                                    true,
                                                )
                                            )
                                        >
                                            {{ $genero->nome }}
                                        </option>
                                    @endforeach
                                </select>

                                @can(
                                    'create',
                                    App\Models\Musica\Genero::class
                                )
                                    <button
                                        class="btn btn-secondary"
                                        type="button"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modal-criar-genero"
                                        aria-label="Criar novo género"
                                        title="Criar novo género"
                                    >
                                        <i
                                            class="bi bi-plus-lg"
                                            aria-hidden="true"
                                        ></i>
                                    </button>
                                @endcan
                            </div>

                            <div
                                id="erro-generos-nova-banda"
                                class="invalid-feedback {{
                                    (
                                        $errors->has('generos')
                                        || $errors->has('generos.*')
                                    )
                                        ? 'd-block'
                                        : ''
                                }}"
                                aria-live="polite"
                                data-erro-campo="generos"
                            >
                                @error('generos')
                                    {{ $message }}
                                @else
                                    @error('generos.*')
                                        {{ $message }}
                                    @enderror
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer border-secondary">
                        <button
                            class="btn btn-secondary"
                            type="button"
                            data-bs-dismiss="modal"
                        >
                            Cancelar
                        </button>

                        <button
                            class="btn btn-primary"
                            type="submit"
                        >
                            Criar banda
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endcan
