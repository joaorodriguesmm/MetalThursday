<x-app-layout>
    <x-slot name="title">
        {{ $mt->edition?->name }} - Semana {{ $mt->week_number_in_edition ?? 'N/A' }}
    </x-slot>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-lg-12">
                <x-session-status />
                @include('metalthursday.partials._full-view-card', ['mt' => $mt])
            </div>
        </div>
    </div>

    @include('metalthursday.partials._rating-modal')

    @push('page-scripts')
        @vite(['resources/js/pages/showMetalThursday.js'])
    @endpush
</x-app-layout>
