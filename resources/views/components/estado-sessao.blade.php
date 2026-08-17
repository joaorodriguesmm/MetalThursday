{{--
    Apresenta a primeira mensagem disponível armazenada na sessão.

    A seleção, prioridade e configuração da mensagem são preparadas pela
    classe App\View\Components\EstadoSessao.

    @since 1.0.0
--}}

@if ($temMensagem)
    <div
        {{
            $attributes
                ->except([
                    'role',
                    'aria-live',
                    'aria-atomic',
                ])
                ->class([
                    'alert',
                    $classeAlerta,
                    'alert-dismissible',
                    'fade',
                    'show',
                ])
        }}
        role="{{ $funcaoAcessivel }}"
        aria-live="{{ $prioridadeAnuncio }}"
        aria-atomic="true"
    >
        <span>
            {{ $mensagem }}
        </span>

        <button
            class="btn-close"
            type="button"
            data-bs-dismiss="alert"
            aria-label="Fechar mensagem"
        ></button>
    </div>
@endif
