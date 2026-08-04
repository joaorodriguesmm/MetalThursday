{{--
    Apresenta a ligação original de um convite acabado de criar.

    A resposta possui cabeçalhos que impedem o armazenamento em cache. O código
    não é colocado na sessão nem pode voltar a ser recuperado.

    @since 2.0.0
    @version 1.0.0
--}}

<x-layout-aplicacao>
    <x-slot name="titulo">
        Convite criado
    </x-slot>

    <x-slot name="cabecalho">
        <h1 class="h4 mb-0 fw-bold">
            Convite criado
        </h1>
    </x-slot>

    <div
        class="alert alert-success"
        role="status"
    >
        O convite de {{ $convite->nome_convidado }} foi criado com sucesso.
    </div>

    <div
        class="alert alert-warning"
        role="alert"
    >
        Guarda ou envia esta ligação agora. O código original não foi
        persistido e não poderá voltar a ser apresentado.
    </div>

    <section class="card shadow-sm bg-dark">
        <div class="card-header">
            <h2 class="h5 mb-0">
                Ligação do convite
            </h2>
        </div>

        <div class="card-body">
            <label
                class="form-label"
                for="ligacao-convite"
            >
                Ligação completa
            </label>

            <textarea
                id="ligacao-convite"
                class="form-control font-monospace"
                rows="4"
                readonly
                autofocus
            >{{ $ligacaoConvite }}</textarea>

            <dl class="row mt-4 mb-0">
                <dt class="col-sm-4">
                    Pessoa convidada
                </dt>

                <dd class="col-sm-8">
                    {{ $convite->nome_convidado }}
                </dd>

                <dt class="col-sm-4">
                    Destinatário
                </dt>

                <dd class="col-sm-8 text-break">
                    {{
                        $convite->email_destino
                        ?? 'Sem destinatário específico'
                    }}
                </dd>

                <dt class="col-sm-4">
                    Expiração
                </dt>

                <dd class="col-sm-8 mb-0">
                    {{
                        $convite
                            ->expira_em
                            ?->format(
                                'd/m/Y H:i',
                            )
                        ?? 'Sem expiração'
                    }}
                </dd>
            </dl>
        </div>
    </section>

    <div class="mt-4 d-flex flex-wrap gap-2">
        <a
            class="btn btn-primary"
            href="{{ route('convites.criar') }}"
        >
            Criar outro convite
        </a>

        <a
            class="btn btn-secondary"
            href="{{ route('convites.indice') }}"
        >
            Voltar aos convites
        </a>
    </div>
</x-layout-aplicacao>
