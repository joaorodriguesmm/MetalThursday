@props(['viewType', 'perPageOptions', 'perPage', 'availableFilters', 'viewParams'])

<div class="card shadow-sm mb-4 bg-dark">
    <div class="card-body">
        <h5 class="card-title mb-3 text-white">Filtrar e Ordenar</h5>
        <form id="filter-sort-form" action="{{ route('home') }}" method="GET">
            <input type="hidden" name="{{ $viewParams['view']['name'] }}" id="view-type-input" value="{{ $viewType === 'simplified' ? $viewParams['view']['simplified'] : $viewParams['view']['full'] }}">

            <div class="row g-3 align-items-end">
                <div class="col-12 col-md-3 col-lg-3">
                    <label for="add_filter_dropdown" class="form-label small text-muted">Adicionar Filtro</label>
                    <select id="add_filter_dropdown" class="form-select bg-secondary text-white border-secondary">
                        <option value="">Seleciona um filtro</option>
                        @foreach ($availableFilters as $group => $filters)
                            <optgroup label="{{ $group }}">
                                @foreach ($filters as $filter)
                                    <option value="{{ $filter['key'] }}">{{ $filter['label'] }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-3 col-lg-3">
                    <label for="per_page_select" class="form-label small text-muted">Por Página</label>
                    <select id="per_page_select" name="{{ $viewParams['per_page']['name'] }}" class="form-select bg-secondary text-white border-secondary auto-submit">
                        @foreach ($perPageOptions as $option)
                            <option value="{{ $option }}" @selected($perPage == $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-md-3 col-lg-3">
                    <label for="sort_by_select" class="form-label small text-muted">Ordenar por</label>
                    <select id="sort_by_select" name="{{ $viewParams['sort_by']['name'] }}" class="form-select bg-secondary text-white border-secondary auto-submit">
                        @foreach ($viewParams['sort_by']['options'] as $option)
                            <option value="{{ $option['value'] }}" @selected($viewParams['sort_by']['current'] == $option['value'])>{{ $option['label'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-md-3 col-lg-3">
                    <label for="sort_direction_select" class="form-label small text-muted">Ordem</label>
                    <select id="sort_direction_select" name="{{ $viewParams['sort_direction']['name'] }}" class="form-select bg-secondary text-white border-secondary auto-submit">
                        @foreach ($viewParams['sort_direction']['options'] as $option)
                            <option value="{{ $option['value'] }}" @selected($viewParams['sort_direction']['current'] == $option['value'])>{{ $option['label'] }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <hr class="my-4 border-secondary">
            <div id="active-filters-area" class="row g-3"></div>
            <hr class="my-4 border-secondary">

            <div class="d-grid d-md-flex justify-content-md-between align-items-md-center gap-2">
                <button type="button" id="view-toggle-btn" class="btn btn-primary order-md-1">
                    {{ $viewType === 'simplified' ? 'Ver Vista Completa' : 'Ver Vista Simplificada' }}
                </button>
                <div class="d-grid d-md-flex gap-2 order-md-2">
                    <button type="submit" class="btn btn-primary">Aplicar Filtros</button>
                    <a href="{{ route('home', [$viewParams['view']['name'] => ($viewType === 'simplified' ? $viewParams['view']['simplified'] : $viewParams['view']['full'])]) }}" class="btn btn-secondary">Limpar Filtros</a>
                </div>
            </div>
        </form>
    </div>
</div>
