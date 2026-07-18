<x-app-layout>
    <x-slot name="title">{{ $band->name }}</x-slot>
    <x-slot name="header">
        <h2 class="h4 font-weight-bold">
            {{ $band->name }} <span class="h5">({{ $band->country->name }})</span>
        </h2>
        <p class="text-muted">{{ $band->genres->pluck('name')->join(', ') }}</p>
    </x-slot>

    <h3 class="h5 mb-3">Aparições em MetalThursdays ({{ $sections->count() }})</h3>

    @forelse($sections as $section)
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <h5 class="card-title accent-red">{{ $section->title }} ({{ $section->year }})</h5>
                <h6 class="card-subtitle mb-2 text-muted">
                    {{ $section->sectionType->name }} na
                    <a href="{{ route('metalthursday.show', $section->metalThursday) }}">
                        MetalThursday de {{ $section->metalThursday->author->name }}
                    </a>
                    ({{ $section->metalThursday->date->format('d/m/Y') }})
                </h6>
                <p class="card-text small fst-italic">"{!! nl2br(e($section->description)) !!}"</p>
                <a href="{{ $section->link }}" target="_blank" class="btn btn-sm btn-secondary">Abrir link Externo</a>
            </div>
        </div>
    @empty
        <div class="alert alert-info">Esta banda ainda não apareceu em nenhuma secção.</div>
    @endforelse
</x-app-layout>
