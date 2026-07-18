<x-app-layout>
    <x-slot name="title">Gerir Edições</x-slot>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="h4 font-weight-bold mb-0">Gerir Edições</h2>
            @can('create', App\Models\MtEdition::class)
                <a href="{{ route('editions.create') }}" class="btn btn-primary">Adicionar Edição</a>
            @endcan
        </div>
    </x-slot>

    <div class="card shadow-sm bg-dark">
        <div class="card-body">
            <x-session-status />
            <div class="table-responsive">
                <table class="table table-dark table-striped table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Data de Início</th>
                            <th>Data de Fim</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($editions as $edition)
                            <tr>
                                <td>{{ $edition->name }}</td>
                                <td>{{ $edition->start_date->format('d/m/Y') }}</td>
                                <td>{{ $edition->end_date ? $edition->end_date->format('d/m/Y') : 'Atualmente' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('editions.show', $edition) }}" class="btn btn-sm btn-info" title="Ver/Gerir Melhores Músicas">
                                        <i class="bi bi-trophy-fill"></i>
                                    </a>
                                    @can('update', $edition)
                                        <a href="{{ route('editions.edit', $edition) }}" class="btn btn-sm btn-secondary" title="Editar"><i class="bi bi-pencil-square"></i></a>
                                    @endcan
                                    @can('delete', $edition)
                                        <form action="{{ route('editions.destroy', $edition) }}" method="POST" class="d-inline" onsubmit="return confirm('Tens a certeza que desejas eliminar esta edição?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" title="Apagar"><i class="bi bi-trash"></i></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-4">Ainda não foram criadas edições.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($editions->hasPages())
            <div class="card-footer bg-dark border-secondary">
                {{ $editions->links() }}
            </div>
        @endif
    </div>

    @push('page-scripts')
        @vite('resources/js/pages/entities.js')
    @endpush
</x-app-layout>
