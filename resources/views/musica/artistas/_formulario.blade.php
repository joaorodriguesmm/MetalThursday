{{--
    Apresenta o formulário de criação ou edição de um artista.

    O endereço, os valores selecionados e o texto do botão são preparados
    pelo controlador responsável pelos artistas.

    @since 1.0.0
--}}

<form
    id="formulario-artista"
    method="POST"
    action="{{ $enderecoFormulario }}"
    autocomplete="off"
    novalidate
>
    @csrf

    @if ($emEdicao)
        @method('PATCH')
    @endif

    @if (
        ! $emEdicao
        && ($exigeConfirmacaoNomeRepetido ?? false)
    )
        <input
            type="hidden"
            name="confirmar_nome_repetido"
            value="1"
        >
    @endif

    <div class="grupo-campo-formulario mb-3">
        <label
            class="form-label"
            for="nome-artista"
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
            id="nome-artista"
            class="form-control @error('nome') is-invalid @enderror"
            type="text"
            name="nome"
            value="{{ $nomeArtista }}"
            placeholder="Nome do artista"
            maxlength="{{ App\Models\Musica\Artista::COMPRIMENTO_MAXIMO_NOME }}"
            autocomplete="off"
            aria-describedby="erro-nome-artista"
            required
            autofocus
            @error('nome')
                aria-invalid="true"
            @enderror
        >

        <div
            id="erro-nome-artista"
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
            for="origem-geografica-artista"
        >
            Origem geográfica

            <span class="fw-normal text-muted">
                (opcional)
            </span>
        </label>

        <select
            id="origem-geografica-artista"
            class="form-select tom-select-unico @error('origem_geografica_id') is-invalid @enderror"
            name="origem_geografica_id"
            placeholder="Seleciona uma origem geográfica"
            aria-describedby="erro-origem-geografica-artista"
            autocomplete="off"
            data-ordenar-alfabeticamente
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
            id="erro-origem-geografica-artista"
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
            for="generos-artista"
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
                id="generos-artista"
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
                aria-describedby="erro-generos-artista"
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
            id="erro-generos-artista"
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
            href="{{ route('artistas.indice') }}"
        >
            Cancelar
        </a>

        <button
            class="btn btn-primary"
            type="submit"
        >
            @if (
                ! $emEdicao
                && ($exigeConfirmacaoNomeRepetido ?? false)
            )
                Criar artista mesmo assim
            @else
                {{ $textoBotaoSubmissao }}
            @endif
        </button>
    </div>
</form>
