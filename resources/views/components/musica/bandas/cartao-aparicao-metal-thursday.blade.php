{{--
    Apresenta uma aparição de uma banda numa MetalThursday.

    Todos os valores são preparados pela classe
    App\View\Components\Musica\Bandas\CartaoAparicaoMetalThursday.

    @since 2.0.0
--}}

<article
    id="aparicao-banda-{{ $dados['identificador'] }}"
    {{
        $attributes
            ->except([
                'id',
            ])
            ->class([
                'card',
                'shadow-sm',
                'mb-3',
            ])
    }}
>
    <div class="card-body">
        <h3 class="h5 card-title text-danger">
            {{ $dados['titulo'] }}

            @if ($dados['ano'] !== null)
                ({{ $dados['ano'] }})
            @endif
        </h3>

        <p class="card-subtitle mb-2 text-muted">
            {{ $dados['nomeTipoSeccao'] }}

            na

            @if ($dados['enderecoMetalThursday'] !== null)
                <a href="{{ $dados['enderecoMetalThursday'] }}">
                    MetalThursday de {{ $dados['nomeAutor'] }}
                </a>
            @else
                <span>
                    MetalThursday de {{ $dados['nomeAutor'] }}
                    (eliminada)
                </span>
            @endif

            <time datetime="{{ $dados['dataIso'] }}">
                ({{ $dados['dataApresentacao'] }})
            </time>
        </p>

        @if ($dados['descricao'] !== null)
            <p class="card-text small fst-italic">
                “{!! $dados['descricao']->toHtml() !!}”
            </p>
        @endif

        @if ($dados['ligacao'] !== null)
            <a
                class="btn btn-sm btn-secondary"
                href="{{ $dados['ligacao'] }}"
                target="_blank"
                rel="noopener noreferrer"
                aria-label="Abrir a ligação externa de {{ $dados['titulo'] }} num novo separador"
            >
                Abrir ligação externa
            </a>
        @endif
    </div>
</article>
