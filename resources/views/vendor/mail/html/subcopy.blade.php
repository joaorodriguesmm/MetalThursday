{{--
    Apresenta o texto auxiliar colocado abaixo do conteúdo principal.

    Este conteúdo é normalmente utilizado para mostrar uma ligação alternativa
    quando o botão principal não funciona.

    @since 1.0.0
    @version 2.0.0
--}}

<table
    class="subcopy"
    width="100%"
    cellpadding="0"
    cellspacing="0"
    role="presentation"
>
    <tr>
        <td>
            {{
                Illuminate\Mail\Markdown::parse(
                    $slot
                )
            }}
        </td>
    </tr>
</table>
