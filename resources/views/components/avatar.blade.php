{{--
    Apresenta o avatar de um utilizador.

    A preparação do tamanho, fotografia, iniciais, descrição e estado
    decorativo é efetuada pela classe App\View\Components\Avatar.

    @since 1.0.0
--}}

@if ($temFotografia)
    <img
        {{
            $attributes
                ->except([
                    'style',
                    'src',
                    'width',
                    'height',
                    'alt',
                    'role',
                    'aria-label',
                    'aria-hidden',
                ])
                ->class([
                    'avatar-utilizador',
                    'avatar-utilizador--fotografia',
                    "avatar-utilizador--tamanho-{$tamanho}",
                ])
        }}
        src="{{ $urlFotografia }}"
        width="{{ $tamanho }}"
        height="{{ $tamanho }}"
        alt="{{ $avatarDecorativo ? '' : $descricaoAvatar }}"
    >
@else
    <span
        {{
            $attributes
                ->except([
                    'style',
                    'src',
                    'width',
                    'height',
                    'alt',
                    'role',
                    'aria-label',
                    'aria-hidden',
                ])
                ->class([
                    'avatar-utilizador',
                    'avatar-utilizador--iniciais',
                    "avatar-utilizador--tamanho-{$tamanho}",
                ])
        }}
        @if ($avatarDecorativo)
            aria-hidden="true"
        @else
            role="img"
            aria-label="{{ $descricaoAvatar }}"
        @endif
    >
        <span aria-hidden="true">
            {{ $iniciais }}
        </span>
    </span>
@endif
