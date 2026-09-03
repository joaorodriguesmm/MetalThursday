{{--
    Apresenta o perfil de um artista e as respetivas aparições em
    MetalThursdays.

    @since 1.0.0
--}}

<x-layout-aplicacao>
    <x-slot name="titulo">
        {{ $artista->nome }}
    </x-slot>

    <x-slot name="cabecalho">
        <div class="d-flex flex-column flex-sm-row justify-content-between gap-3">
            <div>
                <h1 class="h4 mb-1 fw-bold">
                    {{ $artista->nome }}

                    @if ($nomeOrigemGeograficaArtista !== null)
                        <span class="h5">
                            ({{ $nomeOrigemGeograficaArtista }})
                        </span>
                    @endif
                </h1>

                @if ($nomesGenerosArtista !== null)
                    <p class="mb-0 text-muted">
                        {{ $nomesGenerosArtista }}
                    </p>
                @endif
            </div>

            @can('update', $artista)
                <div>
                    <a
                        class="btn btn-sm btn-secondary"
                        href="{{ route('artistas.editar', $artista) }}"
                    >
                        <i
                            class="bi bi-pencil-square"
                            aria-hidden="true"
                        ></i>

                        Editar artista
                    </a>
                </div>
            @endcan
        </div>
    </x-slot>

    <x-estado-sessao class="mb-4" />

    <section
        class="card shadow-sm bg-dark mb-4"
        aria-labelledby="titulo-perfil-artista"
    >
        <div class="card-body">
            <h2
                id="titulo-perfil-artista"
                class="h5 mb-3"
            >
                Perfil
            </h2>

            <div class="row g-4">
                @if ($artista->url_imagem !== null)
                    <div class="col-12 col-md-auto">
                        <img
                            class="img-thumbnail"
                            src="{{ $artista->url_imagem }}"
                            alt="Imagem de {{ $artista->nome }}"
                            style="width: 220px; height: 220px; object-fit: cover;"
                        >
                    </div>
                @endif

                <div class="col">
                    <dl class="row mb-0">
                        <dt class="col-sm-4 col-lg-3">
                            Atividade
                        </dt>

                        <dd class="col-sm-8 col-lg-9">
                            @if (
                                $artista->ano_inicio_atividade !== null
                                && $artista->ano_fim_atividade !== null
                            )
                                {{ $artista->ano_inicio_atividade }}–{{ $artista->ano_fim_atividade }}
                            @elseif ($artista->ano_inicio_atividade !== null)
                                Desde {{ $artista->ano_inicio_atividade }}
                            @elseif ($artista->ano_fim_atividade !== null)
                                Até {{ $artista->ano_fim_atividade }}
                            @else
                                <span class="text-muted">
                                    Não indicada
                                </span>
                            @endif
                        </dd>

                        <dt class="col-sm-4 col-lg-3">
                            Estado
                        </dt>

                        <dd class="col-sm-8 col-lg-9">
                            @if ($artista->estado_atividade !== null)
                                {{ $artista->estado_atividade->etiqueta() }}
                            @else
                                <span class="text-muted">
                                    Não indicado
                                </span>
                            @endif
                        </dd>

                        <dt class="col-sm-4 text-muted">
                            MusicBrainz
                        </dt>

                        <dd class="col-sm-8">
                            @if ($artista->url_musicbrainz !== null)
                                <a
                                    href="{{ $artista->url_musicbrainz }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    Abrir perfil MusicBrainz
                                </a>
                            @else
                                <span class="text-muted">
                                    Não associado
                                </span>
                            @endif
                        </dd>

                        <dt class="col-sm-4 col-lg-3">
                            Discogs
                        </dt>

                        <dd class="col-sm-8 col-lg-9">
                            @if ($artista->url_discogs !== null)
                                <a
                                    href="{{ $artista->url_discogs }}"
                                    target="_blank"
                                    rel="noopener noreferrer nofollow"
                                >
                                    Perfil Discogs #{{ $artista->discogs_id }}
                                </a>
                            @else
                                <span class="text-muted">
                                    Não associado
                                </span>
                            @endif
                        </dd>
                    </dl>
                </div>
            </div>

            @if ($artista->biografia !== null)
                <hr class="border-secondary">

                <h3 class="h6">
                    Biografia
                </h3>

                <div class="mb-0">
                    {!! nl2br(e($artista->biografia)) !!}
                </div>
            @endif

            @if ($artista->ligacoes->isNotEmpty())
                <hr class="border-secondary">

                <h3 class="h6">
                    Ligações
                </h3>

                <ul class="mb-0">
                    @foreach ($artista->ligacoes as $ligacao)
                        <li>
                            <a
                                href="{{ $ligacao->url }}"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                {{ $ligacao->titulo }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </section>

    <div
        class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-3"
    >
        <h2 class="h5 mb-0">
            Aparições em MetalThursdays
            ({{ $seccoes->total() }})
        </h2>

        <a
            class="btn btn-sm btn-secondary"
            href="{{ route('artistas.indice') }}"
        >
            Voltar aos artistas
        </a>
    </div>

    @forelse ($seccoes as $seccao)
        <x-musica.artistas.cartao-aparicao-metal-thursday
            :seccao="$seccao"
            :nome-artista="$artista->nome"
        />
    @empty
        <div
            class="alert alert-info"
            role="status"
        >
            Este artista ainda não apareceu em nenhuma secção.
        </div>
    @endforelse

    @if ($seccoes->hasPages())
        <nav
            class="mt-4"
            aria-label="Paginação das aparições do artista"
        >
            {{
                $seccoes->links(
                    'vendor.pagination.bootstrap-5',
                )
            }}
        </nav>
    @endif
</x-layout-aplicacao>
