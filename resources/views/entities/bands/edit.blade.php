<x-app-layout>
    <x-slot name="title">Editar Banda</x-slot>
    <x-slot name="header">
        <h2 class="h4 font-weight-bold">Editar Banda: {{ $band->name }}</h2>
    </x-slot>

    <div class="card shadow-sm">
        <div class="card-body">
            @include('entities.bands._form')
        </div>
    </div>

    @push('page-scripts')
        @vite('resources/js/pages/entities.js')
    @endpush
</x-app-layout>
