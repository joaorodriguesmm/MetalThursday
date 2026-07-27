{{--
    Apresenta um botão de ação num e-mail Markdown.

    Os nomes das propriedades e das classes pertencem ao contrato interno
    dos componentes de e-mail do Laravel.

    @since 1.0.0
    @version 2.0.0
--}}

@props([
    'url',
    'color' => 'primary',
    'align' => 'center',
])

<table
    class="action"
    align="{{ $align }}"
    width="100%"
    cellpadding="0"
    cellspacing="0"
    role="presentation"
>
    <tr>
        <td align="{{ $align }}">
            <table
                width="100%"
                border="0"
                cellpadding="0"
                cellspacing="0"
                role="presentation"
            >
                <tr>
                    <td align="{{ $align }}">
                        <table
                            border="0"
                            cellpadding="0"
                            cellspacing="0"
                            role="presentation"
                        >
                            <tr>
                                <td>
                                    <a
                                        class="button button-{{ $color }}"
                                        href="{{ $url }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    >
                                        {!! $slot !!}
                                    </a>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
