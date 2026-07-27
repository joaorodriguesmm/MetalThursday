{{--
    Apresenta o formulário de criação ou edição de um género musical.

    O endereço, os valores selecionados e o texto do botão são preparados
    pelo controlador responsável pelos géneros.

    @since 1.0.0
    @version 3.0.0
--}}

<form
    id="formulario-genero"
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
            for="nome-genero"
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
            id="nome-genero"
            class="form-control @error('nome') is-invalid @enderror"
            type="text"
            name="nome"
            value="{{ $nomeGenero }}"
            placeholder="Nome do género"
            maxlength="255"
            autocomplete="off"
            aria-describedby="erro-nome-genero"
            required
            autofocus
            @error('nome')
                aria-invalid="true"
            @enderror
        >

        <div
            id="erro-nome-genero"
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
            for="generos-pai"
        >
            Géneros pais

            <span class="fw-normal text-muted">
                (opcional)
            </span>
        </label>

        <select
            id="generos-pai"
            @class([
                'form-select',
                'tom-select-multiplo',
                'is-invalid' =>
                    $errors->has('generos_pai')
                    || $errors->has('generos_pai.*'),
            ])
            name="generos_pai[]"
            placeholder="Seleciona um ou mais géneros pais"
            aria-describedby="ajuda-generos-pai erro-generos-pai"
            multiple
            @if (
                $errors->has('generos_pai')
                || $errors->has('generos_pai.*')
            )
                aria-invalid="true"
            @endif
        >
            @foreach ($generosDisponiveis as $generoDisponivel)
                <option
                    value="{{ $generoDisponivel->getKey() }}"
                    @selected(
                        in_array(
                            (string) $generoDisponivel->getKey(),
                            $identificadoresGenerosPaisSelecionados,
                            true,
                        )
                    )
                >
                    {{ $generoDisponivel->nome }}
                </option>
            @endforeach
        </select>

        <div
            id="ajuda-generos-pai"
            class="form-text"
        >
            Seleciona os géneros dos quais este género deriva diretamente.
        </div>

        <div
            id="erro-generos-pai"
            @class([
                'invalid-feedback',
                'd-block' =>
                    $errors->has('generos_pai')
                    || $errors->has('generos_pai.*'),
            ])
            aria-live="polite"
        >
            @error('generos_pai')
                {{ $message }}
            @else
                @error('generos_pai.*')
                    {{ $message }}
                @enderror
            @enderror
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2">
        <a
            class="btn btn-secondary"
            href="{{ route('generos.indice') }}"
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
