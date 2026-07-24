<x-layout-aplicacao>
    <x-slot name="title">Criar Banda</x-slot>
    <x-slot name="header">
        <h2 class="h4 font-weight-bold">Criar Nova Banda</h2>
    </x-slot>

    <div class="card shadow-sm">
        <div class="card-body">
            @include('entities.bands._form')
        </div>
    </div>

    @push('page-scripts')
        @vite('resources/js/pages/entities.js')
    @endpush
</x-layout-aplicacao>
