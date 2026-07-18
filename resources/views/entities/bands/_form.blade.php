{{-- Adiciona novalidate ao formulário para desativar a validação do browser --}}
<form
    action="{{ isset($band) ? route('bands.update', $band) : route('bands.store') }}"
    method="POST"
    id="band-form"
    novalidate
>
    @csrf
    @if(isset($band))
        @method('PATCH')
    @endif

    {{-- Campo Nome --}}
    <div class="form-field-group mb-3">
        <label for="name" class="form-label">Nome <span class="text-danger">*</span></label>
        <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $band->name ?? '') }}" placeholder="Nome da banda" required>
        <div class="invalid-feedback">@error('name') {{ $message }} @enderror</div>
    </div>

    {{-- Campo País --}}
    <div class="form-field-group mb-3">
        <label for="country_id" class="form-label">País <span class="text-danger">*</span></label>
        <select name="country_id" id="country_id" class="form-select tom-select-single @error('country_id') is-invalid @enderror" placeholder="Seleciona um país" required>
            <option value="">Seleciona um país</option>
            @foreach($countries as $country)
                <option value="{{ $country->id }}" @selected(old('country_id', $band->country_id ?? '') == $country->id)>
                    {{ $country->name }}
                </option>
            @endforeach
        </select>
        <div class="invalid-feedback">@error('country_id') {{ $message }} @enderror</div>
    </div>

    {{-- Campo Géneros --}}
    <div class="form-field-group mb-3">
        <label for="genres" class="form-label">Géneros <span class="text-danger">*</span></label>
        <select name="genres[]" id="genres" class="form-select tom-select-multiple @error('genres') is-invalid @enderror" placeholder="Seleciona um ou mais géneros" multiple required>
            @php
                $selectedGenres = old('genres', isset($band) ? $band->genres->pluck('id')->all() : []);
            @endphp
            @foreach($genres as $genre)
                <option value="{{ $genre->id }}" @selected(in_array($genre->id, $selectedGenres))>
                    {{ $genre->name }}
                </option>
            @endforeach
        </select>
        <div class="invalid-feedback">@error('genres') {{ $message }} @enderror</div>
    </div>

    <div class="text-end">
        <a href="{{ route('bands.index') }}" class="btn btn-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary">Guardar</button>
    </div>
</form>
