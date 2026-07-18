<div class="modal fade" id="edition-modal" tabindex="-1" aria-labelledby="editionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark">
            <div class="modal-header">
                <h5 class="modal-title" id="editionModalLabel">Criar Nova Edição</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="create-edition-form">
                    <div class="form-field-group mb-3">
                        <div class="form-floating">
                            <input
                                type="text"
                                class="form-control @error('name') is-invalid @enderror"
                                id="new_edition_name"
                                name="name"
                                placeholder="Nome da Edição"
                                value="{{ old('name') }}"
                                required
                            >
                            <label for="new_edition_name">Nome da Edição <span class="text-danger">*</span></label>
                        </div>
                        <div class="invalid-feedback @error('name') d-block @enderror">
                            @error('name') {{ $message }} @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 form-field-group mb-3">
                            <div class="form-floating">
                                <input
                                    type="date"
                                    class="form-control @error('start_date') is-invalid @enderror"
                                    id="new_edition_start_date"
                                    name="start_date"
                                    placeholder="Data de Início"
                                    value="{{ old('start_date') }}"
                                    required
                                >
                                <label for="new_edition_start_date">Data de Início <span class="text-danger">*</span></label>
                            </div>
                            <div class="invalid-feedback @error('start_date') d-block @enderror">
                                @error('start_date') {{ $message }} @enderror
                            </div>
                        </div>

                        <div class="col-md-6 form-field-group mb-3">
                            <div class="form-floating">
                                <input
                                    type="date"
                                    class="form-control @error('end_date') is-invalid @enderror"
                                    id="new_edition_end_date"
                                    name="end_date"
                                    placeholder="Data de Fim"
                                    value="{{ old('end_date') }}"
                                >
                                <label for="new_edition_end_date">Data de Fim (opcional)</label>
                            </div>
                            <div class="invalid-feedback @error('end_date') d-block @enderror">
                                @error('end_date') {{ $message }} @enderror
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary" form="create-edition-form">Guardar Edição</button>
            </div>
        </div>
    </div>
</div>
