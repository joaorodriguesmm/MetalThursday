<div class="modal fade" id="band-modal" tabindex="-1" aria-labelledby="bandModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark">
            <div class="modal-header">
                <h5 class="modal-title" id="bandModalLabel">Criar Nova Banda</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="create-band-form">
                    <div class="form-field-group mb-3">
                        <label for="new_band_name" class="form-label">Nome <span class="text-danger">*</span></label>
                        <input
                            type="text"
                            class="form-control @error('name') is-invalid @enderror"
                            name="name"
                            id="new_band_name"
                            placeholder="Nome da banda"
                            required
                        >
                        <div class="invalid-feedback @error('name') d-block @enderror">
                            @error('name') {{ $message }} @enderror
                        </div>
                    </div>

                    <div class="form-field-group mb-3">
                        <label for="new_band_country_id" class="form-label">País <span class="text-danger">*</span></label>
                        <select
                            class="form-select tom-select-single @error('country_id') is-invalid @enderror"
                            name="country_id"
                            id="new_band_country_id"
                            placeholder="Seleciona um país"
                            required
                        >
                            <option value="">Seleciona um país</option>
                            @foreach($countries as $country)
                                <option value="{{ $country->id }}">{{ $country->name }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback @error('name') d-block @enderror">
                            @error('name') {{ $message }} @enderror
                        </div>
                    </div>

                    <div class="form-field-group mb-3">
                        <label for="new_band_genres" class="form-label">Género(s) <span class="text-danger">*</span></label>
                         <div class="input-group">
                            <select
                                id="new_band_genres"
                                name="genres[]"
                                class="form-select tom-select-multiple"
                                placeholder="Seleciona o(s) género(s) ou cria um novo"
                                multiple
                                required
                            >
                                @foreach($genres as $genre)
                                    <option value="{{ $genre->id }}">{{ $genre->name }}</option>
                                @endforeach
                            </select>
                            <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#genre-modal" title="Criar Novo Género">+</button>
                        </div>
                        <div class="invalid-feedback"></div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary" form="create-band-form">Guardar Banda</button>
            </div>
        </div>
    </div>
</div>
