{{--
    Permite escolher como apresentar as plataformas nas MetalThursdays.
    As preferências afetam apenas a visualização do próprio utilizador.

    @since 2.0.0
--}}

<section class="card shadow-sm mb-4" aria-labelledby="titulo-preferencias-visualizacao">
    <div class="card-header">
        <h2 id="titulo-preferencias-visualizacao" class="h5 mb-0">
            Preferências de visualização
        </h2>

        <p class="card-subtitle text-muted small mt-1 mb-0">
            Escolhe como pretendes ver as ligações das plataformas musicais.
        </p>
    </div>

    <div class="card-body">
        <form
            id="formulario-preferencias-visualizacao"
            method="POST"
            action="{{ route('perfil.visualizacao.atualizar') }}"
        >
            @csrf
            @method('PATCH')

            @error('plataformas', 'preferenciasVisualizacao')
                <div class="alert alert-danger" role="alert">
                    {{ $message }}
                </div>
            @enderror

            <div class="row g-3">
                @foreach ([
                    \App\Enumeracoes\PlataformaLigacao::AppleMusic,
                    \App\Enumeracoes\PlataformaLigacao::Spotify,
                    \App\Enumeracoes\PlataformaLigacao::YouTube,
                ] as $plataforma)
                    @php
                        $campoPreferencia = "plataformas.{$plataforma->value}";
                        $valorPreferencia = old(
                            $campoPreferencia,
                            $utilizador->modoVisualizacaoLigacao($plataforma)->value,
                        );
                    @endphp

                    <div class="col-12 col-md-4">
                        <label
                            class="form-label"
                            for="preferencia-{{ $plataforma->value }}"
                        >
                            {{ $plataforma === \App\Enumeracoes\PlataformaLigacao::YouTube
                                ? 'YouTube / YouTube Music'
                                : $plataforma->nome() }}
                        </label>

                        <select
                            id="preferencia-{{ $plataforma->value }}"
                            name="plataformas[{{ $plataforma->value }}]"
                            class="form-select @error($campoPreferencia, 'preferenciasVisualizacao') is-invalid @enderror"
                            @error($campoPreferencia, 'preferenciasVisualizacao') aria-invalid="true" @enderror
                        >
                            @foreach (\App\Enumeracoes\ModoVisualizacaoLigacao::cases() as $modo)
                                <option
                                    value="{{ $modo->value }}"
                                    @selected($valorPreferencia === $modo->value)
                                >
                                    {{ $modo->nome() }}
                                </option>
                            @endforeach
                        </select>

                        @error($campoPreferencia, 'preferenciasVisualizacao')
                            <div class="invalid-feedback d-block" role="alert">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>
                @endforeach
            </div>

            <p class="small text-muted mt-3 mb-0">
                Se uma ligação não permitir incorporação, será apresentada
                como ligação externa. Ocultar não elimina as ligações guardadas.
            </p>

            <div class="d-flex justify-content-end mt-4">
                <button class="btn btn-primary" type="submit">
                    Guardar preferências
                </button>
            </div>
        </form>
    </div>
</section>
