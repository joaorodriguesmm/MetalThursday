{{--
    Apresenta o formulário de criação de um artista.

    Os dados e os valores iniciais do formulário são preparados pelo
    App\Http\Controllers\Musica\ControladorArtista.

    @since 1.0.0
--}}

@php
    $confirmacaoNomeRepetido = session(
        'confirmacao_nome_repetido'
    );

    $artistasHomonimos = (
        is_array($confirmacaoNomeRepetido)
        && isset(
            $confirmacaoNomeRepetido['artistas_homonimos']
        )
        && is_array(
            $confirmacaoNomeRepetido['artistas_homonimos']
        )
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
            class="alert alert-warning mb-4"
            role="alert"
        >
            <h2 class="h5 alert-heading">
                Possível artista repetido
            </h2>

            <p>
                {{
                    $confirmacaoNomeRepetido['mensagem']
                    ?? 'Já existem artistas com este nome. Confirma se pretendes criar um novo artista.'
                }}
            </p>

            <div class="vstack gap-3">
                @foreach (
                    $artistasHomonimos
                    as $artistaHomonimo
                )
                    <div class="border rounded p-3">
                        <div class="fw-bold mb-2">
                            {{
                                $artistaHomonimo['nome']
                                ?? 'Artista desconhecido'
                            }}
                        </div>

                        <dl class="row mb-0">
                            <dt class="col-sm-4">
                                Ano de início de atividade
                            </dt>

                            <dd class="col-sm-8">
                                Desconhecido
                            </dd>

                            <dt class="col-sm-4">
                                Origem geográfica
                            </dt>

                            <dd class="col-sm-8 mb-0">
                                {{
                                    data_get(
                                        $artistaHomonimo,
                                        'origem_geografica.nome',
                                        'Desconhecida',
                                    )
                                }}
                            </dd>
                        </dl>
                    </div>
                @endforeach
            </div>

            <p class="mb-0 mt-3">
                Se se trata de um artista diferente, podes confirmar
                a criação mesmo utilizando o mesmo nome.
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
    @endpush
</x-layout-aplicacao>
