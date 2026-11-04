{{--
    Apresenta uma resposta HTTP 403 coerente com a identidade visual da aplicação.
    A personalização não altera o estado HTTP nem as regras de autorização.

    @since 2.0.0
--}}

@auth('sessao')
<x-layout-aplicacao>
    <x-slot name="titulo">
        Acesso negado
    </x-slot>

    <x-slot name="cabecalho">
        <h1 class="h4 mb-0 fw-bold">
            Acesso negado
        </h1>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-12 col-md-9 col-lg-7">
            <section class="card shadow-sm bg-dark border-secondary text-center">
                <div class="card-body p-4 p-md-5">
                    <p
                        class="display-1 fw-bold text-danger mb-3"
                        aria-hidden="true"
                    >
                        403
                    </p>

                    <h2 class="h4 mb-3">
                        Não tens permissão para aceder a esta página.
                    </h2>

                    <p class="text-muted mb-4">
                        A tua conta não possui as permissões necessárias
                        para consultar este conteúdo.
                    </p>

                    <a
                        class="btn btn-primary"
                        href="{{ route('inicio') }}"
                    >
                        <i
                            class="bi bi-house-door me-2"
                            aria-hidden="true"
                        ></i>

                        Voltar ao início
                    </a>
                </div>
            </section>
        </div>
    </div>
</x-layout-aplicacao>

@else
    <x-layout-convidado>
        <x-slot name="titulo">
            Acesso negado
        </x-slot>

        <div class="text-center">
            <p class="display-1 fw-bold text-danger mb-3" aria-hidden="true">
                403
            </p>

            <h1 class="h4 mb-3">
                Não tens permissão para aceder a esta página.
            </h1>

            <p class="text-muted mb-4">
                A ligação pode ser inválida ou ter expirado.
            </p>

            <a class="btn btn-primary" href="{{ route('login') }}">
                Iniciar sessão
            </a>

            <div class="mt-3">
                <a href="{{ route('autenticacao.reenviar-verificacao-email') }}">
                    Pedir nova ligação de verificação
                </a>
            </div>
        </div>
    </x-layout-convidado>
@endauth
