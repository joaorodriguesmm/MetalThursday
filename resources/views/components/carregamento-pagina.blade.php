{{--
    Apresenta o indicador global de carregamento da página.

    O componente é autónomo em relação aos assets principais para poder ser
    apresentado antes de o CSS e o JavaScript da aplicação estarem disponíveis.

    @since 2.0.0
--}}

<style>
    @keyframes rotacao-carregamento-metal {
        to {
            transform: rotate(360deg);
        }
    }

    @keyframes pulsacao-carregamento-metal {
        0%,
        100% {
            transform: scale(1);
            filter:
                drop-shadow(0 0 4px rgb(109 63 64 / 50%));
        }

        50% {
            transform: scale(1.04);
            filter:
                drop-shadow(0 0 14px rgb(109 63 64 / 90%));
        }
    }

    @media (prefers-reduced-motion: reduce) {
        #carregamento-pagina .carregamento-metal-anel,
        #carregamento-pagina .carregamento-metal-logotipo {
            animation: none !important;
        }
    }
</style>

<div
    id="carregamento-pagina"
    role="status"
    aria-live="polite"
    style="
        position: fixed;
        inset: 0;
        z-index: 9999;
        display: none;
        align-items: center;
        justify-content: center;
        background-color: #282424;
        opacity: 1;
        transition: opacity 150ms ease;
    "
>
    <div
        style="
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 180px;
            height: 180px;
        "
    >
        <div
            class="carregamento-metal-anel"
            aria-hidden="true"
            style="
                position: absolute;
                inset: 0;
                border: 3px solid rgb(109 63 64 / 20%);
                border-top-color: #6d3f40;
                border-right-color: #a55f61;
                border-radius: 50%;
                animation:
                    rotacao-carregamento-metal
                    1.1s
                    linear
                    infinite;
            "
        ></div>

        <img
            class="carregamento-metal-logotipo"
            src="{{ asset('images/logo.png') }}"
            alt=""
            aria-hidden="true"
            style="
                position: relative;
                z-index: 1;
                display: block;
                width: 125px;
                height: auto;
                animation:
                    pulsacao-carregamento-metal
                    1.5s
                    ease-in-out
                    infinite;
            "
        >
    </div>

    <span
        style="
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            white-space: nowrap;
            border: 0;
            clip-path: inset(50%);
        "
    >
        A carregar…
    </span>
</div>

<script>
    (() => {
        const carregamentoPagina = document.getElementById(
            'carregamento-pagina',
        );

        if (!(carregamentoPagina instanceof HTMLElement)) {
            return;
        }

        carregamentoPagina.style.display = 'flex';

        const removerCarregamento = () => {
            carregamentoPagina.style.opacity = '0';

            window.setTimeout(() => {
                carregamentoPagina.remove();
            }, 150);
        };

        if (document.readyState === 'complete') {
            removerCarregamento();

            return;
        }

        window.addEventListener(
            'load',
            removerCarregamento,
            {
                once: true,
            },
        );
    })();
</script>
