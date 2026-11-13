{{--
    Apresenta os detalhes completos de uma MetalThursday.

    O cartão principal prepara e apresenta as secções, comentários,
    avaliações, audições e ações de gestão autorizadas.

    @since 1.0.0
--}}

<x-layout-aplicacao>
    <x-slot name="titulo">
        {{ $metalThursday->edicao?->nome ?? 'MetalThursday' }}

        @if ($metalThursday->numero_semana_na_edicao !== null)
            — Semana {{ $metalThursday->numero_semana_na_edicao }}
        @endif
    </x-slot>

    <x-slot name="cabecalho">
        <div>
            <h1 class="h4 mb-1 fw-bold">
                {{ $metalThursday->edicao?->nome ?? 'MetalThursday' }}

                @if ($metalThursday->numero_semana_na_edicao !== null)
                    — Semana {{ $metalThursday->numero_semana_na_edicao }}
                @endif
            </h1>

            @if (filled($metalThursday->nome))
                <p class="mb-0 text-muted">
                    {{ $metalThursday->nome }}
                </p>
            @endif
        </div>
    </x-slot>

    <x-estado-sessao class="mb-4" />

    @unless ($metalThursday->estaPublicada())
        <div class="alert alert-info mb-4" role="status">
            <strong>Pré-visualização</strong>

            <p class="mb-0 mt-2">
                Esta MetalThursday ainda não está publicada.
                Apenas os utilizadores autorizados podem consultar
                o conteúdo. As interações sociais estão indisponíveis.
            </p>
        </div>
    @endunless

    <x-metal-thursday.cartao-vista-completa
        :registo-metal-thursday="$metalThursday"
        class="mb-0"
    />

    @include(
        'metal-thursday.parciais._modal-avaliacao'
    )

    @push('scripts-pagina')
        @vite(
            'resources/js/paginas/detalhesMetalThursday.js'
        )
    @endpush
</x-layout-aplicacao>
