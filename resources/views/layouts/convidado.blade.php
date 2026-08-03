{{--
    Define o documento utilizado nas páginas destinadas a visitantes.

    Os dados comuns do documento são preparados pela classe
    App\View\Components\LayoutConvidado, através da classe LayoutBase.

    @since 1.0.0
    @version 4.0.0
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

        <main
            id="conteudo-principal"
            class="d-flex flex-grow-1 align-items-center justify-content-center"
        >
            <div class="container py-4">
                <div class="row justify-content-center">
                    <div class="col-md-6 col-lg-5">
                        <div class="mb-4 text-center">
                            <a
                                href="{{ route('inicio') }}"
                                aria-label="Ir para a página inicial de {{ $nomeAplicacao }}"
                            >
                                <img
                                    class="img-fluid"
                                    src="{{ asset('images/logo.png') }}"
                                    alt="{{ $nomeAplicacao }}"
                                >
                            </a>
                        </div>

                        <div class="card shadow-sm">
                            <div class="card-body p-4 p-md-5">
                                {{ $slot }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <footer class="py-3 text-center text-muted small">
            &copy;
            {{ $anoAtual }}
            {{ $nomeAplicacao }}.
            Todos os direitos reservados.
        </footer>

        @stack('scripts-pagina')
    </body>
</html>
