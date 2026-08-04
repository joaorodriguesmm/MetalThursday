{{--
    Apresenta o formulário administrativo de criação de um convite.

    @since 2.0.0
    @version 1.0.0
--}}

<x-layout-aplicacao>
    <x-slot name="titulo">
        Criar convite
    </x-slot>

    <x-slot name="cabecalho">
        <div
            class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3"
        >
            <h1 class="h4 mb-0 fw-bold">
                Criar convite
            </h1>

            <a
                class="btn btn-secondary"
                href="{{ route('convites.indice') }}"
            >
                Voltar aos convites
            </a>
        </div>
    </x-slot>

    <div class="card shadow-sm bg-dark">
        <div class="card-body">
            <div
                class="alert alert-info"
                role="status"
            >
                O código original será apresentado apenas uma vez, imediatamente
                depois da criação. A aplicação guardará apenas o respetivo hash.
            </div>

            <form
                method="POST"
                action="{{ route('convites.guardar') }}"
                novalidate
            >
                @csrf

                <div class="mb-3">
                    <label
                        class="form-label"
                        for="nome-convidado"
                    >
                        Nome da pessoa convidada
                    </label>

                    <input
                        id="nome-convidado"
                        class="form-control @error('nome_convidado', 'criacao_convite') is-invalid @enderror"
                        type="text"
                        name="nome_convidado"
                        value="{{ old('nome_convidado') }}"
                        maxlength="{{ App\Models\Autenticacao\Convite::COMPRIMENTO_MAXIMO_NOME_CONVIDADO }}"
                        autocomplete="name"
                        aria-describedby="erro-nome-convidado"
                        @error('nome_convidado', 'criacao_convite')
                            aria-invalid="true"
                        @enderror
                        required
                        autofocus
                    >

                    <div
                        id="erro-nome-convidado"
                        class="invalid-feedback @error('nome_convidado', 'criacao_convite') d-block @enderror"
                        aria-live="polite"
                    >
                        @error('nome_convidado', 'criacao_convite')
                            {{ $message }}
                        @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label
                        class="form-label"
                        for="email-destino"
                    >
                        Endereço de e-mail de destino
                    </label>

                    <input
                        id="email-destino"
                        class="form-control @error('email_destino', 'criacao_convite') is-invalid @enderror"
                        type="email"
                        name="email_destino"
                        value="{{ old('email_destino') }}"
                        maxlength="255"
                        autocomplete="email"
                        aria-describedby="ajuda-email-destino erro-email-destino"
                        @error('email_destino', 'criacao_convite')
                            aria-invalid="true"
                        @enderror
                    >

                    <div
                        id="ajuda-email-destino"
                        class="form-text"
                    >
                        Quando preenchido, apenas este endereço poderá utilizar
                        o convite. Deixa vazio para criar um convite sem
                        destinatário específico.
                    </div>

                    <div
                        id="erro-email-destino"
                        class="invalid-feedback @error('email_destino', 'criacao_convite') d-block @enderror"
                        aria-live="polite"
                    >
                        @error('email_destino', 'criacao_convite')
                            {{ $message }}
                        @enderror
                    </div>
                </div>

                <div class="mb-4">
                    <label
                        class="form-label"
                        for="expiracao-convite"
                    >
                        Data e hora de expiração
                    </label>

                    <input
                        id="expiracao-convite"
                        class="form-control @error('expira_em', 'criacao_convite') is-invalid @enderror"
                        type="datetime-local"
                        name="expira_em"
                        value="{{ old('expira_em') }}"
                        min="{{ $expiracaoMinima }}"
                        aria-describedby="ajuda-expiracao-convite erro-expiracao-convite"
                        @error('expira_em', 'criacao_convite')
                            aria-invalid="true"
                        @enderror
                    >

                    <div
                        id="ajuda-expiracao-convite"
                        class="form-text"
                    >
                        Deixa vazio para criar um convite sem prazo de
                        expiração.
                    </div>

                    <div
                        id="erro-expiracao-convite"
                        class="invalid-feedback @error('expira_em', 'criacao_convite') d-block @enderror"
                        aria-live="polite"
                    >
                        @error('expira_em', 'criacao_convite')
                            {{ $message }}
                        @enderror
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a
                        class="btn btn-secondary"
                        href="{{ route('convites.indice') }}"
                    >
                        Cancelar
                    </a>

                    <button
                        class="btn btn-primary"
                        type="submit"
                    >
                        <i
                            class="bi bi-person-plus me-2"
                            aria-hidden="true"
                        ></i>

                        Criar convite
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-layout-aplicacao>
