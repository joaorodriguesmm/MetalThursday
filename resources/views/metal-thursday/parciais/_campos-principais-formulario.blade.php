{{--
    Apresenta os campos principais do formulário de criação ou edição
    de uma MetalThursday.

    A variável $metalThursday contém o modelo editado ou nulo durante
    a criação. As coleções de edições e utilizadores são preparadas pelo
    App\Http\Controllers\MetalThursday\ControladorMetalThursday.

    @since 1.0.0
--}}

<div class="row">
    <div class="col-md-4 grupo-campo-formulario mb-3">
        <label
            class="form-label"
            for="edicao-metal-thursday"
        >
            <strong>
                Edição

                <span
                    class="text-danger"
                    aria-hidden="true"
                >
                    *
                </span>
            </strong>

            <span
                class="bi bi-info-circle"
                role="img"
                tabindex="0"
                data-bs-toggle="tooltip"
                data-bs-title="Edição à qual pertence esta MetalThursday."
                aria-label="Edição à qual pertence esta MetalThursday."
            ></span>
        </label>

        <div class="input-group has-validation">
            <select
                id="edicao-metal-thursday"
                class="form-select tom-select-unico @error('edicao_id') is-invalid @enderror"
                name="edicao_id"
                placeholder="Seleciona uma edição ou cria uma nova"
                aria-describedby="erro-edicao-metal-thursday"
                required
                @error('edicao_id')
                    aria-invalid="true"
                @enderror
            >
                <option value="">
                    Seleciona uma edição
                </option>

                @foreach ($edicoes as $edicao)
                    <option
                        value="{{ $edicao->getKey() }}"
                        @selected(
                            (string) old(
                                'edicao_id',
                                $metalThursday?->edicao_id,
                            )
                            === (string) $edicao->getKey()
                        )
                    >
                        {{ $edicao->texto_apresentacao }}
                    </option>
                @endforeach
            </select>

            @can(
                'create',
                App\Models\MetalThursday\Edicao::class
            )
                <button
                    class="btn btn-secondary"
                    type="button"
                    data-bs-toggle="modal"
                    data-bs-target="#modal-criar-edicao"
                    aria-label="Criar nova edição"
                    title="Criar nova edição"
                >
                    <i
                        class="bi bi-plus-lg"
                        aria-hidden="true"
                    ></i>
                </button>
            @endcan
        </div>

        <div
            id="erro-edicao-metal-thursday"
            class="invalid-feedback @error('edicao_id') d-block @enderror"
            aria-live="polite"
        >
            @error('edicao_id')
                {{ $message }}
            @enderror
        </div>
    </div>

    <div class="col-md-4 grupo-campo-formulario mb-3">
        <label
            class="form-label"
            for="data-metal-thursday"
        >
            <strong>
                Data

                <span
                    class="text-danger"
                    aria-hidden="true"
                >
                    *
                </span>
            </strong>

            <span
                class="bi bi-info-circle"
                role="img"
                tabindex="0"
                data-bs-toggle="tooltip"
                data-bs-title="Data de realização da MetalThursday."
                aria-label="Data de realização da MetalThursday."
            ></span>
        </label>

        <input
            id="data-metal-thursday"
            class="form-control @error('data') is-invalid @enderror"
            type="date"
            name="data"
            value="{{
                old(
                    'data',
                    $metalThursday?->data?->format('Y-m-d'),
                )
            }}"
            aria-describedby="erro-data-metal-thursday"
            required
            @error('data')
                aria-invalid="true"
            @enderror
        >

        <div
            id="erro-data-metal-thursday"
            class="invalid-feedback @error('data') d-block @enderror"
            aria-live="polite"
        >
            @error('data')
                {{ $message }}
            @enderror
        </div>
    </div>

    <div class="col-md-4 grupo-campo-formulario mb-3">
        <label
            class="form-label"
            for="nome-metal-thursday"
        >
            Nome

            <span class="fw-normal text-muted">
                (apenas para edições especiais)
            </span>

            <span
                class="bi bi-info-circle"
                role="img"
                tabindex="0"
                data-bs-toggle="tooltip"
                data-bs-title="Preenche apenas se esta for uma MetalThursday especial, como um especial de Natal."
                aria-label="Preenche apenas se esta for uma MetalThursday especial, como um especial de Natal."
            ></span>
        </label>

        <input
            id="nome-metal-thursday"
            class="form-control @error('nome') is-invalid @enderror"
            type="text"
            name="nome"
            value="{{ old('nome', $metalThursday?->nome) }}"
            placeholder="Exemplo: Especial de Natal"
            maxlength="{{ App\Models\MetalThursday\MetalThursday::COMPRIMENTO_MAXIMO_NOME }}"
            aria-describedby="erro-nome-metal-thursday"
            @error('nome')
                aria-invalid="true"
            @enderror
        >

        <div
            id="erro-nome-metal-thursday"
            class="invalid-feedback @error('nome') d-block @enderror"
            aria-live="polite"
        >
            @error('nome')
                {{ $message }}
            @enderror
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6 grupo-campo-formulario mb-3">
        <label
            class="form-label"
            for="autor-metal-thursday"
        >
            <strong>
                Autor

                <span
                    class="text-danger"
                    aria-hidden="true"
                >
                    *
                </span>
            </strong>

            <span
                class="bi bi-info-circle"
                role="img"
                tabindex="0"
                data-bs-toggle="tooltip"
                data-bs-title="Utilizador responsável por esta MetalThursday."
                aria-label="Utilizador responsável por esta MetalThursday."
            ></span>
        </label>

        <select
            id="autor-metal-thursday"
            class="form-select tom-select-unico @error('autor_id') is-invalid @enderror"
            name="autor_id"
            placeholder="Seleciona o autor"
            aria-describedby="erro-autor-metal-thursday"
            required
            @error('autor_id')
                aria-invalid="true"
            @enderror
        >
            <option value="">
                Seleciona o autor
            </option>

            @foreach ($utilizadores as $utilizador)
                <option
                    value="{{ $utilizador->getKey() }}"
                    @selected(
                        (string) old(
                            'autor_id',
                            $metalThursday?->autor_id,
                        )
                        === (string) $utilizador->getKey()
                    )
                >
                    {{ $utilizador->nome }}
                </option>
            @endforeach
        </select>

        <div
            id="erro-autor-metal-thursday"
            class="invalid-feedback @error('autor_id') d-block @enderror"
            aria-live="polite"
        >
            @error('autor_id')
                {{ $message }}
            @enderror
        </div>
    </div>

    <div class="col-md-6 grupo-campo-formulario mb-3">
        <label
            class="form-label"
            for="proximo-nomeado-metal-thursday"
        >
            <strong>
                Próximo nomeado

                <span
                    class="text-danger"
                    aria-hidden="true"
                >
                    *
                </span>
            </strong>

            <span
                class="bi bi-info-circle"
                role="img"
                tabindex="0"
                data-bs-toggle="tooltip"
                data-bs-title="Utilizador nomeado para preparar a próxima MetalThursday."
                aria-label="Utilizador nomeado para preparar a próxima MetalThursday."
            ></span>
        </label>

        <div class="input-group has-validation">
            <select
                id="proximo-nomeado-metal-thursday"
                class="form-select tom-select-unico @error('proximo_nomeado_id') is-invalid @enderror"
                name="proximo_nomeado_id"
                placeholder="Seleciona o próximo nomeado"
                aria-describedby="erro-proximo-nomeado-metal-thursday"
                required
                @error('proximo_nomeado_id')
                    aria-invalid="true"
                @enderror
            >
                <option value="">
                    Seleciona o próximo nomeado
                </option>

                @foreach ($utilizadores as $utilizador)
                    <option
                        value="{{ $utilizador->getKey() }}"
                        @selected(
                            (string) old(
                                'proximo_nomeado_id',
                                $metalThursday?->proximo_nomeado_id,
                            )
                            === (string) $utilizador->getKey()
                        )
                    >
                        {{ $utilizador->nome }}
                    </option>
                @endforeach
            </select>

            <button
                id="botao-selecionar-nomeado-aleatorio"
                class="btn btn-secondary"
                type="button"
                aria-label="Selecionar um nomeado aleatoriamente"
                title="Selecionar um nomeado aleatoriamente"
            >
                <i
                    class="bi bi-shuffle"
                    aria-hidden="true"
                ></i>
            </button>

            <button
                id="botao-selecionar-nomeado-mais-antigo"
                class="btn btn-secondary"
                type="button"
                aria-label="Selecionar o utilizador que não é nomeado há mais tempo"
                title="Selecionar o utilizador que não é nomeado há mais tempo"
            >
                <i
                    class="bi bi-calendar-x"
                    aria-hidden="true"
                ></i>
            </button>
        </div>

        <div
            id="erro-proximo-nomeado-metal-thursday"
            class="invalid-feedback @error('proximo_nomeado_id') d-block @enderror"
            aria-live="polite"
        >
            @error('proximo_nomeado_id')
                {{ $message }}
            @enderror
        </div>
    </div>
</div>
