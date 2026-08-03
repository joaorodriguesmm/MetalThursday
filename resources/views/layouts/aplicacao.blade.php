{{--
    Define o documento principal da área autenticada da aplicação.

    Os dados comuns do documento são preparados pela classe
    App\View\Components\LayoutAplicacao, através da classe LayoutBase.

    @since 1.0.0
    @version 4.1.0
--}}

<!DOCTYPE html>

<html lang="{{ $idiomaDocumento }}">
    <head>
        <meta charset="utf-8">

        <meta
            name="viewport"
            content="width=device-width, initial-scale=1"
        >

        <meta
            name="csrf-token"
            content="{{ csrf_token() }}"
        >

        <meta
            name="robots"
            content="noindex, nofollow"
        >

        <title>
            {{ $tituloDocumento($titulo ?? null) }}
        </title>

        @vite([
            'resources/sass/app.scss',
            'resources/js/app.js',
        ])

        @stack('estilos-pagina')
    </head>

    <body class="d-flex flex-column min-vh-100">
        <a
            class="visually-hidden-focusable position-absolute top-0 start-0 m-2 btn btn-primary"
            href="#conteudo-principal"
        >
            Saltar para o conteúdo principal
        </a>

        <div class="d-flex flex-column flex-grow-1">
            <x-navegacao
                :nome-aplicacao="$nomeAplicacao"
            />

            @if (isset($cabecalho))
                <header class="bg-dark shadow-sm">
                    <div class="container py-3">
                        {{ $cabecalho }}
                    </div>
                </header>
            @endif

            <main
                id="conteudo-principal"
                class="flex-grow-1"
            >
                <div class="container py-4">
                    {{ $slot }}
                </div>
            </main>
        </div>

        <footer class="py-3 text-center text-muted small bg-dark">
            &copy;
            {{ $anoAtual }}
            {{ $nomeAplicacao }}.
            Todos os direitos reservados.
        </footer>

        @auth('sessao')
            <form
                id="formulario-terminar-sessao"
                class="d-none"
                method="POST"
                action="{{ route('logout') }}"
            >
                @csrf
            </form>
        @endauth

        @stack('scripts-pagina')
    </body>
</html>
