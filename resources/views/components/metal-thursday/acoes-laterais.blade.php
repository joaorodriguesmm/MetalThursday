{{--
    Apresenta os acessos rápidos às principais áreas de gestão relacionadas
    com a MetalThursday.

    A identificação das páginas ativas e a disponibilidade da ação genérica de
    criação são preparadas pela classe
    App\View\Components\MetalThursday\AcoesLaterais. A autorização de cada
    acesso continua a ser validada pela respetiva política.

    @since 1.0.0
--}}

<nav
    {{
        $attributes
            ->except([
                'aria-labelledby',
            ])
            ->class([
                'card',
                'shadow-sm',
                'bg-dark',
                'text-white',
            ])
    }}
    aria-labelledby="titulo-acoes-laterais"
>
    <div class="card-header border-secondary">
        <h2
            id="titulo-acoes-laterais"
            class="h5 text-white mb-0"
        >
            Ações
        </h2>
    </div>

    <div class="list-group list-group-flush">
        @if ($apresentaAcaoCriacaoMetalThursday)
            @can(
                'create',
                App\Models\MetalThursday\MetalThursday::class
            )
                <a
                    class="list-group-item list-group-item-action bg-dark text-white border-secondary {{
                        $paginaCriacaoMetalThursdayAtiva
                            ? 'active'
                            : ''
                    }}"
                    href="{{ route('metal-thursday.criar') }}"
                    @if ($paginaCriacaoMetalThursdayAtiva)
                        aria-current="page"
                    @endif
                >
                    <i
                        class="bi bi-plus-circle me-2"
                        aria-hidden="true"
                    ></i>

                    Criar MetalThursday
                </a>
            @endcan
        @endif

        @can(
            'viewAny',
            App\Models\Musica\Artista::class
        )
            <a
                class="list-group-item list-group-item-action bg-dark text-white border-secondary {{
                    $paginaArtistasAtiva
                        ? 'active'
                        : ''
                }}"
                href="{{ route('artistas.indice') }}"
                @if ($paginaArtistasAtiva)
                    aria-current="page"
                @endif
            >
                <i
                    class="bi bi-music-note-beamed me-2"
                    aria-hidden="true"
                ></i>

                Artistas
            </a>
        @endcan

        @can(
            'viewAny',
            App\Models\MetalThursday\Edicao::class
        )
            <a
                class="list-group-item list-group-item-action bg-dark text-white border-secondary {{
                    $paginaEdicoesAtiva
                        ? 'active'
                        : ''
                }}"
                href="{{ route('edicoes.indice') }}"
                @if ($paginaEdicoesAtiva)
                    aria-current="page"
                @endif
            >
                <i
                    class="bi bi-collection me-2"
                    aria-hidden="true"
                ></i>

                Edições
            </a>
        @endcan

        @can(
            'viewAny',
            App\Models\Musica\Genero::class
        )
            <a
                class="list-group-item list-group-item-action bg-dark text-white border-secondary {{
                    $paginaGenerosAtiva
                        ? 'active'
                        : ''
                }}"
                href="{{ route('generos.indice') }}"
                @if ($paginaGenerosAtiva)
                    aria-current="page"
                @endif
            >
                <i
                    class="bi bi-tags me-2"
                    aria-hidden="true"
                ></i>

                Géneros
            </a>
        @endcan
    </div>
</nav>
