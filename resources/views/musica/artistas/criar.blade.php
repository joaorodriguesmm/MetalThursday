{{--
    Apresenta o formulário de criação de um artista.

    @since 1.0.0
--}}

@php
    $confirmacaoNomeRepetido = session(
        'confirmacao_nome_repetido'
    );

    $artistasHomonimos = (
        is_array($confirmacaoNomeRepetido)
        && isset($confirmacaoNomeRepetido['artistas_homonimos'])
        && is_array($confirmacaoNomeRepetido['artistas_homonimos'])
    )
        ? $confirmacaoNomeRepetido['artistas_homonimos']
        : [];

    $exigeConfirmacaoNomeRepetido =
        $artistasHomonimos !== [];
@endphp

<x-layout-aplicacao>
    <x-slot name="titulo">
        Criar artista
    </x-slot>

    <x-slot name="cabecalho">
        <h1 class="h4 mb-0 fw-bold">
            Criar novo artista
        </h1>
    </x-slot>

    <x-estado-sessao class="mb-4" />

    @if ($exigeConfirmacaoNomeRepetido)
        <div
            class="aviso-artista-homonimo"
            role="alert"
        >
            <div class="aviso-artista-homonimo__cabecalho">
                <i
                    class="bi bi-exclamation-triangle-fill aviso-artista-homonimo__icone"
                    aria-hidden="true"
                ></i>

                <div>
                    <div class="aviso-artista-homonimo__titulo">
                        Artista com o mesmo nome
                    </div>

                    <p class="aviso-artista-homonimo__mensagem">
                        {{
                            $confirmacaoNomeRepetido['mensagem']
                            ?? 'Já existem artistas com este nome. Confirma se pretendes criar um novo artista.'
                        }}
                    </p>
                </div>
            </div>

            <div class="aviso-artista-homonimo__lista">
                @foreach ($artistasHomonimos as $artistaHomonimo)
                    <div
                        class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-1 gap-sm-3 px-3 py-2"
                    >
                        <strong>
                            {{ $artistaHomonimo['nome'] ?? 'Artista desconhecido' }}
                        </strong>

                        <span class="text-muted text-sm-end">
                            {{
                                data_get(
                                    $artistaHomonimo,
                                    'origem_geografica.nome',
                                )
                                ?? 'Origem não indicada'
                            }}

                            <span aria-hidden="true">
                                ·
                            </span>

                            @if (
                                isset($artistaHomonimo['ano_inicio_atividade'])
                                && is_numeric($artistaHomonimo['ano_inicio_atividade'])
                            )
                                Início em {{ $artistaHomonimo['ano_inicio_atividade'] }}
                            @else
                                Ano de início desconhecido
                            @endif
                        </span>
                    </div>
                @endforeach
            </div>

            <p class="aviso-artista-homonimo__nota">
                Se for um artista diferente, volta a confirmar a criação.
            </p>
        </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body p-4">
            @include(
                'musica.artistas._formulario'
            )
        </div>
    </div>

    @include(
        'musica.generos._modal-criar'
    )

    @push('scripts-pagina')
        @vite(
            'resources/js/paginas/entidades.js'
        )

        @vite(
            'resources/js/paginas/perfilArtista.js'
        )
    @endpush
</x-layout-aplicacao>
