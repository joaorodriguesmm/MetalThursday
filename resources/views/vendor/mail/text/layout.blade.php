{{--
    Define a estrutura base da versão de texto dos e-mails Markdown.

    O conteúdo HTML eventualmente produzido pelos componentes é removido
    antes de ser incluído na mensagem de texto simples.

    @since 1.0.0
--}}

{!! strip_tags($header ?? '') !!}

{!! strip_tags($slot) !!}

@isset($subcopy)
{!! strip_tags($subcopy) !!}
@endisset

{!! strip_tags($footer ?? '') !!}
