{{--
    Apresenta os campos principais do formulário de criação ou edição
    de uma MetalThursday.

    A variável $metalThursday contém o modelo editado ou nulo durante
    a criação. As coleções de edições, autores e utilizadores elegíveis para nomeação são preparadas pelo
    App\Http\Controllers\MetalThursday\ControladorMetalThursday.

    A edição é determinada automaticamente no servidor a partir da data da
    MetalThursday e, por isso, não é enviada como escolha do utilizador.

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
            </strong>

            <span
                class="bi bi-info-circle"
                role="img"
                tabindex="0"
                data-bs-toggle="tooltip"
                data-bs-title="A edição é determinada automaticamente pela data da MetalThursday."
                aria-label="A edição é determinada automaticamente pela data da MetalThursday."
            ></span>
        </label>

        <div class="input-group">
            <input
                id="edicao-metal-thursday"
                class="form-control"
                type="text"
                value="{{
                    $edicoes
                        ->firstWhere(
                            'id',
                            old(
                                'edicao_id',
                                $metalThursday?->edicao_id,
                            ),
                        )
                        ?->nome
                }}"
                placeholder="Determinada automaticamente pela data"
                aria-describedby="estado-edicao-metal-thursday"
                readonly
            >

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
            id="estado-edicao-metal-thursday"
            class="form-text"
            aria-live="polite"
        >
            A edição é determinada automaticamente pela data da MetalThursday.
        </div>

        <div
            id="dados-edicoes-metal-thursday"
            data-data-referencia="{{ now()->format('Y-m-d') }}"
            hidden
        >
            @foreach ($edicoes as $edicao)
                <span
                    data-edicao-identificador="{{ $edicao->getKey() }}"
                    data-edicao-nome="{{ $edicao->nome }}"
                    data-edicao-inicio="{{ $edicao->data_inicio?->format('Y-m-d') }}"
                    data-edicao-fim="{{ $edicao->data_fim?->format('Y-m-d') }}"
                ></span>
            @endforeach
        </div>
    </div>

    <div class="col-md-4 grupo-campo-formulario mb-3">
        @php
            $dataPersistida =
                $metalThursday?->data?->format('Y-m-d');

            $dataReservaPendente =
                $reservaPendente?->data?->format('Y-m-d');

            $dataFormulario =
                $podeAlterarData
                    ? old(
                        'data',
                        $dataPersistida,
                    )
                    : (
                        $dataPersistida
                        ?? $dataReservaPendente
                        ?? now()->format('Y-m-d')
                    );
        @endphp

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
            value="{{ $dataFormulario }}"
            aria-describedby="erro-data-metal-thursday @if (! $podeAlterarData) ajuda-data-metal-thursday @endif"
            required
            @if (! $podeAlterarData)
                readonly
                aria-readonly="true"
            @endif
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

        @if (! $podeAlterarData)
            <div
                id="ajuda-data-metal-thursday"
                class="form-text"
            >
                @if ($metalThursday instanceof App\Models\MetalThursday\MetalThursday)
                    A data desta MetalThursday não pode ser alterada.
                @elseif ($dataReservaPendente !== null)
                    A data corresponde à tua reserva pendente e não pode ser alterada.
                @else
                    Não tens nenhuma reserva pendente para publicar.
                @endif
            </div>
        @endif
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

        @if ($podeSelecionarAutor)
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

                @foreach ($utilizadoresAutores as $utilizador)
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
        @else
            <input
                id="autor-metal-thursday"
                class="form-control @error('autor_id') is-invalid @enderror"
                type="text"
                value="{{ $autorFormulario?->nome }}"
                aria-describedby="erro-autor-metal-thursday"
                readonly
                @error('autor_id')
                    aria-invalid="true"
                @enderror
            >

            <input
                type="hidden"
                name="autor_id"
                value="{{ $autorFormulario?->getKey() }}"
                aria-describedby="erro-autor-metal-thursday"
            >
        @endif

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
        @if ($metalThursday instanceof App\Models\MetalThursday\MetalThursday)
            @php
                $nomeProximoNomeado =
                    $utilizadoresElegiveisNomeacao
                        ->firstWhere(
                            'id',
                            $metalThursday->proximo_nomeado_id,
                        )
                        ?->nome;
            @endphp

            <label
                class="form-label"
                for="proximo-nomeado-metal-thursday"
            >
                <strong>
                    Próximo nomeado
                </strong>

                <span
                    class="bi bi-info-circle"
                    role="img"
                    tabindex="0"
                    data-bs-toggle="tooltip"
                    data-bs-title="A nomeação efetiva já foi definida pela reserva seguinte e não pode ser alterada nesta edição."
                    aria-label="A nomeação efetiva já foi definida pela reserva seguinte e não pode ser alterada nesta edição."
                ></span>
            </label>

            <input
                id="proximo-nomeado-metal-thursday"
                class="form-control @error('proximo_nomeado_id') is-invalid @enderror"
                type="text"
                value="{{ $nomeProximoNomeado ?? 'Não definido' }}"
                aria-describedby="erro-proximo-nomeado-metal-thursday ajuda-proximo-nomeado-metal-thursday"
                readonly
                aria-readonly="true"
                @error('proximo_nomeado_id')
                    aria-invalid="true"
                @enderror
            >

            <div
                id="erro-proximo-nomeado-metal-thursday"
                class="invalid-feedback @error('proximo_nomeado_id') d-block @enderror"
                aria-live="polite"
            >
                @error('proximo_nomeado_id')
                    {{ $message }}
                @enderror
            </div>

            <div
                id="ajuda-proximo-nomeado-metal-thursday"
                class="form-text"
            >
                A nomeação desta MetalThursday já está definida pela reserva seguinte e não pode ser alterada.
            </div>
        @else
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

                    @foreach ($utilizadoresElegiveisNomeacao as $utilizador)
                        <option
                            value="{{ $utilizador->getKey() }}"
                            @selected(
                                (string) old(
                                    'proximo_nomeado_id',
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
        @endif
    </div>
</div>
