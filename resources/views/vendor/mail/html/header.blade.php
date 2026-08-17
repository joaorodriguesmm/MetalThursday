{{--
    Apresenta o cabeçalho de um e-mail Markdown.

    O conteúdo visual do cabeçalho é fornecido através do slot do componente.

    @since 1.0.0
--}}

@props([
    'url',
])

<tr>
    <td class="header">
        <a
            href="{{ $url }}"
            target="_blank"
            rel="noopener noreferrer"
            style="display: inline-block;"
        >
            {!! $slot !!}
        </a>
    </td>
</tr>
