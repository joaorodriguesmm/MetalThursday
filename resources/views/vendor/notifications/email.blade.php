{{--
    Define a estrutura comum das notificações enviadas por e-mail.

    Os nomes das variáveis pertencem ao contrato interno das notificações
    do Laravel e, por isso, devem permanecer inalterados.

    @since 1.0.0
    @version 2.0.0
--}}

<x-mail::message>
@if (! empty($greeting))
# {{ $greeting }}
@elseif ($level === 'error')
# Ocorreu um problema!
@else
# Olá!
@endif

{{-- Linhas introdutórias --}}
@foreach ($introLines as $line)
{{ $line }}

@endforeach

{{-- Botão de ação --}}
@isset($actionText)
<x-mail::button
    :url="$actionUrl"
    :color="
        in_array(
            $level,
            [
                'success',
                'error',
            ],
            true,
        )
            ? $level
            : 'primary'
    "
>
{{ $actionText }}
</x-mail::button>
@endisset

{{-- Linhas finais --}}
@foreach ($outroLines as $line)
{{ $line }}

@endforeach

{{-- Despedida --}}
@if (! empty($salutation))
{{ $salutation }}
@endif

{{-- Ligação alternativa --}}
@isset($actionText)
<x-slot:subcopy>
Se estás a ter problemas ao clicar no botão "{{ $actionText }}",
copia e cola o seguinte endereço no teu navegador:

<span class="break-all">[{{ $displayableActionUrl }}]({{ $actionUrl }})</span>
</x-slot:subcopy>
@endisset
</x-mail::message>
