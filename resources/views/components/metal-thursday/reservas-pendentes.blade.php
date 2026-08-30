{{--
    Apresenta as MetalThursdays que ainda exigem acompanhamento operacional.

    São distinguidos os estados Por preparar, Rascunho e Preparada. Uma
    MetalThursday preparada apenas surge quando o utilizador autenticado possui
    autorização para a alterar e nunca expõe aqui o respetivo conteúdo.

    @since 2.0.0
--}}

@if ($itensPorPublicar !== [])
    <section
        {{
            $attributes->class([
                'mb-4',
            ])
        }}
        aria-labelledby="titulo-metal-thursdays-por-publicar"
    >
        <div
            class="d-flex justify-content-between align-items-center gap-3 mb-3"
        >
            <h2
                id="titulo-metal-thursdays-por-publicar"
                class="h5 mb-0"
            >
                MetalThursdays por publicar
            </h2>

            <span class="badge text-bg-secondary">
                {{ count($itensPorPublicar) }}
            </span>
        </div>

        <div class="vstack gap-3">
            @foreach ($itensPorPublicar as $itemPorPublicar)
                <article
                    id="{{ $itemPorPublicar['idElemento'] }}"
                    class="card shadow-sm"
                >
                    <header class="card-header bg-dark text-white">
                        <div
                            class="d-flex justify-content-between align-items-center gap-3"
                        >
                            <h3 class="h6 mb-0">
                                MetalThursday de
                                {{ $itemPorPublicar['data'] }}
                            </h3>

                            <div
                                class="d-flex flex-wrap justify-content-end gap-2"
                            >
                                @if ($itemPorPublicar['emAtraso'])
                                    <span class="badge text-bg-danger">
                                        Em atraso
                                    </span>
                                @endif

                                <span
                                    class="badge {{
                                        match ($itemPorPublicar['estado']) {
                                            'Preparada' => 'text-bg-success',
                                            'Rascunho' => 'text-bg-warning',
                                            default => 'text-bg-secondary',
                                        }
                                    }}"
                                >
                                    {{ $itemPorPublicar['estado'] }}
                                </span>
                            </div>
                        </div>
                    </header>

                    <div class="card-body">
                        <p class="mb-0">
                            <strong>
                                Responsável:
                            </strong>

                            {{ $itemPorPublicar['nomeResponsavel'] }}
                        </p>

                        @if ($itemPorPublicar['rotaAcao'] !== null)
                            <a
                                class="btn btn-primary mt-3"
                                href="{{ $itemPorPublicar['rotaAcao'] }}"
                            >
                                <i
                                    class="bi bi-pencil-square me-2"
                                    aria-hidden="true"
                                ></i>

                                {{ $itemPorPublicar['textoAcao'] }}
                            </a>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    </section>
@endif
