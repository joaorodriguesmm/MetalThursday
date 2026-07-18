<form
    action="{{ isset($edition) ? route('editions.update', $edition) : route('editions.store') }}"
    method="POST"
    id="edition-form"
    novalidate
>
    @csrf
    @if(isset($edition))
        @method('PATCH')
    @endif

    <div class="form-field-group mb-3">
        <label for="name" class="form-label">Nome <span class="text-danger">*</span></label>
        <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $edition->name ?? '') }}" required>
        <div class="invalid-feedback">@error('name') {{ $message }} @enderror</div>
    </div>

    <div class="row">
        <div class="col-md-6 form-field-group mb-3">
            <label for="start_date" class="form-label">Data de Início <span class="text-danger">*</span></label>
            <input type="date" name="start_date" id="start_date" class="form-control @error('start_date') is-invalid @enderror" value="{{ old('start_date', isset($edition) ? $edition->start_date->format('Y-m-d') : '') }}" required>
            <div class="invalid-feedback">@error('start_date') {{ $message }} @enderror</div>
        </div>
        <div class="col-md-6 form-field-group mb-3">
            <label for="end_date" class="form-label">Data de Fim (opcional)</label>
            <input type="date" name="end_date" id="end_date" class="form-control @error('end_date') is-invalid @enderror" value="{{ old('end_date', isset($edition) && $edition->end_date ? $edition->end_date->format('Y-m-d') : '') }}">
            <div class="invalid-feedback">@error('end_date') {{ $message }} @enderror</div>
        </div>
    </div>

    <div class="text-end">
        <a href="{{ route('editions.index') }}" class="btn btn-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary">Guardar</button>
    </div>
</form>
