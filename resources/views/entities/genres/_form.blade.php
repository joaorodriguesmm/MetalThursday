<form
    action="{{ isset($genre) ? route('genres.update', $genre) : route('genres.store') }}"
    method="POST"
    id="genre-form"
    novalidate
>
    @csrf
    @if(isset($genre))
        @method('PATCH')
    @endif

    <div class="form-field-group mb-3">
        <label for="name" class="form-label">Nome <span class="text-danger">*</span></label>
        <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $genre->name ?? '') }}" required>
        <div class="invalid-feedback">@error('name') {{ $message }} @enderror</div>
    </div>

    <div class="form-field-group mb-3">
        <label for="parent_genres" class="form-label">Géneros Pai (opcional)</label>
        <select name="parent_genres[]" id="parent_genres" class="form-select tom-select-multiple @error('parent_genres') is-invalid @enderror" placeholder="Seleciona um ou mais géneros" multiple>
            @php
                $selectedGenres = old('parent_genres', isset($genre) ? $genre->parents->pluck('id')->all() : []);
            @endphp
            @foreach($genres as $parentGenre)
                <option value="{{ $parentGenre->id }}" @selected(in_array($parentGenre->id, $selectedGenres))>
                    {{ $parentGenre->name }}
                </option>
            @endforeach
        </select>
        <div class="invalid-feedback">@error('parent_genres') {{ $message }} @enderror</div>
    </div>

    <div class="text-end">
        <a href="{{ route('genres.index') }}" class="btn btn-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary">Guardar</button>
    </div>
</form>
