{{--
    Define o conteúdo comum das mensagens Markdown do MetalThursday.

    Inclui o logótipo da aplicação, o conteúdo principal, o texto auxiliar
    opcional e o rodapé institucional.

    @since 1.0.0
    @version 2.0.0
--}}

<x-mail::layout>
    <x-slot:header>
        <x-mail::header :url="config('app.url')">
            <img
                src="{{ asset('images/logo.png') }}"
                width="150"
                alt="{{ config('app.name') }}"
                style="
                    border: 0;
                    display: block;
                    height: auto;
                    max-width: 150px;
                    outline: none;
                    text-decoration: none;
                "
            >
        </x-mail::header>
    </x-slot:header>

    {{ $slot }}

    @isset($subcopy)
        <x-slot:subcopy>
            <x-mail::subcopy>
                {{ $subcopy }}
            </x-mail::subcopy>
        </x-slot:subcopy>
    @endisset

    <x-slot:footer>
        <x-mail::footer>
            © {{ now()->year }} {{ config('app.name') }}.
            Todos os direitos reservados.
        </x-mail::footer>
    </x-slot:footer>
</x-mail::layout>
