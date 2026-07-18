<x-app-layout>
    <x-slot name="title">
        MetalThursday
    </x-slot>

    <x-slot name="header">
        <h2 class="h4 font-weight-bold">
            MetalThursdays
        </h2>
    </x-slot>

    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-lg-9">
                <x-session-status />

                @include('metalthursday.partials._filters', [
                    'viewType' => $viewType,
                    'perPageOptions' => $perPageOptions,
                    'perPage' => $perPage,
                    'availableFilters' => $availableFilters
                ])

                <div id="full-view-container" style="{{ $viewType !== 'full' ? 'display: none;' : '' }}">
                    @if($metalThursdays && $metalThursdays->count() > 0)
                        <div class="mb-4">{{ $metalThursdays->links('vendor.pagination.bootstrap-5') }}</div>
                        @foreach ($metalThursdays as $mt)
                            @include('metalthursday.partials._full-view-card', ['mt' => $mt])
                        @endforeach
                        <div class="mb-4">{{ $metalThursdays->links('vendor.pagination.bootstrap-5') }}</div>
                    @else
                        <div class="alert alert-info">Nenhum resultado encontrado.</div>
                    @endif
                </div>

                <div id="simplified-view-container" style="{{ $viewType !== 'simplified' ? 'display: none;' : '' }}">
                    @include('metalthursday.partials._simplified-view-table', ['simplifiedSections' => $simplifiedSections])
                </div>
            </div>

            <div class="col-lg-3">
                @include('metalthursday.partials._sidebar-actions')
            </div>
        </div>
    </div>

    @include('metalthursday.partials._rating-modal')

    @push('page-scripts')
        <script>
            window.filterData = {
                editions: @json($editions->map(fn($item) => ['id' => $item->id, 'name' => $item->name])),
                authors: @json($users->map(fn($item) => ['id' => $item->id, 'name' => $item->name])),
                bands: @json($bands->map(fn($item) => ['id' => $item->id, 'name' => $item->name])),
                genres: @json($genres->map(fn($item) => ['id' => $item->id, 'name' => $item->name])),
            };
            window.availableFilters = @json(collect($availableFilters)->flatMap(fn($group) => $group)->keyBy('key'));
            window.viewTranslations = {
                full: "{{ config('filters.params.view.values.full') }}",
                simplified: "{{ config('filters.params.view.values.simplified') }}"
            };
        </script>
        @vite('resources/js/pages/home.js')
    @endpush
</x-app-layout>
