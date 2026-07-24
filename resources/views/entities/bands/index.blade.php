<x-layout-aplicacao>
    <x-slot name="title">
        Bandas
    </x-slot>

    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="h4 font-weight-bold mb-0">
                Bandas
            </h2>
            @can('create', App\Models\Band::class)
                <a href="{{ route('bands.create') }}" class="btn btn-primary">Adicionar Banda</a>
            @endcan
        </div>
    </x-slot>

    <div class="card shadow-sm bg-dark">
        <div class="card-body">
            <x-session-status />
            <form action="{{ route('bands.index') }}" method="GET" class="mb-3">
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="Pesquisa por nome" value="{{ request('search') }}">
                    <button class="btn btn-secondary" type="submit">Pesquisar</button>
                </div>
            </form>
            <div class="table-responsive">
                <table class="table table-dark table-striped table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>País</th>
                            <th>Géneros</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($bands as $band)
                            <tr>
                                <td>{{ $band->name }}</td>
                                <td>{{ $band->country->name ?? 'N/A' }}</td>
                                <td>
                                    {{ $band->genres->pluck('name')->join(', ') }}
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('bands.show', $band) }}" class="btn btn-sm btn-info" title="Visualizar"><i class="bi bi-eye"></i></a>
                                    @can('update', $band)
                                        <a href="{{ route('bands.edit', $band) }}" class="btn btn-sm btn-secondary" title="Editar">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                    @endcan
                                    @can('delete', $band)
                                        <form action="{{ route('bands.destroy', $band) }}" method="POST" class="d-inline" onsubmit="return confirm('Tens a certeza que desejas eliminar esta banda?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" title="Apagar">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-4">
                                    Ainda não foram criadas bandas.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($bands->hasPages())
            {{-- CORREÇÃO: Paginação movida para um card-footer para melhor estética --}}
            <div class="card-footer bg-dark border-secondary">
                {{ $bands->links() }}
            </div>
        @endif
    </div>

    @push('page-scripts')
        @vite('resources/js/pages/entities.js')
    @endpush
</x-layout-aplicacao>
