@props(['index', 'section' => [], 'sectionTypes' => $sectionTypes ?? [], 'bands' => $bands ?? []])

<div class="section-item border rounded p-3 mb-3 bg-light bg-opacity-10 position-relative">
    <input type="hidden" name="sections[{{ $index }}][id]" value="{{ $section['id'] ?? '' }}">
    <div class="row">
        <div class="col-12 form-field-group mb-3">
            @php $errorKey = 'sections.' . $index . '.type_id'; @endphp
            <label for="sections-{{ $index }}-type_id" class="form-label">
                <strong>Tipo de Secção <span class="text-danger">*</span></strong>
                <i class="bi bi-info-circle" data-bs-toggle="tooltip" title="Se é apenas texto, álbum ou música."></i>
            </label>
            <select name="sections[{{ $index }}][type_id]" id="sections-{{ $index }}-type_id" class="form-select tom-select-single section-type-select @error($errorKey) is-invalid @enderror" required>
                <option value="">Seleciona um tipo</option>
                @foreach ($sectionTypes as $type)
                    <option value="{{ $type->id }}" data-has-details="{{ $type->has_details ? 'true' : 'false' }}" @selected(old('sections.'.$index.'.type_id', $section['section_type_id'] ?? '') == $type->id)>
                        {{ $type->name }}
                    </option>
                @endforeach
            </select>
            <div class="invalid-feedback @error($errorKey) d-block @enderror">@error($errorKey) {{ $message }} @enderror</div>
        </div>
    </div>

    @php
        $selectedTypeId = old('sections.' . $index . '.type_id', $section['section_type_id'] ?? null);
        $selectedType = $selectedTypeId ? collect($sectionTypes)->firstWhere('id', (int)$selectedTypeId) : null;
        $hasDetails = $selectedType && $selectedType->has_details;
    @endphp

    <div class="row section-details-row-1" style="{{ $hasDetails ? '' : 'display: none;' }}">
        <div class="col-md-6 form-field-group mb-3">
            @php $errorKey = 'sections.' . $index . '.band_id'; @endphp
            <label for="sections-{{ $index }}-band_id" class="form-label">
                <strong>Banda <span class="text-danger">*</span></strong>
                <i class="bi bi-info-circle" data-bs-toggle="tooltip" title="Banda da secção."></i>
            </label>
            <div class="input-group">
                <select name="sections[{{ $index }}][band_id]" id="sections-{{ $index }}-band_id" class="form-select tom-select-single tom-select-bands @error($errorKey) is-invalid @enderror">
                    <option value="">Seleciona uma banda</option>
                    @foreach ($bands as $band)
                         <option value="{{ $band->id }}" @selected(old('sections.'.$index.'.band_id', $section['band_id'] ?? '') == $band->id)>{{ $band->name }}</option>
                    @endforeach
                </select>
                <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#band-modal" title="Criar Nova Banda">+</button>
            </div>
            <div class="invalid-feedback @error($errorKey) d-block @enderror">@error($errorKey) {{ $message }} @enderror</div>
        </div>

        <div class="col-md-6 form-field-group mb-3 section-title-col">
            @php $errorKey = 'sections.' . $index . '.title'; @endphp
            <label for="sections-{{ $index }}-title" class="form-label">
                <strong>Título <span class="text-danger title-required-indicator" style="{{ !$hasDetails ? 'display: none;' : '' }}">*</span></strong>
                <i class="bi bi-info-circle" data-bs-toggle="tooltip" title="Título do álbum ou música."></i>
            </label>
            <input type="text" id="sections-{{ $index }}-title" name="sections[{{ $index }}][title]" class="form-control @error($errorKey) is-invalid @enderror" value="{{ old('sections.' . $index . '.title', $section['title'] ?? '') }}" {{ $hasDetails ? 'required' : '' }}>
            <div class="invalid-feedback @error($errorKey) d-block @enderror">@error($errorKey) {{ $message }} @enderror</div>
        </div>
    </div>

    <div class="row section-details-row-2" style="{{ $hasDetails ? '' : 'display: none;' }}">
        <div class="col-md-6 form-field-group mb-3">
            @php $errorKey = 'sections.' . $index . '.link'; @endphp
            <label for="sections-{{ $index }}-link" class="form-label">
                <strong>Link <span class="text-danger">*</span></strong>
                <i class="bi bi-info-circle" data-bs-toggle="tooltip" title="Link para podermos ouvir o álbum ou música."></i>
            </label>
            <div class="input-group">
                <input type="url" id="sections-{{ $index }}-link" name="sections[{{ $index }}][link]" class="form-control link-input @error($errorKey) is-invalid @enderror" value="{{ old('sections.' . $index . '.link', $section['link'] ?? '') }}">
                <button class="btn btn-secondary link-test-btn" type="button">Testar Embed</button>
            </div>
            <div class="invalid-feedback @error($errorKey) d-block @enderror">@error($errorKey) {{ $message }} @enderror</div>
            <input type="hidden" name="sections[{{ $index }}][embed_type]" class="embed-type-input" value="{{ old('sections.'.$index.'.embed_type', $section['embed_type'] ?? 'link') }}">

            <div class="link-test-results mt-3 border-top pt-3" style="display: none;">
                <div class="test-status small mb-2"></div>
                <div class="video-option mb-3" style="display: none;">
                    <div class="form-check">
                        <input class="form-check-input embed-choice-radio" type="radio" name="embed_choice_{{ $index }}" id="choice_video_{{ $index }}" value="youtube_video">
                        <label class="form-check-label" for="choice_video_{{ $index }}"><strong>Opção 1: Usar como Vídeo</strong></label>
                    </div>
                    <div class="embed-preview-container video-preview-container mt-2"></div>
                </div>
                <div class="playlist-option mb-3" style="display: none;">
                    <div class="form-check">
                        <input class="form-check-input embed-choice-radio" type="radio" name="embed_choice_{{ $index }}" id="choice_playlist_{{ $index }}" value="youtube_playlist">
                        <label class="form-check-label" for="choice_playlist_{{ $index }}"><strong>Opção 2: Usar como Playlist</strong></label>
                    </div>
                    <div class="embed-preview-container playlist-preview-container mt-2"></div>
                </div>
                <div class="link-option">
                    <div class="form-check">
                        <input class="form-check-input embed-choice-radio" type="radio" name="embed_choice_{{ $index }}" id="choice_link_{{ $index }}" value="link" checked>
                        <label class="form-check-label" for="choice_link_{{ $index }}">Nenhuma das opções acima funciona, usar como Link Simples.</label>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 form-field-group mb-3">
            @php $errorKey = 'sections.' . $index . '.year'; @endphp
            <label for="sections-{{ $index }}-year" class="form-label">
                <strong>Ano <span class="text-danger">*</span></strong>
                <i class="bi bi-info-circle" data-bs-toggle="tooltip" title="Ano de lançamento do álbum ou música."></i>
            </label>
            <input type="number" id="sections-{{ $index }}-year" name="sections[{{ $index }}][year]" class="form-control @error($errorKey) is-invalid @enderror" value="{{ old('sections.'.$index.'.year', $section['year'] ?? '') }}" min="1900" max="{{ date('Y') }}">
            <div class="invalid-feedback @error($errorKey) d-block @enderror">@error($errorKey) {{ $message }} @enderror</div>
        </div>
    </div>

    <div class="form-field-group mb-3">
        @php $errorKey = 'sections.' . $index . '.description'; @endphp
        <label for="sections-{{ $index }}-description" class="form-label">
            <strong>Descrição <span class="text-danger">*</span></strong>
            <i class="bi bi-info-circle" data-bs-toggle="tooltip" title="Descrição da secção."></i>
        </label>
        <textarea id="sections-{{ $index }}-description" name="sections[{{ $index }}][description]" class="form-control @error($errorKey) is-invalid @enderror" rows="3" required>{{ old('sections.'.$index.'.description', $section['description'] ?? '') }}</textarea>
        <div class="invalid-feedback @error($errorKey) d-block @enderror">@error($errorKey) {{ $message }} @enderror</div>
    </div>

    <button type="button" class="btn btn-sm btn-outline-danger remove-section-btn">Remover Secção</button>
</div>
