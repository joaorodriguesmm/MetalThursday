{{--
    Apresenta os detalhes de uma banda e as respetivas aparições em
    MetalThursdays.

    Os dados do cabeçalho são preparados pelo
    App\Http\Controllers\Musica\ControladorBanda.

    Cada aparição é apresentada pelo componente
    App\View\Components\Musica\Bandas\CartaoAparicaoMetalThursday.

    @since 1.0.0
--}}

<x-layout-aplicacao>
    <x-slot name="titulo">
        {{ $banda->nome }}
    </x-slot>

    <x-slot name="cabecalho">
        <div>
            <h1 class="h4 mb-1 fw-bold">
                {{ $banda->nome }}

                <span class="h5">
                    ({{ $nomeOrigemGeograficaBanda }})
                </span>
            </h1>

            @if ($nomesGenerosBanda !== null)
                <p class="mb-0 text-muted">
                    {{ $nomesGenerosBanda }}
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
            href="{{ route('bandas.indice') }}"
        >
            Voltar às bandas
        </a>
    </div>

    @forelse ($seccoes as $seccao)
        <x-musica.bandas.cartao-aparicao-metal-thursday
            :seccao="$seccao"
            :nome-banda="$banda->nome"
        />
    @empty
        <div
            class="alert alert-info"
            role="status"
        >
            Esta banda ainda não apareceu em nenhuma secção.
        </div>
    @endforelse

    @if ($seccoes->hasPages())
        <nav
            class="mt-4"
            aria-label="Paginação das aparições da banda"
        >
            {{
                $seccoes->links(
                    'vendor.pagination.bootstrap-5',
                )
            }}
        </nav>
    @endif
</x-layout-aplicacao>
