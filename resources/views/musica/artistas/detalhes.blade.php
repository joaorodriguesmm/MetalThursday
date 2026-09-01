{{--
    Apresenta os detalhes de um artista e as respetivas aparições em
    MetalThursdays.

    Os dados do cabeçalho são preparados pelo
    App\Http\Controllers\Musica\ControladorArtista.

    O componente das aparições conserva temporariamente a nomenclatura
    anterior durante a migração estrutural.

    @since 1.0.0
--}}

<x-layout-aplicacao>
    <x-slot name="titulo">
        {{ $artista->nome }}
    </x-slot>

    <x-slot name="cabecalho">
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
    </x-slot>

    <x-estado-sessao class="mb-4" />

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
