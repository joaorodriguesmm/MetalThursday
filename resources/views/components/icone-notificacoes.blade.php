{{--
    Apresenta a ligação para a página de notificações.

    A normalização da quantidade, a descrição acessível e a deteção da
    página ativa são efetuadas pela classe
    App\View\Components\IconeNotificacoes.

    @since 1.0.0
    @version 3.0.0
--}}

<a
    {{
        $attributes
            ->except([
                'href',
                'aria-label',
                'aria-current',
            ])
            ->class([
                'nav-link',
                'text-white',
                'position-relative',
                'icone-notificacoes',
                'active' => $paginaAtiva,
            ])
    }}
    href="{{ route('notificacoes.indice') }}"
    aria-label="{{ $descricao }}"
    @if ($paginaAtiva)
        aria-current="page"
    @endif
>
    <i
        class="bi bi-bell"
        aria-hidden="true"
    ></i>

    @if ($temNotificacoesPorLer)
        <span
            class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger text-white indicador-notificacoes"
            aria-hidden="true"
        >
            {{ $quantidadeVisivel }}
        </span>
    @endif
</a>
