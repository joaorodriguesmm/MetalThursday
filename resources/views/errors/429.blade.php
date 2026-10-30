{{--
    Apresenta uma mensagem acessível quando o limite de pedidos é atingido.

    @since 2.0.0
--}}

<x-layout-convidado>
    <x-slot name="titulo">
        Demasiados pedidos
    </x-slot>

    <div class="text-center">
        <h1 class="h3 mb-3 fw-normal">
            Demasiados pedidos
        </h1>

        <p class="mb-3 text-muted">
            Fizeste demasiados pedidos num curto espaço de tempo.
            Aguarda um pouco antes de tentares novamente.
        </p>

        <p class="mb-4 text-muted small">
            Erro 429
        </p>

        <a
            class="btn btn-primary w-100"
            href="{{ route('inicio') }}"
        >
            Voltar à página inicial
        </a>
    </div>
</x-layout-convidado>
