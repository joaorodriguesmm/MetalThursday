<div class="card shadow-sm bg-dark text-white">
    <div class="card-header border-secondary">
        <h5 class="text-white mb-0">Ações</h5>
    </div>
    <div class="list-group list-group-flush">
        <a href="{{ route('metalthursday.create') }}" class="list-group-item list-group-item-action bg-dark text-white border-secondary">
            <i class="bi bi-plus-circle me-2"></i> Criar MetalThursday
        </a>
        <a href="{{ route('bands.index') }}" class="list-group-item list-group-item-action bg-dark text-white border-secondary">
            <i class="bi bi-music-note-beamed me-2"></i> Bandas
        </a>
        <a href="{{ route('editions.index') }}" class="list-group-item list-group-item-action bg-dark text-white border-secondary">
            <i class="bi bi-collection me-2"></i> Edições
        </a>
        <a href="{{ route('genres.index') }}" class="list-group-item list-group-item-action bg-dark text-white border-secondary">
            <i class="bi bi-tags me-2"></i> Géneros
        </a>
    </div>
</div>
