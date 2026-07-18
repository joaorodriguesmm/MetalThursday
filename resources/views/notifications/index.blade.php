<x-app-layout>
    <x-slot name="title">
        Notificações
    </x-slot>

    <x-slot name="header">
        <h2 class="h4 font-weight-bold">
            Minhas Notificações
        </h2>
    </x-slot>

    <div class="container-fluid mt-4">
        <div class="row justify-content-center">
            <div class="col-md-12">
                <div class="card shadow-sm mb-4 bg-dark text-white">
                    <div class="card-header d-flex justify-content-between align-items-center border-secondary">
                        <h5 class="mb-0">Lista de Notificações</h5>
                        @if(Auth::user()->unreadNotifications->count() > 0)
                            <form action="{{ route('notifications.markAllAsRead') }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-secondary">Marcar todas como lidas</button>
                            </form>
                        @endif
                    </div>
                    <div class="card-body p-0">
                        @forelse ($notifications as $notification)
                            <div class="list-group-item bg-dark text-white {{ $notification->read_at ? 'text-muted' : 'font-weight-bold' }} pe-2 ps-2" style="border-bottom: 1px solid var(--bs-secondary);">
                                <div class="d-flex w-100 justify-content-between align-items-center py-2">
                                    <div class="d-flex align-items-center">
                                        <i class="bi {{ $notification->data['icon'] ?? 'bi-info-circle' }} fs-4 me-3 {{ $notification->data['color'] ?? 'text-info' }}"></i>

                                        <div>
                                            <h6 class="mb-1">{{ $notification->data['title'] ?? 'Nova Notificação' }}</h6>
                                            <p class="mb-1 small">{{ $notification->data['message'] ?? 'Você tem uma nova notificação.' }}</p>
                                            <small class="text-muted">{{ $notification->created_at->diffForHumans() }}</small>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        @if (!$notification->read_at)
                                            <form action="{{ route('notifications.markAsRead', $notification->id) }}" method="POST" class="me-2">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-success" title="Marcar como lida">
                                                    <i class="bi bi-check-circle"></i>
                                                </button>
                                            </form>
                                        @endif
                                        @if (isset($notification->data['url']))
                                            <a href="{{ $notification->data['url'] }}" class="btn btn-sm btn-outline-info" title="Ver detalhes">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <p class="p-3 text-center text-muted">Não tens notificações.</p>
                        @endforelse
                    </div>
                    <div class="card-footer bg-dark border-secondary">
                        {{ $notifications->links('vendor.pagination.bootstrap-5') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
