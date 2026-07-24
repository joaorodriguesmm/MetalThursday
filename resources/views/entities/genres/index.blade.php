<x-layout-aplicacao>
    <x-slot name="title">Gerir Géneros</x-slot>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="h4 font-weight-bold mb-0">Gerir Géneros</h2>
            @can('create', App\Models\Genre::class)
                <a href="{{ route('genres.create') }}" class="btn btn-primary">Adicionar Género</a>
            @endcan
        </div>
    </x-slot>

    <div class="card shadow-sm bg-dark">
        <div class="card-body">
            <x-session-status />
            <form action="{{ route('genres.index') }}" method="GET" class="mb-3">
                <div class="input-group">
                    <input type="text" name="search" class="form-control bg-dark text-white border-secondary" placeholder="Pesquisa por nome" value="{{ request('search') }}">
                    <button class="btn btn-secondary" type="submit">Pesquisar</button>
                </div>
            </form>
            <div class="table-responsive">
                <table class="table table-dark table-striped table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Géneros Pai</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($genres as $genre)
                            <tr>
                                <td>{{ $genre->name }}</td>
                                <td>{{ $genre->parents->pluck('name')->join(', ') ?: '-' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('genres.show', $genre) }}" class="btn btn-sm btn-info" title="Visualizar"><i class="bi bi-eye"></i></a>
                                    @can('update', $genre)
                                        <a href="{{ route('genres.edit', $genre) }}" class="btn btn-sm btn-secondary" title="Editar"><i class="bi bi-pencil-square"></i></a>
                                    @endcan
                                    @can('delete', $genre)
                                        <form action="{{ route('genres.destroy', $genre) }}" method="POST" class="d-inline" onsubmit="return confirm('Tens a certeza que desejas eliminar este género?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" title="Apagar"><i class="bi bi-trash"></i></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center py-4">Ainda não foram criados géneros.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($genres->hasPages())
            <div class="card-footer bg-dark border-secondary">
                {{ $genres->links() }}
            </div>
        @endif
    </div>

    @push('page-scripts')
        @vite('resources/js/pages/entities.js')
    @endpush
</x-layout-aplicacao>
