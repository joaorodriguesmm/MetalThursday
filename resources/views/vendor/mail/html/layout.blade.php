{{--
    Define a estrutura HTML base dos e-mails Markdown.

    Os nomes das classes pertencem ao contrato interno dos componentes
    de e-mail do Laravel.

    @since 1.0.0
    @version 2.0.0
--}}

<!DOCTYPE html PUBLIC
    "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"
>
<html
    lang="pt-PT"
    xmlns="http://www.w3.org/1999/xhtml"
>
    <head>
        <title>
            {{ config('app.name') }}
        </title>

        <meta
            name="viewport"
            content="width=device-width, initial-scale=1.0"
        >

        <meta
            http-equiv="Content-Type"
            content="text/html; charset=UTF-8"
        >

        <meta
            name="color-scheme"
            content="light"
        >

        <meta
            name="supported-color-schemes"
            content="light"
        >

        <style>
            @media only screen and (max-width: 600px) {
                .inner-body {
                    width: 100% !important;
                }

                .footer {
                    width: 100% !important;
                }

                .content-cell {
                    padding: 24px !important;
                }
            }

            @media only screen and (max-width: 500px) {
                .button {
                    display: block !important;
                    width: auto !important;
                }
            }

            @media only screen and (max-width: 400px) {
                .content-cell {
                    padding: 20px !important;
                }
            }
        </style>

        {!! $head ?? '' !!}
    </head>

    <body
        style="
            background-color: #111827;
            margin: 0;
            padding: 0;
            width: 100%;
        "
    >
        <table
            class="wrapper"
            width="100%"
            cellpadding="0"
            cellspacing="0"
            role="presentation"
            bgcolor="#111827"
            style="background-color: #111827;"
        >
            <tr>
                <td align="center">
                    <table
                        class="content"
                        width="100%"
                        cellpadding="0"
                        cellspacing="0"
                        role="presentation"
                    >
                        {!! $header ?? '' !!}

                        <tr>
                            <td
                                class="body"
                                width="100%"
                                bgcolor="#111827"
                                style="background-color: #111827;"
                            >
                                <table
                                    class="inner-body"
                                    align="center"
                                    width="570"
                                    cellpadding="0"
                                    cellspacing="0"
                                    role="presentation"
                                    bgcolor="#ffffff"
                                    style="background-color: #ffffff;"
                                >
                                    <tr>
                                        <td class="content-cell">
                                            {!!
                                                Illuminate\Mail\Markdown::parse(
                                                    $slot
                                                )
                                            !!}

                                            {!! $subcopy ?? '' !!}
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>

                        {!! $footer ?? '' !!}
                    </table>
                </td>
            </tr>
        </table>
    </body>
</html>
