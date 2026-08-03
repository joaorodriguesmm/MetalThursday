{{--
    Apresenta um painel de destaque num e-mail Markdown.

    @since 1.0.0
    @version 2.0.0
--}}

<table
    class="panel"
    width="100%"
    cellpadding="0"
    cellspacing="0"
    role="presentation"
>
    <tr>
        <td class="panel-content">
            <table
                width="100%"
                cellpadding="0"
                cellspacing="0"
                role="presentation"
            >
                <tr>
                    <td class="panel-item">
                        {{
                            Illuminate\Mail\Markdown::parse(
                                $slot
                            )
                        }}
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
