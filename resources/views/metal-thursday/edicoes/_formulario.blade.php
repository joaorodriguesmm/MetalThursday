{{--
    Apresenta o formulário de criação ou edição de uma edição.

    O modo do formulário, os valores iniciais, o endereço de submissão e o
    texto do botão são preparados pelo controlador responsável pela página.

    @since 1.0.0
    @version 4.0.0
--}}

<form
    id="formulario-edicao"
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
            for="nome-edicao"
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
            id="nome-edicao"
            class="form-control @error('nome') is-invalid @enderror"
            type="text"
            name="nome"
            value="{{ old('nome', $nomeEdicao) }}"
            placeholder="Nome da edição"
            maxlength="{{ App\Models\MetalThursday\Edicao::COMPRIMENTO_MAXIMO_NOME }}"
            aria-describedby="erro-nome-edicao"
            required
            autofocus
            @error('nome')
                aria-invalid="true"
            @enderror
        >

        <div
            id="erro-nome-edicao"
            class="invalid-feedback @error('nome') d-block @enderror"
            aria-live="polite"
        >
            @error('nome')
                {{ $message }}
            @enderror
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 grupo-campo-formulario mb-3">
            <label
                class="form-label"
                for="data-inicio-edicao"
            >
                Data de início

                <span
                    class="text-danger"
                    aria-hidden="true"
                >
                    *
                </span>
            </label>

            <input
                id="data-inicio-edicao"
                class="form-control @error('data_inicio') is-invalid @enderror"
                type="date"
                name="data_inicio"
                value="{{ old('data_inicio', $dataInicioEdicao) }}"
                aria-describedby="erro-data-inicio-edicao"
                required
                @error('data_inicio')
                    aria-invalid="true"
                @enderror
            >

            <div
                id="erro-data-inicio-edicao"
                class="invalid-feedback @error('data_inicio') d-block @enderror"
                aria-live="polite"
            >
                @error('data_inicio')
                    {{ $message }}
                @enderror
            </div>
        </div>

        <div class="col-md-6 grupo-campo-formulario mb-3">
            <label
                class="form-label"
                for="data-fim-edicao"
            >
                Data de fim

                <span class="fw-normal text-muted">
                    (opcional)
                </span>
            </label>

            <input
                id="data-fim-edicao"
                class="form-control @error('data_fim') is-invalid @enderror"
                type="date"
                name="data_fim"
                value="{{ old('data_fim', $dataFimEdicao) }}"
                aria-describedby="erro-data-fim-edicao"
                @error('data_fim')
                    aria-invalid="true"
                @enderror
            >

            <div
                id="erro-data-fim-edicao"
                class="invalid-feedback @error('data_fim') d-block @enderror"
                aria-live="polite"
            >
                @error('data_fim')
                    {{ $message }}
                @enderror
            </div>
        </div>
    </div>

    <div class="text-end">
        <a
            class="btn btn-secondary"
            href="{{ route('edicoes.indice') }}"
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
