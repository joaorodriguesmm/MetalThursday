import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vitest';

import Modal from 'bootstrap/js/dist/modal';

import axios from '../../../resources/js/modulos/ClienteHttp.js';

import TratadorFormularioAjax
    from '../../../resources/js/modulos/TratadorFormularioAjax.js';

describe('B27.10 — sucesso tardio apos reabertura', () => {
    let modal;
    let instancia;
    let campo;

    beforeEach(() => {
        document.body.innerHTML = `
            <div id="modal-sucesso-tardio" class="modal">
                <div class="modal-dialog">
                    <form id="formulario-sucesso-tardio">
                        <label for="nome-sucesso-tardio">
                            Nome
                        </label>

                        <input
                            id="nome-sucesso-tardio"
                            name="nome"
                            value=""
                        >

                        <button type="submit">
                            Guardar
                        </button>
                    </form>
                </div>
            </div>
        `;

        modal = document.getElementById(
            'modal-sucesso-tardio',
        );

        campo = document.getElementById(
            'nome-sucesso-tardio',
        );

        instancia = Modal.getOrCreateInstance(
            modal,
            {
                backdrop: false,
                focus: false,
            },
        );
    });

    afterEach(() => {
        instancia?.dispose();

        vi.restoreAllMocks();

        document.body.replaceChildren();
        document.body.classList.remove('modal-open');
        document.body.style.removeProperty('overflow');
        document.body.style.removeProperty('padding-right');
    });

    it.each([false, true])(
        'nao altera o modal apos sucesso tardio (reabertura: %s)',
        async (reabrir) => {
            let resolverPedido;

            const pedidoPendente = new Promise(
                (resolver) => {
                    resolverPedido = resolver;
                },
            );

            const pedido = vi.spyOn(
                axios,
                'post',
            ).mockReturnValueOnce(pedidoPendente);

            const aoSucesso = vi.fn();

            const tratador = new TratadorFormularioAjax(
                'formulario-sucesso-tardio',
                '/teste/guardar',
                aoSucesso,
            );

            const emitirSucesso = vi.spyOn(
                tratador,
                'emitirEventoSucesso',
            );

            vi.spyOn(
                tratador,
                'mostrarMensagemSucesso',
            ).mockImplementation(() => {});

            vi.spyOn(
                tratador,
                'mostrarAvisoAtualizacaoInterface',
            ).mockImplementation(() => {});

            instancia.show();

            campo.value = 'Mesmo nome';

            const submissaoAnterior =
                tratador.submeter();

            expect(pedido).toHaveBeenCalledTimes(1);
            expect(tratador.emSubmissao).toBe(true);

            const ocultado = new Promise((resolver) => {
                modal.addEventListener(
                    'hidden.bs.modal',
                    resolver,
                    { once: true },
                );
            });

            instancia.hide();

            await ocultado;

            expect(
                modal.classList.contains('show'),
            ).toBe(false);

            // A abertura seguinte pertence a outro ciclo.
            if (reabrir) {
                instancia.show();
                campo.value = 'Mesmo nome';
            }

            expect(
                modal.classList.contains('show'),
            ).toBe(reabrir);

            // Só agora termina o pedido da abertura antiga.
            resolverPedido({
                data: {
                    mensagem: 'Operacao concluida.',
                },
            });

            await submissaoAnterior;

            // O sucesso antigo nao altera o ciclo atual.
            expect(
                modal.classList.contains('show'),
            ).toBe(reabrir);

            // A conclusao no servidor continua a ser comunicada.
            expect(emitirSucesso).toHaveBeenCalledTimes(1);

            expect(emitirSucesso).toHaveBeenCalledWith({
                mensagem: 'Operacao concluida.',
            });

            // O callback visual da abertura anterior e ignorado.
            expect(aoSucesso).not.toHaveBeenCalled();

            expect(campo.value).toBe('Mesmo nome');

            expect(tratador.emSubmissao).toBe(false);
        },
    );
    it.each([false, true])(
        'callback assincrono termina depois do fecho (reabertura: %s)',
        async (reabrir) => {
            let concluirCallback;

            const callbackPendente = new Promise((resolver) => {
                concluirCallback = resolver;
            });

            const aoSucesso = vi.fn(
                () => callbackPendente,
            );

            const pedido = vi.spyOn(
                axios,
                'post',
            ).mockResolvedValueOnce({
                data: {
                    mensagem: 'Operacao concluida.',
                },
            });

            const tratador = new TratadorFormularioAjax(
                'formulario-sucesso-tardio',
                '/teste/guardar',
                aoSucesso,
            );

            const emitirSucesso = vi.spyOn(
                tratador,
                'emitirEventoSucesso',
            );

            const mensagemSucesso = vi.spyOn(
                tratador,
                'mostrarMensagemSucesso',
            ).mockImplementation(() => {});

            const aviso = vi.spyOn(
                tratador,
                'mostrarAvisoAtualizacaoInterface',
            ).mockImplementation(() => {});

            const finalizar = vi.spyOn(
                tratador,
                'finalizarSubmissao',
            );

            const botao = modal.querySelector(
                'button[type="submit"]',
            );

            instancia.show();

            campo.value = 'Mesmo nome';

            const submissao = tratador.submeter();

            await vi.waitFor(() => {
                expect(aoSucesso).toHaveBeenCalledTimes(1);
            });

            expect(pedido).toHaveBeenCalledTimes(1);
            expect(emitirSucesso).toHaveBeenCalledTimes(1);
            expect(tratador.emSubmissao).toBe(true);
            expect(botao.disabled).toBe(true);

            const ocultado = new Promise((resolver) => {
                modal.addEventListener(
                    'hidden.bs.modal',
                    resolver,
                    { once: true },
                );
            });

            instancia.hide();

            await ocultado;

            if (reabrir) {
                instancia.show();
                campo.value = 'Mesmo nome';
            }

            expect(
                modal.classList.contains('show'),
            ).toBe(reabrir);

            concluirCallback();

            await submissao;

            expect(
                modal.classList.contains('show'),
            ).toBe(reabrir);

            if (reabrir) {
                expect(campo.value).toBe('Mesmo nome');
            }

            expect(emitirSucesso).toHaveBeenCalledTimes(1);
            expect(aoSucesso).toHaveBeenCalledTimes(1);
            expect(finalizar).not.toHaveBeenCalled();
            expect(mensagemSucesso).not.toHaveBeenCalled();
            expect(aviso).not.toHaveBeenCalled();
            expect(tratador.emSubmissao).toBe(false);
            expect(botao.disabled).toBe(false);
        },
    );

});
