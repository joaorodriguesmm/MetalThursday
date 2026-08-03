{{--
    Apresenta a navegação principal da aplicação.

    A identificação do utilizador, as páginas ativas, a autorização para gerir
    utilizadores e a quantidade de notificações por ler são preparadas pela
    classe App\View\Components\Navegacao.

    @since 1.0.0
    @version 5.0.0
--}}

<nav
    {{
        $attributes
            ->except([
                'aria-label',
            ])
            ->class([
                'navbar',
                'navbar-expand-md',
                'navbar-dark',
                'bg-dark',
                'border-bottom',
                'border-secondary',
                'shadow-sm',
            ])
    }}
    aria-label="Navegação principal"
>
    <div class="container">
        <a
            class="navbar-brand"
            href="{{ route('inicio') }}"
            aria-label="{{ $nomeAplicacao }} — página inicial"
        >
            <img
                src="{{ asset('images/logo.png') }}"
                alt=""
                height="60"
                aria-hidden="true"
                decoding="async"
            >
        </a>

        <ul
            class="navbar-nav flex-row d-md-none ms-auto align-items-center"
            aria-label="Acessos rápidos"
        >
            <li class="nav-item me-3">
                <x-icone-notificacoes
                    :quantidade="$numeroNotificacoesNaoLidas"
                />
            </li>
        </ul>

        <button
            class="navbar-toggler ms-3"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#conteudo-navegacao-principal"
            aria-controls="conteudo-navegacao-principal"
            aria-expanded="false"
            aria-label="Abrir ou fechar a navegação principal"
        >
            <span
                class="navbar-toggler-icon"
                aria-hidden="true"
            ></span>
        </button>

        <div
            id="conteudo-navegacao-principal"
            class="collapse navbar-collapse"
        >
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a
                        class="nav-link {{
                            $paginaInicialAtiva
                                ? 'active'
                                : ''
                        }}"
                        href="{{ route('inicio') }}"
                        @if ($paginaInicialAtiva)
                            aria-current="page"
                        @endif
                    >
                        Início
                    </a>
                </li>

                @if ($podeGerirUtilizadores)
                    <li class="nav-item">
                        <a
                            class="nav-link {{
                                $paginaUtilizadoresAtiva
                                    ? 'active'
                                    : ''
                            }}"
                            href="{{ route('utilizadores.indice') }}"
                            @if ($paginaUtilizadoresAtiva)
                                aria-current="page"
                            @endif
                        >
                            Utilizadores
                        </a>
                    </li>
                @endif

                <li
                    class="nav-item d-md-none"
                    aria-hidden="true"
                >
                    <hr class="border-secondary">
                </li>

                <li class="nav-item d-md-none">
                    <a
                        class="nav-link {{
                            $paginaPerfilAtiva
                                ? 'active'
                                : ''
                        }}"
                        href="{{ route('perfil.editar') }}"
                        @if ($paginaPerfilAtiva)
                            aria-current="page"
                        @endif
                    >
                        Editar perfil
                    </a>
                </li>

                <li class="nav-item d-md-none">
                    <button
                        class="nav-link w-100 border-0 bg-transparent text-start"
                        type="button"
                        data-terminar-sessao
                    >
                        Sair
                    </button>
                </li>
            </ul>

            <ul
                class="navbar-nav ms-auto d-none d-md-flex align-items-center"
                aria-label="Conta do utilizador"
            >
                <li class="nav-item me-3">
                    <x-icone-notificacoes
                        :quantidade="$numeroNotificacoesNaoLidas"
                    />
                </li>

                <li class="nav-item dropdown">
                    <button
                        id="alternador-menu-utilizador"
                        class="nav-link dropdown-toggle p-0 border-0 bg-transparent"
                        type="button"
                        data-bs-toggle="dropdown"
                        aria-expanded="false"
                        aria-label="Abrir menu do utilizador"
                    >
                        <span class="contentor-avatar-navegacao">
                            <x-avatar
                                :utilizador="$utilizadorAutenticado"
                                :tamanho="40"
                                descricao=""
                            />

                            <span
                                class="indicador-menu-utilizador position-absolute bg-dark rounded-circle"
                                aria-hidden="true"
                            >
                                <i class="bi bi-chevron-down"></i>
                            </span>
                        </span>
                    </button>

                    <div
                        class="dropdown-menu dropdown-menu-end bg-dark border-secondary"
                        aria-labelledby="alternador-menu-utilizador"
                    >
                        <a
                            class="dropdown-item text-white {{
                                $paginaPerfilAtiva
                                    ? 'active'
                                    : ''
                            }}"
                            href="{{ route('perfil.editar') }}"
                            @if ($paginaPerfilAtiva)
                                aria-current="page"
                            @endif
                        >
                            Editar perfil
                        </a>

                        <hr class="dropdown-divider border-secondary">

                        <button
                            class="dropdown-item text-white"
                            type="button"
                            data-terminar-sessao
                        >
                            Sair
                        </button>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</nav>
