{{--
    Apresenta as secções de MetalThursday numa tabela simplificada.

    Os valores das relações, contagens, médias e descrições dos tooltips são
    preparados pela classe
    App\View\Components\MetalThursday\TabelaVistaSimplificada.

    @since 1.0.0
    @version 3.0.0
--}}

<div
    {{
        $attributes->class([
            'card',
            'shadow-sm',
            'bg-dark',
        ])
    }}
>
    <div class="table-responsive">
        <table
            class="table table-dark table-striped table-hover mb-0 align-middle tabela-vista-simplificada"
        >
            <caption class="visually-hidden">
                Secções de MetalThursday apresentadas na vista simplificada
            </caption>

            <thead>
                <tr>
                    <th scope="col">
                        Data
                    </th>

                    <th scope="col">
                        Autor
                    </th>

                    <th scope="col">
                        Banda
                    </th>

                    <th scope="col">
                        País
                    </th>

                    <th scope="col">
                        Título
                    </th>

                    <th scope="col">
                        Ano
                    </th>

                    <th scope="col">
                        Géneros
                    </th>

                    <th
                        class="text-center"
                        scope="col"
                    >
                        Ligação
                    </th>

                    <th
                        class="text-center"
                        scope="col"
                    >
                        Avaliação
                    </th>

                    <th
                        class="text-center"
                        scope="col"
                    >
                        Ouvido
                    </th>
                </tr>
            </thead>

            <tbody>
                @forelse ($linhas as $linha)
                    <tr id="seccao-simplificada-{{ $linha['identificador'] }}">
                        <td>
                            @if ($linha['dataIso'] !== null)
                                <time datetime="{{ $linha['dataIso'] }}">
                                    {{ $linha['dataApresentacao'] }}
                                </time>
                            @else
                                <span class="text-white-50">
                                    {{ $linha['dataApresentacao'] }}
                                </span>
                            @endif
                        </td>

                        <td>
                            {{ $linha['nomeAutor'] }}
                        </td>

                        <td>
                            {{ $linha['nomeBanda'] }}
                        </td>

                        <td>
                            {{ $linha['nomePais'] }}
                        </td>

                        <th scope="row">
                            {{ $linha['titulo'] }}

                            @if ($linha['nomeTipoSeccao'] !== null)
                                <span class="text-white-50">
                                    ({{ $linha['nomeTipoSeccao'] }})
                                </span>
                            @endif
                        </th>

                        <td>
                            {{ $linha['ano'] }}
                        </td>

                        <td>
                            {{ $linha['nomesGeneros'] }}
                        </td>

                        <td class="text-center">
                            @if ($linha['ligacao'] !== null)
                                <a
                                    class="btn btn-sm btn-outline-light"
                                    href="{{ $linha['ligacao'] }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    aria-label="Abrir a ligação de {{ $linha['titulo'] }} num novo separador"
                                >
                                    Abrir
                                </a>
                            @else
                                <span aria-label="Sem ligação">
                                    —
                                </span>
                            @endif
                        </td>

                        <td class="text-center">
                            <span
                                class="apresentacao-avaliacoes"
                                data-bs-toggle="tooltip"
                                data-bs-html="true"
                                data-bs-title="{!!
                                    $linha['avaliacao']['descricao']->toHtml()
                                !!}"
                                aria-label="{{
                                    $linha['avaliacao']['descricaoAcessivel']
                                }}"
                            >
                                <i
                                    class="bi bi-star-fill text-warning"
                                    aria-hidden="true"
                                ></i>

                                <strong class="media-avaliacoes">
                                    {{ $linha['avaliacao']['media'] }}
                                </strong>

                                <span class="text-white-50">
                                    ({{ $linha['avaliacao']['quantidade'] }})
                                </span>
                            </span>
                        </td>

                        <td class="text-center">
                            <span
                                class="apresentacao-audicoes"
                                data-bs-toggle="tooltip"
                                data-bs-html="true"
                                data-bs-title="{!!
                                    $linha['audicoes']['descricao']->toHtml()
                                !!}"
                                aria-label="{{
                                    $linha['audicoes']['descricaoAcessivel']
                                }}"
                            >
                                <i
                                    class="bi bi-headphones"
                                    aria-hidden="true"
                                ></i>

                                <span class="quantidade-audicoes">
                                    {{ $linha['audicoes']['quantidade'] }}
                                </span>
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td
                            class="text-center py-4"
                            colspan="10"
                        >
                            Nenhum resultado encontrado.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if ($seccoesSimplificadas->hasPages())
    <nav
        class="mt-4"
        aria-label="Paginação das secções"
    >
        {{
            $seccoesSimplificadas->links(
                'vendor.pagination.bootstrap-5',
            )
        }}
    </nav>
@endif
