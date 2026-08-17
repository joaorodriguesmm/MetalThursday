{{--
    Apresenta os detalhes e as operações administrativas de um utilizador.

    Os dados do utilizador, a suspensão atual, o convite utilizado e os
    históricos do acesso e dos papéis são carregados explicitamente pelo
    App\Http\Controllers\Utilizadores\ControladorUtilizador.

    A alteração do papel, a suspensão, a reativação e o encerramento das
    sessões são autorizados pela política e executados pelos respetivos
    serviços transacionais.

    @since 2.0.0
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

        <div class="col-12">
            <section class="card shadow-sm">
                <div
                    class="card-header d-flex justify-content-between align-items-center gap-3"
                >
                    <h2 class="h5 mb-0">
                        Histórico dos papéis
                    </h2>

                    <span class="badge text-bg-secondary">
                        {{ $utilizador->registosPapel->count() }}
                    </span>
                </div>

                <div class="card-body p-0">
                    @if ($utilizador->registosPapel->isEmpty())
                        <div
                            class="alert alert-info m-3"
                            role="status"
                        >
                            Ainda não existem alterações do papel.
                        </div>
                    @else
                        <div class="table-responsive">
                            <table
                                class="table table-striped table-hover align-middle mb-0"
                            >
                                <caption class="visually-hidden">
                                    Histórico das alterações do papel
                                </caption>

                                <thead>
                                    <tr>
                                        <th scope="col">
                                            Papel anterior
                                        </th>

                                        <th scope="col">
                                            Novo papel
                                        </th>

                                        <th scope="col">
                                            Data
                                        </th>

                                        <th scope="col">
                                            Responsável
                                        </th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @foreach ($utilizador->registosPapel as $registo)
                                        <tr>
                                            <td>
                                                {{ $registo->papel_anterior->etiqueta() }}
                                            </td>

                                            <td>
                                                <span class="badge text-bg-primary">
                                                    {{ $registo->papel_novo->etiqueta() }}
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

    @can('alterarPapel', $utilizador)
        <section class="card shadow-sm mt-4 border-primary">
            <div class="card-header">
                <h2 class="h5 mb-0">
                    Alterar papel
                </h2>
            </div>

            <div class="card-body">
                <div
                    class="alert alert-warning"
                    role="alert"
                >
                    A alteração do papel encerra todas as sessões atuais e
                    invalida a autenticação persistente do utilizador. O estado
                    ativo ou suspenso da conta não será alterado.
                </div>

                <form
                    method="POST"
                    action="{{
                        route(
                            'utilizadores.alterar-papel',
                            $utilizador,
                        )
                    }}"
                    novalidate
                >
                    @csrf
                    @method('PATCH')

                    <div class="mb-3">
                        <label
                            class="form-label"
                            for="papel-utilizador"
                        >
                            Novo papel
                        </label>

                        <select
                            id="papel-utilizador"
                            class="form-select @error('papel', 'papel') is-invalid @enderror"
                            name="papel"
                            aria-describedby="ajuda-papel-utilizador erro-papel-utilizador"
                            @error('papel', 'papel')
                                aria-invalid="true"
                            @enderror
                            required
                        >
                            @foreach ($papeisDisponiveis as $papelDisponivel)
                                <option
                                    value="{{ $papelDisponivel->value }}"
                                    @selected(
                                        old(
                                            'papel',
                                            $utilizador->papel->value,
                                        ) === $papelDisponivel->value
                                    )
                                >
                                    {{ $papelDisponivel->etiqueta() }}

                                    @if ($papelDisponivel === $utilizador->papel)
                                        (atual)
                                    @endif
                                </option>
                            @endforeach
                        </select>

                        <div
                            id="ajuda-papel-utilizador"
                            class="form-text"
                        >
                            A alteração ficará registada permanentemente no
                            histórico administrativo.
                        </div>

                        <div
                            id="erro-papel-utilizador"
                            class="invalid-feedback @error('papel', 'papel') d-block @enderror"
                            aria-live="polite"
                        >
                            @error('papel', 'papel')
                                {{ $message }}
                            @enderror
                        </div>
                    </div>

                    <div class="form-check mb-3">
                        <input
                            id="confirmar-alteracao-papel"
                            class="form-check-input @error('confirmar_alteracao_papel', 'papel') is-invalid @enderror"
                            type="checkbox"
                            name="confirmar_alteracao_papel"
                            value="1"
                            @checked(old('confirmar_alteracao_papel'))
                            aria-describedby="erro-confirmar-alteracao-papel"
                            @error('confirmar_alteracao_papel', 'papel')
                                aria-invalid="true"
                            @enderror
                            required
                        >

                        <label
                            class="form-check-label"
                            for="confirmar-alteracao-papel"
                        >
                            Confirmo que pretendo alterar o papel deste
                            utilizador.
                        </label>

                        <div
                            id="erro-confirmar-alteracao-papel"
                            class="invalid-feedback @error('confirmar_alteracao_papel', 'papel') d-block @enderror"
                            aria-live="polite"
                        >
                            @error('confirmar_alteracao_papel', 'papel')
                                {{ $message }}
                            @enderror
                        </div>
                    </div>

                    <div class="text-end">
                        <button
                            class="btn btn-primary"
                            type="submit"
                        >
                            <i
                                class="bi bi-person-gear me-2"
                                aria-hidden="true"
                            ></i>

                            Alterar papel
                        </button>
                    </div>
                </form>
            </div>
        </section>
    @endcan

    @can('suspender', $utilizador)
        <section class="card shadow-sm mt-4 border-danger">
            <div class="card-header">
                <h2 class="h5 mb-0">
                    Suspender acesso
                </h2>
            </div>

            <div class="card-body">
                <div
                    class="alert alert-warning"
                    role="alert"
                >
                    A suspensão encerra todas as sessões do utilizador e impede
                    novos inícios de sessão até que o acesso seja reativado.
                </div>

                <form
                    method="POST"
                    action="{{
                        route(
                            'utilizadores.suspender',
                            $utilizador,
                        )
                    }}"
                    novalidate
                >
                    @csrf
                    @method('PATCH')

                    <div class="mb-3">
                        <label
                            class="form-label"
                            for="motivo-suspensao"
                        >
                            Motivo da suspensão
                        </label>

                        <textarea
                            id="motivo-suspensao"
                            class="form-control @error('motivo', 'suspensao') is-invalid @enderror"
                            name="motivo"
                            rows="4"
                            maxlength="{{ App\ObjetosValor\Utilizadores\MotivoSuspensaoUtilizador::COMPRIMENTO_MAXIMO }}"
                            aria-describedby="ajuda-motivo-suspensao erro-motivo-suspensao"
                            @error('motivo', 'suspensao')
                                aria-invalid="true"
                            @enderror
                            required
                        >{{ old('motivo') }}</textarea>

                        <div
                            id="ajuda-motivo-suspensao"
                            class="form-text"
                        >
                            O motivo ficará registado permanentemente no
                            histórico administrativo.
                        </div>

                        <div
                            id="erro-motivo-suspensao"
                            class="invalid-feedback @error('motivo', 'suspensao') d-block @enderror"
                            aria-live="polite"
                        >
                            @error('motivo', 'suspensao')
                                {{ $message }}
                            @enderror
                        </div>
                    </div>

                    <div class="text-end">
                        <button
                            class="btn btn-danger"
                            type="submit"
                        >
                            <i
                                class="bi bi-person-lock me-2"
                                aria-hidden="true"
                            ></i>

                            Suspender acesso
                        </button>
                    </div>
                </form>
            </div>
        </section>
    @endcan

    @can('reativar', $utilizador)
        <section class="card shadow-sm mt-4 border-success">
            <div class="card-header">
                <h2 class="h5 mb-0">
                    Reativar acesso
                </h2>
            </div>

            <div class="card-body">
                <div
                    class="alert alert-info"
                    role="status"
                >
                    A reativação permite que o utilizador volte a iniciar
                    sessão. As sessões anteriores não serão restauradas.
                </div>

                <form
                    method="POST"
                    action="{{
                        route(
                            'utilizadores.reativar',
                            $utilizador,
                        )
                    }}"
                    novalidate
                >
                    @csrf
                    @method('PATCH')

                    <div class="form-check mb-3">
                        <input
                            id="confirmar-reativacao"
                            class="form-check-input @error('confirmar_reativacao', 'reativacao') is-invalid @enderror"
                            type="checkbox"
                            name="confirmar_reativacao"
                            value="1"
                            @checked(old('confirmar_reativacao'))
                            aria-describedby="erro-confirmar-reativacao"
                            @error('confirmar_reativacao', 'reativacao')
                                aria-invalid="true"
                            @enderror
                            required
                        >

                        <label
                            class="form-check-label"
                            for="confirmar-reativacao"
                        >
                            Confirmo que pretendo reativar o acesso deste
                            utilizador.
                        </label>

                        <div
                            id="erro-confirmar-reativacao"
                            class="invalid-feedback @error('confirmar_reativacao', 'reativacao') d-block @enderror"
                            aria-live="polite"
                        >
                            @error('confirmar_reativacao', 'reativacao')
                                {{ $message }}
                            @enderror
                        </div>
                    </div>

                    <div class="text-end">
                        <button
                            class="btn btn-success"
                            type="submit"
                        >
                            <i
                                class="bi bi-person-check me-2"
                                aria-hidden="true"
                            ></i>

                            Reativar acesso
                        </button>
                    </div>
                </form>
            </div>
        </section>
    @endcan

    @can('encerrarSessoes', $utilizador)
        <section class="card shadow-sm mt-4 border-warning">
            <div class="card-header">
                <h2 class="h5 mb-0">
                    Encerrar sessões
                </h2>
            </div>

            <div class="card-body">
                <div
                    class="alert alert-warning"
                    role="alert"
                >
                    Todas as sessões atuais serão encerradas e a autenticação
                    persistente será invalidada. O estado ativo ou suspenso da
                    conta não será alterado.
                </div>

                <form
                    method="POST"
                    action="{{
                        route(
                            'utilizadores.encerrar-sessoes',
                            $utilizador,
                        )
                    }}"
                    novalidate
                >
                    @csrf
                    @method('DELETE')

                    <div class="form-check mb-3">
                        <input
                            id="confirmar-encerramento-sessoes"
                            class="form-check-input @error('confirmar_encerramento_sessoes', 'sessoes') is-invalid @enderror"
                            type="checkbox"
                            name="confirmar_encerramento_sessoes"
                            value="1"
                            @checked(old('confirmar_encerramento_sessoes'))
                            aria-describedby="erro-confirmar-encerramento-sessoes"
                            @error('confirmar_encerramento_sessoes', 'sessoes')
                                aria-invalid="true"
                            @enderror
                            required
                        >

                        <label
                            class="form-check-label"
                            for="confirmar-encerramento-sessoes"
                        >
                            Confirmo que pretendo encerrar todas as sessões
                            deste utilizador.
                        </label>

                        <div
                            id="erro-confirmar-encerramento-sessoes"
                            class="invalid-feedback @error('confirmar_encerramento_sessoes', 'sessoes') d-block @enderror"
                            aria-live="polite"
                        >
                            @error('confirmar_encerramento_sessoes', 'sessoes')
                                {{ $message }}
                            @enderror
                        </div>
                    </div>

                    <div class="text-end">
                        <button
                            class="btn btn-warning"
                            type="submit"
                        >
                            <i
                                class="bi bi-box-arrow-right me-2"
                                aria-hidden="true"
                            ></i>

                            Encerrar todas as sessões
                        </button>
                    </div>
                </form>
            </div>
        </section>
    @endcan

    <div class="mt-4">
        <a
            class="btn btn-secondary"
            href="{{ route('utilizadores.indice') }}"
        >
            Voltar aos utilizadores
        </a>
    </div>
</x-layout-aplicacao>
