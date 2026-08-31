{{--
    Apresenta os detalhes e as músicas favoritas de uma edição.

    Os grupos de utilizadores, campos de classificação, estado dos resultados
    e ligação da compilação são preparados pelo
    App\Servicos\MetalThursday\ServicoApresentacaoDetalhesEdicao.

    @since 1.0.0
--}}

<x-layout-aplicacao>
    <x-slot name="titulo">
        {{ $edicao->nome }}
    </x-slot>

    <x-slot name="cabecalho">
        <div
            class="d-flex justify-content-between align-items-center flex-wrap gap-3"
        >
            <div>
                <h1 class="h4 mb-1 fw-bold">
                    {{ $edicao->nome }}
                </h1>

                <p class="mb-0 text-muted">
                    Músicas favoritas da edição
                </p>
            </div>

            @if ($ligacaoCompilacao !== null)
                <a
                    class="btn btn-primary"
                    href="{{ $ligacaoCompilacao }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    aria-label="Ouvir a compilação de {{ $edicao->nome }} num novo separador"
                >
                    <i
                        class="bi bi-play-circle-fill me-2"
                        aria-hidden="true"
                    ></i>

                    Ouvir compilação
                </a>
            @endif
        </div>
    </x-slot>

    <x-estado-sessao class="mb-4" />

    <div class="card shadow-sm">
        <div class="card-body">
            @if ($gruposMusicasFavoritas === [])
                <div
                    class="alert alert-info mb-0"
                    role="status"
                >
                    Não existem utilizadores disponíveis para registar
                    músicas favoritas.
                </div>
            @elseif ($bloqueada)
                <h2 class="h5 mb-4">
                    Resultados finais
                </h2>

                @foreach ($gruposMusicasFavoritas as $grupo)
                    <section class="mb-4">
                        <h3 class="h6">
                            {{ $grupo['utilizador']->nome }}
                        </h3>

                        <ol class="list-group list-group-numbered">
                            @forelse ($grupo['escolhas'] as $escolha)
                                <li class="list-group-item">
                                    {{ $escolha->musica }}
                                </li>
                            @empty
                                <li class="list-group-item text-muted">
                                    Nenhuma música registada.
                                </li>
                            @endforelse
                        </ol>
                    </section>
                @endforeach
            @else
                @can('update', $edicao)
                    <form
                        method="POST"
                        action="{{
                            route(
                                'edicoes.musicas-favoritas.guardar',
                                $edicao,
                            )
                        }}"
                        novalidate
                    >
                        @csrf

                        @error('musicas_favoritas')
                            <div
                                class="alert alert-danger"
                                role="alert"
                            >
                                {{ $message }}
                            </div>
                        @enderror

                        @foreach ($gruposMusicasFavoritas as $grupo)
                            <fieldset class="mb-4">
                                <legend class="h5">
                                    {{ $grupo['utilizador']->nome }}
                                </legend>

                                <div class="row">
                                    @foreach ($grupo['campos'] as $campo)
                                        <div class="col-md-4 mb-3">
                                            <div class="grupo-campo-formulario">
                                                <label
                                                    class="form-label"
                                                    for="{{
                                                        $campo['identificadorCampo']
                                                    }}"
                                                >
                                                    {{ $campo['posicao'] }}.ª posição
                                                </label>

                                                <input
                                                    id="{{
                                                        $campo['identificadorCampo']
                                                    }}"
                                                    class="form-control @error($campo['chaveCampo']) is-invalid @enderror"
                                                    type="text"
                                                    name="{{ $campo['nomeCampo'] }}"
                                                    value="{{
                                                        old(
                                                            $campo['chaveCampo'],
                                                            $campo['valorPredefinido'],
                                                        )
                                                    }}"
                                                    placeholder="Artista: álbum — música"
                                                    maxlength="{{ App\Models\MetalThursday\MusicaFavoritaEdicao::COMPRIMENTO_MAXIMO_MUSICA }}"
                                                    aria-describedby="{{
                                                        $campo['identificadorErro']
                                                    }}"
                                                    @error($campo['chaveCampo'])
                                                        aria-invalid="true"
                                                    @enderror
                                                >

                                                <div
                                                    id="{{
                                                        $campo['identificadorErro']
                                                    }}"
                                                    class="invalid-feedback @error($campo['chaveCampo']) d-block @enderror"
                                                    aria-live="polite"
                                                >
                                                    @error($campo['chaveCampo'])
                                                        {{ $message }}
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </fieldset>
                        @endforeach

                        <div class="text-end">
                            <button
                                class="btn btn-primary"
                                type="submit"
                            >
                                Guardar músicas favoritas
                            </button>
                        </div>
                    </form>
                @else
                    <div
                        class="alert alert-info mb-0"
                        role="status"
                    >
                        Os resultados finais ainda não estão disponíveis.
                    </div>
                @endcan
            @endif
        </div>
    </div>

    @can('update', $edicao)
        <div class="card shadow-sm mt-4">
            <div class="card-header">
                <h2 class="h5 mb-0">
                    Ligação da compilação
                </h2>
            </div>

            <div class="card-body">
                <form
                    method="POST"
                    action="{{
                        route(
                            'edicoes.ligacao-compilacao.atualizar',
                            $edicao,
                        )
                    }}"
                    novalidate
                >
                    @csrf
                    @method('PATCH')

                    <div class="grupo-campo-formulario mb-3">
                        <label
                            class="form-label"
                            for="ligacao-compilacao"
                        >
                            Ligação da lista de reprodução
                        </label>

                        <input
                            id="ligacao-compilacao"
                            class="form-control @error('ligacao_compilacao') is-invalid @enderror"
                            type="url"
                            name="ligacao_compilacao"
                            value="{{
                                old(
                                    'ligacao_compilacao',
                                    $ligacaoCompilacao,
                                )
                            }}"
                            placeholder="https://..."
                            maxlength="{{ App\Models\MetalThursday\Edicao::COMPRIMENTO_MAXIMO_LIGACAO_COMPILACAO }}"
                            inputmode="url"
                            autocomplete="url"
                            aria-describedby="ajuda-ligacao-compilacao erro-ligacao-compilacao"
                            @error('ligacao_compilacao')
                                aria-invalid="true"
                            @enderror
                        >

                        <div
                            id="ajuda-ligacao-compilacao"
                            class="form-text"
                        >
                            Deixa o campo vazio para remover a ligação atual.
                        </div>

                        <div
                            id="erro-ligacao-compilacao"
                            class="invalid-feedback @error('ligacao_compilacao') d-block @enderror"
                            aria-live="polite"
                        >
                            @error('ligacao_compilacao')
                                {{ $message }}
                            @enderror
                        </div>
                    </div>

                    <div class="text-end">
                        <button
                            class="btn btn-primary"
                            type="submit"
                        >
                            Guardar ligação
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endcan

    <div class="mt-4">
        <a
            class="btn btn-secondary"
            href="{{ route('edicoes.indice') }}"
        >
            Voltar às edições
        </a>
    </div>
</x-layout-aplicacao>
