{{-- Área reservada ao superadministrador para analisar denúncias de comentários. --}}
<x-layout-aplicacao>
    <x-slot name="titulo">Moderação de comentários</x-slot>

    <x-slot name="cabecalho">
        <h1 class="h4 mb-0 fw-bold">Moderação de comentários</h1>
    </x-slot>

    <x-estado-sessao class="mb-4" />

    <div class="card bg-dark border-secondary shadow-sm">
        <div class="card-body">
            <form
                method="GET"
                action="{{ route('moderacao.denuncias-comentarios.painel') }}"
                class="row g-3 mb-4 align-items-end"
            >
                <div class="col-12 col-md-5">
                    <label class="form-label" for="filtro-estado-denuncias">
                        Estado das denúncias
                    </label>
                    <select class="form-select" id="filtro-estado-denuncias" name="estado">
                        <option value="pendente" @selected($estadoAtual === 'pendente')>Pendentes</option>
                        <option value="rejeitada" @selected($estadoAtual === 'rejeitada')>Rejeitadas</option>
                        <option value="aceite" @selected($estadoAtual === 'aceite')>Aceites</option>
                    </select>
                </div>
                <div class="col-12 col-md-auto">
                    <button class="btn btn-primary" type="submit">Filtrar</button>
                </div>
            </form>

            @forelse ($denuncias as $denuncia)
                @php
                    $comentario = $denuncia->comentario;
                    $entidade = $comentario?->comentavel;
                    $identificadorMetalThursday = $entidade instanceof \App\Models\MetalThursday\MetalThursday
                        ? $entidade->id
                        : ($entidade instanceof \App\Models\MetalThursday\SeccaoMetalThursday
                            ? $entidade->metal_thursday_id
                            : null);
                @endphp

                <article class="border border-secondary rounded p-3 mb-3" data-denuncia-id="{{ $denuncia->id }}">
                    <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
                        <h2 class="h6 mb-1">Denúncia #{{ $denuncia->id }}</h2>
                        <span class="small text-white-50">
                            {{ $denuncia->created_at?->format('d/m/Y H:i') }}
                        </span>
                    </div>

                    <p class="mb-2 small">
                        <strong>Motivo:</strong> {{ match ($denuncia->motivo) {
                            'spam' => 'Spam',
                            'ofensivo' => 'Conteúdo ofensivo',
                            'assedio' => 'Assédio',
                            default => 'Outro',
                        } }}
                        <span class="mx-2" aria-hidden="true">·</span>
                        <strong>Denunciante:</strong> {{ $denuncia->denunciante?->nome ?? 'Utilizador removido' }}
                    </p>

                    @if (filled($denuncia->detalhes))
                        <p class="mb-3 small text-white-50" style="white-space: pre-wrap">{{ $denuncia->detalhes }}</p>
                    @endif

                    @if ($comentario === null || $comentario->trashed() || $comentario->temConteudoEliminado())
                        <p class="text-white-50 fst-italic mb-3">O comentário foi eliminado.</p>
                    @else
                        <div class="rounded bg-secondary p-3 mb-3">
                            <p class="small mb-1">Comentário de {{ $comentario->utilizador?->nome ?? 'Utilizador removido' }}</p>
                            <p class="mb-0" style="white-space: pre-wrap">{{ $comentario->conteudo }}</p>
                        </div>
                    @endif

                    <div class="d-flex flex-wrap align-items-center gap-2">
                        @if ($identificadorMetalThursday !== null && $comentario !== null && in_array($identificadorMetalThursday, $publicacoesDisponiveis, true))
                            @php
                                $comentarioDisponivel = ! $comentario->trashed() && ! $comentario->temConteudoEliminado();
                                $parametrosLigacao = ["metalThursday" => $identificadorMetalThursday];

                                if ($comentarioDisponivel) {
                                    $parametrosLigacao["comentario"] = $comentario->id;
                                }
                            @endphp
                            <a
                                class="btn btn-sm btn-primary"
                                href="{{ route("metal-thursday.detalhes", $parametrosLigacao) }}"
                            >
                                {{ $comentarioDisponivel ? "Ver comentário" : "Ver publicação" }}
                            </a>
                        @endif

                        @if ($denuncia->estado === 'pendente')
                            <form method="POST" action="{{ route('moderacao.denuncias-comentarios.decidir', $denuncia) }}">
                                @csrf
                                @method('PATCH')
                                <button class="btn btn-sm btn-secondary" type="submit" name="decisao" value="rejeitar">
                                    Rejeitar denúncia
                                </button>
                            </form>

                            <form method="POST" action="{{ route('moderacao.denuncias-comentarios.decidir', $denuncia) }}">
                                @csrf
                                @method('PATCH')
                                <button class="btn btn-sm btn-warning" type="submit" name="decisao" value="aceitar">
                                    Aceitar denúncia
                                </button>
                            </form>
                        @else
                            <span class="small text-white-50">
                                Decisão: {{ $denuncia->estado === 'rejeitada' ? 'Rejeitada' : 'Aceite' }}
                            </span>
                        @endif
                    </div>
                </article>
            @empty
                <p class="mb-0 text-white-50">Não existem denúncias neste estado.</p>
            @endforelse

            @if ($denuncias->hasPages())
                <div class="mt-4">{{ $denuncias->links() }}</div>
            @endif
        </div>
    </div>
</x-layout-aplicacao>
