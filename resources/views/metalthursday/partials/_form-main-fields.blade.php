@props(['mt' => null])

<div class="row">
    <div class="col-md-4 form-field-group mb-3">
        <label for="edition_id" class="form-label">
            <strong>Edição <span class="text-danger">*</span></strong>
            <i class="bi bi-info-circle" data-bs-toggle="tooltip" title="Edição a que a MetalThursday pertence."></i>
        </label>
        <div class="input-group">
            <select name="edition_id" id="edition_id" class="form-select tom-select-single @error('edition_id') is-invalid @enderror" placeholder="Seleciona uma edição ou cria uma nova" required>
                <option value="">Seleciona uma edição...</option>
                @foreach ($editions as $edition)
                    <option value="{{ $edition->id }}" @selected(old('edition_id', $mt?->edition_id) == $edition->id)>
                        {{ $edition->display_text }}
                    </option>
                @endforeach
            </select>
            <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#edition-modal" title="Criar Nova Edição">
                <i class="bi bi-plus-lg"></i>
            </button>
        </div>
        <div class="invalid-feedback @error('edition_id') d-block @enderror">@error('edition_id') {{ $message }} @enderror</div>
    </div>

    <div class="col-md-4 form-field-group mb-3">
        <label for="date" class="form-label">
            <strong>Data <span class="text-danger">*</span></strong>
            <i class="bi bi-info-circle" data-bs-toggle="tooltip" title="Data da MetalThursday."></i>
        </label>
        <input type="date" name="date" id="date" class="form-control @error('date') is-invalid @enderror" value="{{ old('date', $mt?->date->format('Y-m-d')) }}" required>
        <div class="invalid-feedback @error('date') d-block @enderror">@error('date') {{ $message }} @enderror</div>
    </div>

    <div class="col-md-4 form-field-group mb-3">
        <label for="name" class="form-label">
            Nome (Apenas para edições especiais)
            <i class="bi bi-info-circle" data-bs-toggle="tooltip" title="Apenas preencher se for uma MetalThursday especial (Ex: Especial de Natal)."></i>
        </label>
        <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $mt?->name) }}" placeholder="Ex: Especial de Natal">
        <div class="invalid-feedback @error('name') d-block @enderror">@error('name') {{ $message }} @enderror</div>
    </div>
</div>

<div class="row">
    <div class="col-md-6 form-field-group mb-3">
        <label for="author_id" class="form-label">
            <strong>Autor <span class="text-danger">*</span></strong>
            <i class="bi bi-info-circle" data-bs-toggle="tooltip" title="Quem é o autor desta MetalThursday?"></i>
        </label>
        <select name="author_id" id="author_id" class="form-select tom-select-single @error('author_id') is-invalid @enderror" placeholder="Seleciona o autor" required>
            <option value="">Seleciona o autor</option>
            @foreach ($users as $user)
                <option value="{{ $user->id }}" @selected(old('author_id', $mt?->author_id) == $user->id)>
                    {{ $user->name }}
                </option>
            @endforeach
        </select>
        <div class="invalid-feedback @error('author_id') d-block @enderror">@error('author_id') {{ $message }} @enderror</div>
    </div>

    <div class="col-md-6 form-field-group mb-3">
        <label for="next_nominee_id" class="form-label">
            <strong>Próximo Nomeado <span class="text-danger">*</span></strong>
            <i class="bi bi-info-circle" data-bs-toggle="tooltip" title="Quem será o nomeado para a próxima MetalThursday? Se não sabes quem nomear usa um dos botões para te ajudar a decidir."></i>
        </label>
        <div class="input-group">
            <select name="next_nominee_id" id="next_nominee_id" class="form-select tom-select-single @error('next_nominee_id') is-invalid @enderror" placeholder="Seleciona o próximo nomeado ou usa um dos botões ao lado" required>
                <option value="">Seleciona o próximo nomeado ou usa um dos botões ao lado</option>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}" @selected(old('next_nominee_id', $mt?->next_nominee_id) == $user->id)>
                        {{ $user->name }}
                    </option>
                @endforeach
            </select>
            <button id="select-random-nominee" class="btn btn-secondary" type="button" title="Selecionar metaleiro aleatório."><i class="bi bi-shuffle"></i></button>
            <button id="select-oldest-nominee" class="btn btn-secondary" type="button" title="Selecionar o metaleiro que não é nomeado há mais tempo."><i class="bi bi-calendar-x"></i></button>
        </div>
        <div class="invalid-feedback @error('next_nominee_id') d-block @enderror">@error('next_nominee_id') {{ $message }} @enderror</div>
    </div>
</div>
