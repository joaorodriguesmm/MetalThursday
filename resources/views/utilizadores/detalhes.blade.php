{{--
    Apresenta os detalhes administrativos de um utilizador.

    Os dados do utilizador, a suspensão atual, o convite utilizado e o
    histórico do acesso são carregados explicitamente pelo
    App\Http\Controllers\Utilizadores\ControladorUtilizador.

    @since 2.0.0
    @version 1.0.0
--}}

<x-layout-aplicacao>
    <x-slot name="titulo">
        {{ $utilizador->nome }}
    </x-slot>

    <x-slot name="cabecalho">
        <div
            class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3"
        >
            <div class="d-flex align-items-center gap-3">
                <x-avatar
                    :utilizador="$utilizador"
                    :tamanho="56"
                    descricao=""
                />

                <div>
                    <h1 class="h4 mb-1 fw-bold">
                        {{ $utilizador->nome }}
                    </h1>

                    <p class="mb-0 text-muted">
                        {{ $utilizador->email }}
                    </p>
                </div>
            </div>

            <a
                class="btn btn-secondary"
                href="{{ route('utilizadores.indice') }}"
            >
                Voltar aos utilizadores
            </a>
        </div>
    </x-slot>

    <x-estado-sessao class="mb-4" />

    <div class="row g-4">
        <div class="col-12 col-xl-6">
            <section class="card shadow-sm h-100">
                <div class="card-header">
                    <h2 class="h5 mb-0">
                        Conta
                    </h2>
                </div>

                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-5">
                            Identificador
                        </dt>

                        <dd class="col-sm-7">
                            {{ $utilizador->getKey() }}
                        </dd>

                        <dt class="col-sm-5">
                            Nome
                        </dt>

                        <dd class="col-sm-7">
                            {{ $utilizador->nome }}
                        </dd>

                        <dt class="col-sm-5">
                            Endereço de e-mail
                        </dt>

                        <dd class="col-sm-7 text-break">
                            {{ $utilizador->email }}
                        </dd>

                        <dt class="col-sm-5">
                            Papel
                        </dt>

                        <dd class="col-sm-7">
                            {{ $utilizador->papel->etiqueta() }}
                        </dd>

                        <dt class="col-sm-5">
                            Verificação do e-mail
                        </dt>

                        <dd class="col-sm-7">
                            @if ($utilizador->email_verified_at !== null)
                                <span class="badge text-bg-success">
                                    Verificado
                                </span>

                                <span class="ms-1">
                                    {{
                                        $utilizador
                                            ->email_verified_at
                                            ->format(
                                                'd/m/Y H:i',
                                            )
                                    }}
                                </span>
                            @else
                                <span class="badge text-bg-warning">
                                    Por verificar
                                </span>
                            @endif
                        </dd>

                        <dt class="col-sm-5">
                            Registado em
                        </dt>

                        <dd class="col-sm-7">
                            {{
                                $utilizador
                                    ->created_at
                                    ?->format(
                                        'd/m/Y H:i',
                                    )
                                ?? 'Indisponível'
                            }}
                        </dd>

                        <dt class="col-sm-5">
                            Última atualização
                        </dt>

                        <dd class="col-sm-7 mb-0">
                            {{
                                $utilizador
                                    ->updated_at
                                    ?->format(
                                        'd/m/Y H:i',
                                    )
                                ?? 'Indisponível'
                            }}
                        </dd>
                    </dl>
                </div>
            </section>
        </div>

        <div class="col-12 col-xl-6">
            <section class="card shadow-sm h-100">
                <div class="card-header">
                    <h2 class="h5 mb-0">
                        Estado do acesso
                    </h2>
                </div>

                <div class="card-body">
                    @if ($utilizador->estaSuspenso())
                        <div
                            class="alert alert-danger"
                            role="status"
                        >
                            O acesso deste utilizador encontra-se suspenso.
                        </div>

                        <dl class="row mb-0">
                            <dt class="col-sm-5">
                                Estado
                            </dt>

                            <dd class="col-sm-7">
                                <span class="badge text-bg-danger">
                                    Suspenso
                                </span>
                            </dd>

                            <dt class="col-sm-5">
                                Suspenso em
                            </dt>

                            <dd class="col-sm-7">
                                {{
                                    $utilizador
                                        ->suspenso_em
                                        ?->format(
                                            'd/m/Y H:i',
                                        )
                                    ?? 'Indisponível'
                                }}
                            </dd>

                            <dt class="col-sm-5">
                                Responsável
                            </dt>

                            <dd class="col-sm-7">
                                @if ($utilizador->responsavelSuspensao !== null)
                                    <span class="d-block">
                                        {{ $utilizador->responsavelSuspensao->nome }}
                                    </span>

                                    <span class="small text-muted text-break">
                                        {{ $utilizador->responsavelSuspensao->email }}
                                    </span>
                                @else
                                    Indisponível
                                @endif
                            </dd>

                            <dt class="col-sm-5">
                                Motivo
                            </dt>

                            <dd class="col-sm-7 mb-0 text-break">
                                {{ $utilizador->motivo_suspensao }}
                            </dd>
                        </dl>
                    @else
                        <div
                            class="alert alert-success mb-0"
                            role="status"
                        >
                            O utilizador possui acesso ativo à aplicação.
                        </div>
                    @endif
                </div>
            </section>
        </div>

        <div class="col-12">
            <section class="card shadow-sm">
                <div class="card-header">
                    <h2 class="h5 mb-0">
                        Convite de registo
                    </h2>
                </div>

                <div class="card-body">
                    @if ($utilizador->conviteUtilizado !== null)
                        <dl class="row mb-0">
                            <dt class="col-sm-4 col-lg-3">
                                Nome no convite
                            </dt>

                            <dd class="col-sm-8 col-lg-9">
                                {{ $utilizador->conviteUtilizado->nome_convidado }}
                            </dd>

                            <dt class="col-sm-4 col-lg-3">
                                E-mail de destino
                            </dt>

                            <dd class="col-sm-8 col-lg-9 text-break">
                                {{
                                    $utilizador
                                        ->conviteUtilizado
                                        ->email_destino
                                    ?? 'Convite sem endereço específico'
                                }}
                            </dd>

                            <dt class="col-sm-4 col-lg-3">
                                Criado por
                            </dt>

                            <dd class="col-sm-8 col-lg-9">
                                @if ($utilizador->conviteUtilizado->criador !== null)
                                    <span class="d-block">
                                        {{ $utilizador->conviteUtilizado->criador->nome }}
                                    </span>

                                    <span class="small text-muted text-break">
                                        {{ $utilizador->conviteUtilizado->criador->email }}
                                    </span>
                                @else
                                    Sistema
                                @endif
                            </dd>

                            <dt class="col-sm-4 col-lg-3">
                                Criado em
                            </dt>

                            <dd class="col-sm-8 col-lg-9">
                                {{
                                    $utilizador
                                        ->conviteUtilizado
                                        ->created_at
                                        ?->format(
                                            'd/m/Y H:i',
                                        )
                                    ?? 'Indisponível'
                                }}
                            </dd>

                            <dt class="col-sm-4 col-lg-3">
                                Utilizado em
                            </dt>

                            <dd class="col-sm-8 col-lg-9 mb-0">
                                {{
                                    $utilizador
                                        ->conviteUtilizado
                                        ->utilizado_em
                                        ?->format(
                                            'd/m/Y H:i',
                                        )
                                    ?? 'Indisponível'
                                }}
                            </dd>
                        </dl>
                    @else
                        <div
                            class="alert alert-info mb-0"
                            role="status"
                        >
                            Não existe um convite associado a este utilizador.
                        </div>
                    @endif
                </div>
            </section>
        </div>

        <div class="col-12">
            <section class="card shadow-sm">
                <div
                    class="card-header d-flex justify-content-between align-items-center gap-3"
                >
                    <h2 class="h5 mb-0">
                        Histórico do acesso
                    </h2>

                    <span class="badge text-bg-secondary">
                        {{ $utilizador->registosAcesso->count() }}
                    </span>
                </div>

                <div class="card-body p-0">
                    @if ($utilizador->registosAcesso->isEmpty())
                        <div
                            class="alert alert-info m-3"
                            role="status"
                        >
                            Ainda não existem alterações do acesso.
                        </div>
                    @else
                        <div class="table-responsive">
                            <table
                                class="table table-striped table-hover align-middle mb-0"
                            >
                                <caption class="visually-hidden">
                                    Histórico das alterações do acesso
                                </caption>

                                <thead>
                                    <tr>
                                        <th scope="col">
                                            Ação
                                        </th>

                                        <th scope="col">
                                            Data
                                        </th>

                                        <th scope="col">
                                            Responsável
                                        </th>

                                        <th scope="col">
                                            Motivo
                                        </th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @foreach ($utilizador->registosAcesso as $registo)
                                        <tr>
                                            <td>
                                                <span
                                                    class="badge {{
                                                        $registo->eSuspensao()
                                                            ? 'text-bg-danger'
                                                            : 'text-bg-success'
                                                    }}"
                                                >
                                                    {{ $registo->acao->etiqueta() }}
                                                </span>
                                            </td>

                                            <td class="text-nowrap">
                                                {{
                                                    $registo
                                                        ->registado_em
                                                        ->format(
                                                            'd/m/Y H:i',
                                                        )
                                                }}
                                            </td>

                                            <td>
                                                <span class="d-block">
                                                    {{ $registo->responsavel->nome }}
                                                </span>

                                                <span class="small text-muted text-break">
                                                    {{ $registo->responsavel->email }}
                                                </span>
                                            </td>

                                            <td class="text-break">
                                                {{ $registo->motivo ?? 'Sem motivo aplicável' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </section>
        </div>
    </div>

    <div class="mt-4">
        <a
            class="btn btn-secondary"
            href="{{ route('utilizadores.indice') }}"
        >
            Voltar aos utilizadores
        </a>
    </div>
</x-layout-aplicacao>
