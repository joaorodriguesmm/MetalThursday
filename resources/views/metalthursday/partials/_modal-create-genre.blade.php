<div class="modal fade" id="genre-modal" tabindex="-1" aria-labelledby="genreModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark">
            <div class="modal-header">
                <h5 class="modal-title" id="genreModalLabel">Criar Novo Género</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="create-genre-form" novalidate>
                    <div class="form-field-group mb-3">
                        <label for="new_genre_name" class="form-label">Nome <span class="text-danger">*</span></label>
                        <input
                            type="text"
                            class="form-control @error('name') is-invalid @enderror"
                            name="name"
                            id="new_genre_name"
                            placeholder="Nome do Género"
                            value="{{ old('name') }}"
                            required
                        >
                        <div class="invalid-feedback @error('name') d-block @enderror">
                            @error('name') {{ $message }} @enderror
                        </div>
                    </div>

                    <div class="form-field-group mb-3">
                        <label for="new_genre_parent_ids" class="form-label">Géneros Pai (opcional)</label>
                        <select
                            class="form-select tom-select-multiple"
                            name="parent_genres[]"
                            id="new_genre_parent_ids"
                            placeholder="Seleciona um ou mais géneros pai (se aplicável)"
                            multiple
                        >
                            @foreach($genres as $genre)
                                <option value="{{ $genre->id }}">{{ $genre->name }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback"></div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary" form="create-genre-form">Guardar Género</button>
            </div>
        </div>
    </div>
</div>
