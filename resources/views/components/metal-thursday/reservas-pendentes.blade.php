{{--
    Apresenta as reservas de MetalThursday que ainda se encontram pendentes.

    As reservas são independentes dos filtros e da paginação das MetalThursdays
    publicadas. Apenas o responsável da reserva recebe a ação para preparar a
    respetiva publicação.

    @since 2.0.0
--}}

@if ($reservasPreparadas !== [])
    <section
        {{
            $attributes->class([
                'mb-4',
            ])
        }}
        aria-labelledby="titulo-reservas-metal-thursday-pendentes"
    >
        <div
            class="d-flex justify-content-between align-items-center gap-3 mb-3"
        >
            <h2
                id="titulo-reservas-metal-thursday-pendentes"
                class="h5 mb-0"
            >
                MetalThursdays por publicar
            </h2>

            <span class="badge text-bg-secondary">
                {{ count($reservasPreparadas) }}
            </span>
        </div>

        <div class="vstack gap-3">
            @foreach ($reservasPreparadas as $reservaPreparada)
                <article
                    id="reserva-metal-thursday-{{ $reservaPreparada['identificador'] }}"
                    class="card shadow-sm"
                >
                    <header class="card-header bg-dark text-white">
                        <div
                            class="d-flex justify-content-between align-items-center gap-3"
                        >
                            <h3 class="h6 mb-0">
                                MetalThursday de
                                {{ $reservaPreparada['data'] }}
                            </h3>

                            <span
                                class="badge {{
                                    $reservaPreparada['emAtraso']
                                        ? 'text-bg-danger'
                                        : 'text-bg-secondary'
                                }}"
                            >
                                {{
                                    $reservaPreparada['emAtraso']
                                        ? 'Em atraso'
                                        : 'Por publicar'
                                }}
                            </span>
                        </div>
                    </header>

                    <div class="card-body">
                        <p class="mb-0">
                            <strong>
                                Responsável:
                            </strong>

                            {{ $reservaPreparada['nomeResponsavel'] }}
                        </p>

                        @if ($reservaPreparada['podePreparar'])
                            <a
                                class="btn btn-primary mt-3"
                                href="{{
                                    route(
                                        'metal-thursday.reservas.preparar',
                                        $reservaPreparada['identificador'],
                                    )
                                }}"
                            >
                                <i
                                    class="bi bi-pencil-square me-2"
                                    aria-hidden="true"
                                ></i>

                                Preparar MetalThursday
                            </a>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    </section>
@endif
