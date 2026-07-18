<x-app-layout>
    <x-slot name="title">
        {{ $genre->name }}
    </x-slot>

    <x-slot name="header">
        <h2 class="h4 font-weight-bold">
            {{ $genre->name }}
        </h2>
        @if($genre->parents->isNotEmpty())
            <p class="text-muted">Subgénero de: {{ $genre->parents->pluck('name')->join(', ') }}</p>
        @endif
    </x-slot>

    <div class="card shadow-sm bg-dark">
        <div class="card-header">
            <h5 class="mb-0">Bandas do Género ({{ $bands->total() }})</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-dark table-striped table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Banda</th>
                            <th>País</th>
                            <th>Outros Géneros</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($bands as $band)
                            <tr>
                                <td>{{ $band->name }}</td>
                                <td>{{ $band->country->name ?? 'N/A' }}</td>
                                <td>{{ $band->genres->where('id', '!=', $genre->id)->pluck('name')->join(', ') ?: '-' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('bands.show', $band) }}" class="btn btn-sm btn-info" title="Visualizar Detalhes da Banda">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-4">Nenhuma banda encontrada para este género.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($bands->hasPages())
            <div class="card-footer bg-dark border-secondary">
                {{ $bands->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
