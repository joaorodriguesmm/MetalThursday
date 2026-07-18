@props(['mt'])

<div class="card mb-4 shadow-sm">
    <div class="card-header bg-dark text-white">
        <div class="d-flex justify-content-between align-items-start">
            <div class="flex-grow-1">
                <h2 class="h4 card-title mb-0">
                    <a href="{{ route('metalthursday.show', $mt) }}" class="text-white text-decoration-none">
                        {{ $mt->edition?->name }} - Semana {{ $mt->week_number_in_edition ?? 'N/A' }}
                        @if ($mt->name) - {{ $mt->name }} @endif
                        ({{ $mt->date->format('d/m/Y') }})
                    </a>
                </h2>
                <small class="text-muted">Por: {{ $mt->author?->name ?? 'Desconhecido' }} | Nomeado: {{ $mt->nextNominee?->name ?? 'N/A' }}</small>
            </div>

            <div class="ms-3 flex-shrink-0">
                @can('update', $mt)
                    <a href="{{ route('metalthursday.edit', $mt) }}" class="btn btn-sm btn-secondary" title="Editar">
                        <i class="bi bi-pencil-square"></i>
                    </a>
                @endcan
                @can('delete', $mt)
                    <button
                        class="btn btn-sm btn-danger"
                        title="Elimnar"
                        data-interaction-type="delete"
                        data-url="{{ route('metalthursday.destroy', $mt) }}"
                        data-removable-parent=".card"
                    >
                        <i class="bi bi-trash"></i>
                    </button>
                @endcan
            </div>
        </div>
    </div>

    <div class="card-body">
        @forelse ($mt->sections as $section)
            <div class="mb-4" id="section-{{ $section->id }}">
                @if (!$section->sectionType->has_details)
                    @if($section->title)<h4 class="h6 text-primary">{{ $section->title }}</h4>@endif
                    <p>{!! nl2br(e($section->description)) !!}</p>
                @else
                    <p class="mb-2">{!! nl2br(e($section->description)) !!}</p>
                    <h4 class="h6">
                        <strong>{{ $section->band?->name ?? 'Banda Desconhecida' }}</strong> - {{ $section->title }} ({{ $section->year ?? 'Ano Desconhecido' }})
                    </h4>
                    @if ($section->link)
                        <div class="mt-3 embed-responsive">{!! \App\Helpers\EmbedHelper::getEmbed($section) !!}</div>
                    @endif
                @endif

                @if ($section->sectionType->has_details)
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <small class="d-flex align-items-center flex-wrap gap-2">
                            <button class="btn btn-sm btn-primary" data-interaction-type="toggle-reply" data-bs-toggle="collapse" data-bs-target="#sectionComments-{{ $section->id }}">
                                <i class="bi bi-chat-dots"></i> Comentários ({{ $section->comments_count }})
                            </button>

                            <button
                                class="btn btn-sm btn-warning"
                                data-bs-toggle="modal"
                                data-bs-target="#ratingModal"
                                data-rateable-type="section"
                                data-rateable-id="{{ $section->id }}"
                                data-rateable-name="{{ $section->band?->name }} - {{ $section->title }}"
                                data-user-rating="{{ $section->user_rating }}"
                            >
                                <i class="bi bi-star-fill"></i>
                                <span>{{ $section->user_rating > 0 ? 'A tua Avaliação: ' . number_format($section->user_rating, 1) : 'Avaliar' }}</span>
                            </button>

                            <button
                                class="btn btn-sm btn-success"
                                data-interaction-type="listen"
                                data-listenable-type="section"
                                data-url="{{ route('listens.toggle', ['listenableType' => 'section', 'listenableId' => $section->id]) }}"
                            >
                                <i class="bi bi-headphones"></i>
                                <span>{{ $section->user_has_listened ? 'Ouvido' : 'Marcar como ouvido' }}</span>
                            </button>
                        </small>

                        <div class="d-flex align-items-center gap-3 text-muted small">
                            <div class="listen-display" data-bs-toggle="tooltip" data-bs-html="true" title="{{ $section->listens->map(fn($l) => e($l->user->name))->join('<br>') ?: 'Ninguém marcou como ouvido.' }}">
                                <i class="bi bi-headphones"></i>
                                <span class="listens-count">{{ $section->listens_count }}</span>
                            </div>
                            <div class="rating-display" data-bs-toggle="tooltip" data-bs-html="true" title="{{ $section->ratings->map(fn($r) => e($r->user->name) . ': ' . number_format($r->rating, 1))->join('<br>') ?: 'Ainda sem avaliações.' }}">
                                <i class="bi bi-star-fill text-warning"></i>
                                <strong class="average-rating">{{ number_format($section->ratings_avg_rating ?? 0.0, 1) }}</strong>
                                (<span class="ratings-count">{{ $section->ratings_count }}</span>)
                            </div>
                        </div>
                    </div>
                    <div class="collapse mt-3" id="sectionComments-{{ $section->id }}">
                        <x-comments-section :commentable="$section" commentableType="section" />
                    </div>
                @endif
                @if (!$loop->last) <hr class="my-4 border-secondary"> @endif
            </div>
        @empty
            <p class="text-white-50">Esta MetalThursday ainda não tem secções.</p>
        @endforelse
    </div>

    <div class="card-footer text-muted d-flex justify-content-between align-items-center bg-dark">
        <small class="d-flex align-items-center flex-wrap gap-2">
            <button class="btn btn-sm btn-primary" data-interaction-type="toggle-reply" data-bs-toggle="collapse" data-bs-target="#mtComments-{{ $mt->id }}">
                <i class="bi bi-chat-dots"></i> Comentários da MetalThursday ({{ $mt->comments_count }})
            </button>
            <button
                class="btn btn-sm btn-warning"
                data-bs-toggle="modal"
                data-bs-target="#ratingModal"
                data-rateable-type="metalthursday"
                data-rateable-id="{{ $mt->id }}"
                data-rateable-name="MetalThursday de {{ $mt->author?->name }}"
                data-user-rating="{{ $mt->user_rating }}"
            >
                <i class="bi bi-star-fill"></i>
                <span>{{ $mt->user_rating > 0 ? 'A tua Avaliação: ' . number_format($mt->user_rating, 1) : 'Avaliar MetalThursday' }}</span>
            </button>
            <button
                class="btn btn-sm btn-success"
                data-interaction-type="listen"
                data-listenable-type="metalthursday"
                data-url="{{ route('listens.toggle', ['listenableType' => 'metalthursday', 'listenableId' => $mt->id]) }}"
            >
                <i class="bi bi-headphones"></i>
                <span>{{ $mt->user_has_listened ? 'Ouvida' : 'Marcar MetalThursday como Ouvida' }}</span>
            </button>
        </small>
        <div class="d-flex align-items-center gap-3 text-muted small">
             <div class="listen-display" data-bs-toggle="tooltip" data-bs-html="true" title="{{ $mt->listens->map(fn($l) => e($l->user->name))->join('<br>') ?: 'Ninguém marcou como ouvido.' }}">
                <i class="bi bi-headphones"></i>
                <span class="listens-count">{{ $mt->listens_count }}</span>
            </div>
            <div class="rating-display" data-bs-toggle="tooltip" data-bs-html="true" title="{{ $mt->ratings->map(fn($r) => e($r->user->name) . ': ' . number_format($r->rating, 1))->join('<br>') ?: 'Ainda sem avaliações.' }}">
                <i class="bi bi-star-fill text-warning"></i>
                <strong class="average-rating">{{ number_format($mt->ratings_avg_rating ?? 0.0, 1) }}</strong>
                (<span class="ratings-count">{{ $mt->ratings_count }}</span>)
            </div>
        </div>
    </div>
    <div class="collapse mt-1" id="mtComments-{{ $mt->id }}">
        <x-comments-section :commentable="$mt" commentableType="metalthursday" />
    </div>
</div>
