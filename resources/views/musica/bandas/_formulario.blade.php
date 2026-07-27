{{--
    Apresenta o formulário de criação ou edição de uma banda.

    O endereço, os valores selecionados e o texto do botão são preparados
    pelo controlador responsável pelas bandas.

    @since 1.0.0
    @version 3.0.0
--}}

<form
    id="formulario-banda"
    method="POST"
    action="{{ $enderecoFormulario }}"
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
            maxlength="255"
            autocomplete="organization"
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
            for="pais-banda"
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
            id="pais-banda"
            class="form-select tom-select-unico @error('pais_id') is-invalid @enderror"
            name="pais_id"
            placeholder="Seleciona um país"
            aria-describedby="erro-pais-banda"
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
            id="erro-pais-banda"
            class="invalid-feedback @error('pais_id') d-block @enderror"
            aria-live="polite"
        >
            @error('pais_id')
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
