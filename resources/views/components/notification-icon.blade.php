@props(['count'])

<a class="nav-link text-white position-relative notification-icon-wrapper" href="{{ route('notifications.index') }}">
    <i class="bi bi-bell"></i>
    @if ($count > 0)
        <span class="position-absolute top-0 start-100 translate-middle badge rounded-circle bg-danger text-white notification-badge">
            {{ $count }}
            <span class="visually-hidden">notificações não lidas</span>
        </span>
    @endif
</a>
