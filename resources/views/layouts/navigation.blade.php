<nav class="navbar navbar-expand-md navbar-dark bg-dark border-bottom border-secondary shadow-sm">
    <div class="container">
        <a class="navbar-brand" href="{{ route('home') }}">
            <img src="{{ asset('images/logo.png') }}" alt="{{ config('app.name', 'MetalThursday') }}" height="60">
        </a>

        <ul class="navbar-nav flex-row d-md-none ms-auto align-items-center">
            <li class="nav-item me-3">
                <x-notification-icon :count="$unreadNotificationsCount" />
            </li>
        </ul>

        <button class="navbar-toggler ms-3" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}">Início</a>
                </li>

                <li class="nav-item d-md-none"><hr></li>
                <li class="nav-item d-md-none">
                    <a class="nav-link {{ request()->routeIs('profile.edit') ? 'active' : '' }}" href="{{ route('perfil.editar') }}">Editar Perfil</a>
                </li>
                <li class="nav-item d-md-none">
                    <a class="nav-link logout-link" id="mobile-logout-link" href="#">Sair</a>
                </li>
            </ul>

            <ul class="navbar-nav ms-auto d-none d-md-flex align-items-center">
                <li class="nav-item me-3">
                    <x-notification-icon :count="$unreadNotificationsCount" />
                </li>

                <li class="nav-item dropdown">
                    <a id="navbarDropdownDesktop" class="nav-link dropdown-toggle p-0" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                        <div class="profile-pic-wrapper position-relative d-flex align-items-center justify-content-center">
                            <x-avatar :user="Auth::user()" :size="40" />
                            <span class="dropdown-indicator position-absolute bottom-0 end-0 bg-dark rounded-circle d-flex align-items-center justify-content-center">
                                <i class="bi bi-chevron-down text-white"></i>
                            </span>
                        </div>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end bg-dark border-secondary" aria-labelledby="navbarDropdownDesktop">
                        <a class="dropdown-item text-white {{ request()->routeIs('profile.edit') ? 'active' : '' }}" href="{{ route('perfil.editar') }}">Editar Perfil</a>
                        <hr class="dropdown-divider border-secondary">
                        <a class="dropdown-item text-white logout-link" id="desktop-logout-link" href="#">Sair</a>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</nav>
