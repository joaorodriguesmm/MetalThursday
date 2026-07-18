<x-app-layout>
    <x-slot name="title">
        Criar MetalThursday
    </x-slot>

    <x-slot name="header">
        <h2 class="h4 font-weight-bold">
            Criar MetalThursday
        </h2>
    </x-slot>

    <div class="container my-4">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <form action="{{ route('metalthursday.store') }}" method="POST" id="create-metalthursday-form">
                    @csrf

                    @include('metalthursday.partials._form-main-fields')

                    <hr class="my-4">

                    <h4 class="h5">Secções da MetalThursday</h4>
                    <div id="sections-container">
                        @if(is_array(old('sections')))
                            @foreach(old('sections') as $index => $oldSection)
                                @include('metalthursday.partials._section-item', ['index' => $index, 'section' => $oldSection])
                            @endforeach
                        @endif
                    </div>
                    <div id="sections-validation-feedback" class="invalid-feedback"></div>

                    <button type="button" id="add-section-btn" class="btn btn-secondary mt-2">
                        <i class="bi bi-plus-lg"></i> Adicionar Secção
                    </button>

                    <div class="mt-4 text-end">
                        <button type="submit" class="btn btn-primary btn-lg">Guardar MetalThursday</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <template id="section-template">
        @include('metalthursday.partials._section-item', ['index' => '__INDEX__'])
    </template>

    @include('metalthursday.partials._modal-create-edition')
    @include('metalthursday.partials._modal-create-band')
    @include('metalthursday.partials._modal-create-genre')

    @push('page-scripts')
        <script>
            window.editionStoreUrl = "{{ route('editions.store') }}";
            window.bandStoreUrl = "{{ route('bands.store') }}";
            window.genreStoreUrl = "{{ route('genres.store') }}";
            window.longestNotNominatedUrl = "{{ route('users.longest-not-nominated') }}";
            window.embedProviders = {!! \App\Helpers\EmbedHelper::getJsDefinitions() !!};
        </script>
        @vite(['resources/js/pages/createMetalThursday.js'])
    @endpush
</x-app-layout>
