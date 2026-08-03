{{--
    Apresenta uma tabela gerada através de conteúdo Markdown.

    @since 1.0.0
    @version 2.0.0
--}}

<div class="table">
    {{
        Illuminate\Mail\Markdown::parse(
            $slot
        )
    }}
</div>
