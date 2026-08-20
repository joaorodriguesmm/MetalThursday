{{--
    Apresenta o formulário de criação ou edição de uma banda.

    O endereço, os valores selecionados e o texto do botão são preparados
    pelo controlador responsável pelas bandas.

    @since 1.0.0
--}}

<form
    id="formulario-banda"
    method="POST"
    action="{{ $enderecoFormulario }}"
    autocomplete="off"
    novalidate
>
    @csrf

    @if ($emEdicao)
        @method('PATCH')
    @endif

    <div class="grupo-campo-formulario mb-3">
        <label
            class="form-label"
            for="nome-banda"
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
            id="nome-banda"
            class="form-control @error('nome') is-invalid @enderror"
            type="text"
            name="nome"
            value="{{ $nomeBanda }}"
            placeholder="Nome da banda"
            maxlength="{{ App\Models\Musica\Banda::COMPRIMENTO_MAXIMO_NOME }}"
            autocomplete="off"
            aria-describedby="erro-nome-banda"
            required
            autofocus
            @error('nome')
                aria-invalid="true"
            @enderror
        >

        <div
            id="erro-nome-banda"
            class="invalid-feedback @error('nome') d-block @enderror"
            aria-live="polite"
        >
            @error('nome')
                {{ $message }}
            @enderror
        </div>
    </div>

    <div class="grupo-campo-formulario mb-3">
        <label
            class="form-label"
            for="origem-geografica-banda"
        >
            Origem geográfica

            <span
                class="text-danger"
                aria-hidden="true"
            >
                *
            </span>
        </label>

        <select
            id="origem-geografica-banda"
            class="form-select tom-select-unico @error('origem_geografica_id') is-invalid @enderror"
            name="origem_geografica_id"
            placeholder="Seleciona uma origem geográfica"
            aria-describedby="erro-origem-geografica-banda"
            autocomplete="off"
            required
            @error('origem_geografica_id')
                aria-invalid="true"
            @enderror
        >
            <option value="">
                Seleciona uma origem geográfica
            </option>

            @foreach (
                $origensGeograficas
                as $origemGeografica
            )
                <option
                    value="{{ $origemGeografica->getKey() }}"
                    @selected(
                        $identificadorOrigemGeograficaSelecionada
                        === (string) $origemGeografica->getKey()
                    )
                >
                    {{ $origemGeografica->nome }}
                </option>
            @endforeach
        </select>

        <div
            id="erro-origem-geografica-banda"
            class="invalid-feedback @error('origem_geografica_id') d-block @enderror"
            aria-live="polite"
        >
            @error('origem_geografica_id')
                {{ $message }}
            @enderror
        </div>
    </div>

    <div class="grupo-campo-formulario mb-3">
        <label
            class="form-label"
            for="generos-banda"
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
                id="generos-banda"
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
                aria-describedby="erro-generos-banda"
                autocomplete="off"
                data-ordenar-alfabeticamente
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
            id="erro-generos-banda"
            class="invalid-feedback {{
                (
                    $errors->has('generos')
                    || $errors->has('generos.*')
                )
                    ? 'd-block'
                    : ''
            }}"
            aria-live="polite"
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

    <div class="d-flex justify-content-end gap-2">
        <a
            class="btn btn-secondary"
            href="{{ route('bandas.indice') }}"
        >
            Cancelar
        </a>

        <button
            class="btn btn-primary"
            type="submit"
        >
            {{ $textoBotaoSubmissao }}
        </button>
    </div>
</form>
