<x-app-layout>
    <x-slot name="title">{{ $edition->name }}</x-slot>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h2 class="h4 font-weight-bold mb-0">{{ $edition->name }}</h2>
                <p class="text-muted mb-0">Melhores Músicas da Edição</p>
            </div>

            @if ($edition->compilation_link)
                <div>
                    <a href="{{ $edition->compilation_link }}" class="btn btn-primary" target="_blank">
                        <i class="bi bi-play-circle-fill me-2"></i> Ouvir Compilação
                    </a>
                </div>
            @endif
        </div>
    </x-slot>

    <div class="card shadow-sm">
        <div class="card-body">
            <x-session-status/>
            @if ($isLocked)
                <h3 class="h5 mb-4">Resultados Finais:</h3>
                @foreach($users as $user)
                    <div class="mb-4">
                        <h6>{{ $user->name }}</h6>
                        <ul class="list-group">
                            @forelse($rankings->get($user->id, collect()) as $ranking)
                                <li class="list-group-item">{{ $ranking->entry_text }}</li>
                            @empty
                                <li class="list-group-item text-muted">Nenhuma submissão.</li>
                            @endforelse
                        </ul>
                    </div>
                @endforeach
            @else
                <form action="{{ route('editions.rankings.store', $edition) }}" method="POST">
                    @csrf
                    @foreach($users as $user)
                        @php
                            $userEntries = $rankings->get($user->id, collect());
                            $displayEntries = [
                                $userEntries[0]->entry_text ?? '',
                                $userEntries[1]->entry_text ?? '',
                                $userEntries[2]->entry_text ?? '',
                            ];
                        @endphp
                        <div class="mb-4">
                            <h5>{{ $user->name }}</h5>
                            <div class="row">
                                @for ($i = 0; $i < 3; $i++)
                                    <div class="col-md-4 mb-2">
                                        <input type="text" class="form-control" name="rankings[{{ $user->id }}][{{ $i }}]"
                                               value="{{ $displayEntries[$i] }}" placeholder="Banda: Álbum - Título">
                                    </div>
                                @endfor
                            </div>
                        </div>
                    @endforeach
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary">Guardar Rankings</button>
                    </div>
                </form>
            @endif
        </div>
    </div>

    @if (!$edition->compilation_link)
        <div class="card shadow-sm mt-4">
            <div class="card-header">
                <h3 class="h5 mb-0">Link da Compilação Final</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('editions.link.update', $edition) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <div class="form-field-group mb-3">
                        <label for="compilation_link" class="form-label">Link da Playlist</label>
                        <input type="url" name="compilation_link" id="compilation_link" class="form-control @error('compilation_link') is-invalid @enderror" value="{{ old('compilation_link', $edition->compilation_link ?? '') }}" placeholder="Cola aqui o link da compilação">
                        <div class="invalid-feedback">@error('compilation_link') {{ $message }} @enderror</div>
                    </div>
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary">Guardar Link</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</x-app-layout>
