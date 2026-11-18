{{--
    Apresenta o formulário de criação ou edição de um artista.

    Os campos essenciais permanecem sempre visíveis. Os metadados opcionais
    ficam agrupados numa área expansível para manter o formulário compacto.

    @since 1.0.0
--}}

@php
    $mostrarCamposAdicionais =
        $errors->has('ano_inicio_atividade')
        || $errors->has('ano_fim_atividade')
        || $errors->has('estado_atividade')
        || $errors->has('biografia')
        || $errors->has('imagem')
        || $errors->has('discogs_id')
        || $errors->has('ligacoes')
        || $errors->has('ligacoes.*')
        || $errors->has('musicbrainz_id');

    if ($emEdicao && ! $mostrarCamposAdicionais) {
        $mostrarCamposAdicionais =
            $artista->ano_inicio_atividade !== null
            || $artista->ano_fim_atividade !== null
            || filled($artista->estado_atividade)
            || filled($artista->biografia)
            || filled($artista->imagem)
            || filled($artista->musicbrainz_id)
            || filled($artista->discogs_id)
            || $artista->ligacoes()->exists();
    }
@endphp

<form
    id="formulario-artista"
    method="POST"
    action="{{ $enderecoFormulario }}"
    autocomplete="off"
    data-formulario-perfil-artista
    novalidate
>
    @csrf

    @if (! $emEdicao)
        <input
            type="hidden"
            name="chave_submissao"
            value="{{ old('chave_submissao', (string) \Illuminate\Support\Str::uuid()) }}"
        >
    @endif

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
        <div class="d-flex align-items-start gap-2 mb-2">
            <label
                class="form-label mb-0"
                for="nome-artista"
            >
                Nome
                <span class="text-danger" aria-hidden="true">*</span>
            </label>

            <x-ajuda-campo
                campo="Nome"
                texto="Utiliza o nome artístico habitual, sem acrescentar país ou género. O sistema avisa se existirem possíveis homónimos."
            />
        </div>

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
            @error('nome') aria-invalid="true" @enderror
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
        <div class="d-flex align-items-start gap-2 mb-2">
            <label
                class="form-label mb-0"
                for="origem-geografica-artista"
            >
                Origem geográfica
            </label>

            <x-ajuda-campo
                campo="Origem geográfica"
                texto="Indica o país ou território de origem do artista. Deixa em branco se a informação não for conhecida."
            />
        </div>

        <select
            id="origem-geografica-artista"
            class="form-select tom-select-unico @error('origem_geografica_id') is-invalid @enderror"
            name="origem_geografica_id"
            placeholder="Seleciona uma origem geográfica"
            aria-describedby="erro-origem-geografica-artista"
            autocomplete="off"
            data-ordenar-alfabeticamente
            @error('origem_geografica_id') aria-invalid="true" @enderror
        >
            <option value="">Seleciona uma origem geográfica</option>

            @foreach ($origensGeograficas as $origemGeografica)
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
        <div class="d-flex align-items-start gap-2 mb-2">
            <label
                class="form-label mb-0"
                for="generos-artista"
            >
                Géneros
            </label>

            <x-ajuda-campo
                campo="Géneros"
                texto="Podes selecionar vários géneros musicais. Utiliza o botão de adicionar se o género pretendido ainda não existir."
            />
        </div>

        <div class="input-group has-validation">
            <select
                id="generos-artista"
                class="form-select tom-select-multiplo {{
                    ($errors->has('generos') || $errors->has('generos.*'))
                        ? 'is-invalid'
                        : ''
                }}"
                name="generos[]"
                placeholder="Seleciona um ou mais géneros"
                aria-describedby="erro-generos-artista"
                autocomplete="off"
                data-ordenar-alfabeticamente
                multiple
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

            @can('create', App\Models\Musica\Genero::class)
                <button
                    class="btn btn-secondary"
                    type="button"
                    data-bs-toggle="modal"
                    data-bs-target="#modal-criar-genero"
                    aria-label="Criar novo género"
                    title="Criar novo género"
                >
                    <i class="bi bi-plus-lg" aria-hidden="true"></i>
                </button>
            @endcan
        </div>

        <div
            id="erro-generos-artista"
            class="invalid-feedback {{
                ($errors->has('generos') || $errors->has('generos.*'))
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

        <div
            id="sugestoes-generos-artista"
            data-sugestoes-generos-importacao
            class="mt-2 border rounded p-3"
            aria-live="polite"
            hidden
        ></div>
    </div>

    <div class="mb-3">
        <button
            class="btn btn-sm btn-secondary"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#campos-adicionais-artista"
            aria-expanded="{{ $mostrarCamposAdicionais ? 'true' : 'false' }}"
            aria-controls="campos-adicionais-artista"
            data-alternar-campos-adicionais
            data-texto-apresentar="Apresentar campos adicionais"
            data-texto-ocultar="Ocultar campos adicionais"
        >
            {{
                $mostrarCamposAdicionais
                    ? 'Ocultar campos adicionais'
                    : 'Apresentar campos adicionais'
            }}
        </button>

        <p class="form-text mt-2 mb-0">
            Datas, estado de atividade, biografia, imagem, importação de dados e ligações.
        </p>
    </div>

    <div
        id="campos-adicionais-artista"
        class="collapse {{ $mostrarCamposAdicionais ? 'show' : '' }}"
        data-campos-adicionais-artista
    >
        <div class="rounded-3 border border-secondary bg-black bg-opacity-10 p-3 p-lg-4 mb-3">
            @include(
                'musica.artistas._importacao',
                [
                    'identificadorMusicBrainzImportacao' => $identificadorMusicBrainzArtista,
                    'identificadorDiscogsImportacao' => $identificadorDiscogsArtista,
                ]
            )

            <div class="row">
                <div class="col-md-4 grupo-campo-formulario mb-3">
                    <div class="d-flex align-items-start gap-2 mb-2">
                        <label class="form-label mb-0" for="ano-inicio-atividade-artista">
                            Ano de início
                        </label>

                        <x-ajuda-campo
                            campo="Ano de início"
                            texto="Ano em que o artista iniciou a atividade, que pode ser anterior ao primeiro lançamento."
                        />
                    </div>

                    <input
                        id="ano-inicio-atividade-artista"
                        class="form-control @error('ano_inicio_atividade') is-invalid @enderror"
                        type="number"
                        name="ano_inicio_atividade"
                        value="{{ $anoInicioAtividadeArtista }}"
                        min="{{ App\Models\Musica\Artista::ANO_MINIMO_ATIVIDADE }}"
                        max="{{ $anoAtual }}"
                        step="1"
                        inputmode="numeric"
                    >

                    @error('ano_inicio_atividade')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4 grupo-campo-formulario mb-3">
                    <div class="d-flex align-items-start gap-2 mb-2">
                        <label class="form-label mb-0" for="ano-fim-atividade-artista">
                            Ano de fim
                        </label>

                        <x-ajuda-campo
                            campo="Ano de fim"
                            texto="Indica o ano em que a atividade terminou. Deixa em branco se não se aplicar ou se for desconhecido."
                        />
                    </div>

                    <input
                        id="ano-fim-atividade-artista"
                        class="form-control @error('ano_fim_atividade') is-invalid @enderror"
                        type="number"
                        name="ano_fim_atividade"
                        value="{{ $anoFimAtividadeArtista }}"
                        min="{{ App\Models\Musica\Artista::ANO_MINIMO_ATIVIDADE }}"
                        max="{{ $anoAtual }}"
                        step="1"
                        inputmode="numeric"
                    >

                    @error('ano_fim_atividade')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4 grupo-campo-formulario mb-3">
                    <div class="d-flex align-items-start gap-2 mb-2">
                        <label class="form-label mb-0" for="estado-atividade-artista">
                            Estado de atividade
                        </label>

                        <x-ajuda-campo
                            campo="Estado de atividade"
                            texto="Seleciona a situação atual do artista. Utiliza Não indicado quando não houver informação suficiente."
                        />
                    </div>

                    <select
                        id="estado-atividade-artista"
                        class="form-select @error('estado_atividade') is-invalid @enderror"
                        name="estado_atividade"
                    >
                        <option value="">Não indicado</option>

                        @foreach (App\Enumeracoes\EstadoAtividadeArtista::cases() as $estadoAtividade)
                            <option
                                value="{{ $estadoAtividade->value }}"
                                @selected($estadoAtividadeArtista === $estadoAtividade->value)
                            >
                                {{ $estadoAtividade->etiqueta() }}
                            </option>
                        @endforeach
                    </select>

                    @error('estado_atividade')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="grupo-campo-formulario mb-3">
                <div class="d-flex align-items-start gap-2 mb-2">
                    <label class="form-label mb-0" for="biografia-artista">
                        Biografia
                    </label>

                    <x-ajuda-campo
                        campo="Biografia"
                        texto="Escreve uma síntese factual da história e evolução do artista, evitando repetir os dados dos restantes campos."
                    />
                </div>

                <textarea
                    id="biografia-artista"
                    class="form-control @error('biografia') is-invalid @enderror"
                    name="biografia"
                    rows="6"
                    maxlength="{{ App\Models\Musica\Artista::COMPRIMENTO_MAXIMO_BIOGRAFIA }}"
                >{{ $biografiaArtista }}</textarea>

                @error('biografia')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>

            <div class="grupo-campo-formulario mb-3">
                <div class="d-flex align-items-start gap-2 mb-2">
                    <label class="form-label mb-0" for="imagem-artista">
                        Imagem
                        <span class="fw-normal text-muted">(URL externa)</span>
                    </label>

                    <x-ajuda-campo
                        campo="Imagem"
                        texto="Indica o endereço de uma imagem pública do artista. Este campo também pode ser preenchido automaticamente através da importação de dados."
                    />
                </div>

                <input
                    id="imagem-artista"
                    class="form-control @error('imagem') is-invalid @enderror"
                    type="url"
                    name="imagem"
                    value="{{ $imagemArtista }}"
                    maxlength="{{ App\Models\Musica\Artista::COMPRIMENTO_MAXIMO_URL_IMAGEM }}"
                    placeholder="https://exemplo.com/imagem.jpg"
                    inputmode="url"
                    autocomplete="url"
                >


                @error('imagem')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>

            <section class="rounded-3 border border-secondary bg-black bg-opacity-25 p-3">
                <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                    <div>
                        <h3 class="h6 mb-0">Ligações</h3>
                        <p class="small text-muted mb-0">
                            Site oficial, Bandcamp, redes sociais ou outras páginas relevantes.
                        </p>
                    </div>

                    <button
                        class="btn btn-sm btn-secondary"
                        type="button"
                        data-adicionar-ligacao
                    >
                        <i class="bi bi-plus-lg" aria-hidden="true"></i>
                        Adicionar ligação
                    </button>
                </div>

                <div data-lista-ligacoes>
                    @foreach ($ligacoesFormulario as $indice => $ligacaoFormulario)
                        <div class="row g-2 mb-2" data-ligacao>
                            <div class="col-md-4">
                                <input
                                    class="form-control"
                                    type="text"
                                    name="ligacoes[{{ $indice }}][titulo]"
                                    value="{{ $ligacaoFormulario['titulo'] }}"
                                    maxlength="{{ App\Models\Comum\Ligacao::COMPRIMENTO_MAXIMO_TITULO }}"
                                    placeholder="Ex.: Site oficial"
                                    aria-label="Título da ligação"
                                    data-campo-ligacao="titulo"
                                >
                            </div>

                            <div class="col-md-7">
                                <input
                                    class="form-control"
                                    type="url"
                                    name="ligacoes[{{ $indice }}][url]"
                                    value="{{ $ligacaoFormulario['url'] }}"
                                    maxlength="{{ App\Models\Comum\Ligacao::COMPRIMENTO_MAXIMO_URL }}"
                                    placeholder="https://..."
                                    inputmode="url"
                                    aria-label="Endereço da ligação"
                                    data-campo-ligacao="url"
                                >
                            </div>

                            <div class="col-md-1 d-grid">
                                <button
                                    class="btn btn-outline-danger"
                                    type="button"
                                    data-remover-ligacao
                                    aria-label="Remover ligação"
                                >
                                    <i class="bi bi-trash" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>

                <template data-modelo-ligacao>
                    <div class="row g-2 mb-2" data-ligacao>
                        <div class="col-md-4">
                            <input
                                class="form-control"
                                type="text"
                                name="ligacoes[__INDICE__][titulo]"
                                maxlength="{{ App\Models\Comum\Ligacao::COMPRIMENTO_MAXIMO_TITULO }}"
                                placeholder="Ex.: Site oficial"
                                aria-label="Título da ligação"
                                data-campo-ligacao="titulo"
                            >
                        </div>

                        <div class="col-md-7">
                            <input
                                class="form-control"
                                type="url"
                                name="ligacoes[__INDICE__][url]"
                                maxlength="{{ App\Models\Comum\Ligacao::COMPRIMENTO_MAXIMO_URL }}"
                                placeholder="https://..."
                                inputmode="url"
                                aria-label="Endereço da ligação"
                                data-campo-ligacao="url"
                            >
                        </div>

                        <div class="col-md-1 d-grid">
                            <button
                                class="btn btn-outline-danger"
                                type="button"
                                data-remover-ligacao
                                aria-label="Remover ligação"
                            >
                                <i class="bi bi-trash" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                </template>

                @if ($errors->has('ligacoes') || $errors->has('ligacoes.*'))
                    <div class="invalid-feedback d-block">
                        {{ $errors->first('ligacoes') ?: $errors->first('ligacoes.*') }}
                    </div>
                @endif
            </section>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2">
        <a class="btn btn-secondary" href="{{ route('artistas.indice') }}">
            Cancelar
        </a>

        <button class="btn btn-primary" type="submit">
            @if (! $emEdicao && ($exigeConfirmacaoNomeRepetido ?? false))
                Criar artista mesmo assim
            @else
                {{ $textoBotaoSubmissao }}
            @endif
        </button>
    </div>
</form>
