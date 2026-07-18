@props(['simplifiedSections'])

@if($simplifiedSections)
    <div class="card shadow-sm bg-dark">
        <div class="table-responsive">
            <table class="table table-dark table-striped table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Autor</th>
                        <th>Banda</th>
                        <th>País</th>
                        <th>Título</th>
                        <th>Ano</th>
                        <th>Género</th>
                        <th class="text-center">Link</th>
                        <th class="text-center">Avaliação</th>
                        <th class="text-center">Ouvido</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($simplifiedSections as $section)
                        <tr>
                            <td>{{ $section->metalThursday->date->format('d/m/Y') }}</td>
                            <td>{{ $section->metalThursday->author?->name }}</td>
                            <td>{{ $section->band?->name }}</td>
                            <td>{{ $section->band?->country?->name }}</td>
                            <td>{{ $section->title }} <span class="text-white-50">({{ $section->sectionType?->name }})</span></td>
                            <td>{{ $section->year }}</td>
                            <td>
                                @if($section->band?->genres->isNotEmpty())
                                    {{ $section->band->genres->pluck('name')->join(', ') }}
                                @endif
                            </td>
                            <td class="text-center">
                                @if($section->link)
                                    <a href="{{ $section->link }}" target="_blank" class="btn btn-sm btn-outline-light">Abrir</a>
                                @endif
                            </td>
                            <td class="text-center">
                                @php $ratingTooltip = $section->ratings->isNotEmpty() ? $section->ratings->map(fn($r) => e($r->user->name) . ': ' . number_format($r->rating, 1))->join('<br>') : 'Ainda sem avaliações.'; @endphp
                                <span data-bs-toggle="tooltip" data-bs-html="true" title="{{ $ratingTooltip }}">
                                    <i class="bi bi-star-fill text-warning"></i>
                                    {{ number_format($section->ratings_avg_rating ?? 0.0, 1) }}
                                </span>
                            </td>
                            <td class="text-center">
                                @php $listenTooltip = $section->listens->isNotEmpty() ? $section->listens->map(fn($l) => e($l->user->name))->join('<br>') : 'Ninguém marcou como ouvido.'; @endphp
                                <span data-bs-toggle="tooltip" data-bs-html="true" title="{{ $listenTooltip }}">
                                    <i class="bi bi-headphones"></i>
                                    {{ $section->listens_count }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-4">Nenhum resultado encontrado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-4">
        {{ $simplifiedSections->links('vendor.pagination.bootstrap-5') }}
    </div>
@endif
