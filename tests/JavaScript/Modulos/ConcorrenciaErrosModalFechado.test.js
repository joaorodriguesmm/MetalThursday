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

function criarErroValidacao() {
    return new axios.AxiosError(
        'Dados invalidos.',
        'ERR_BAD_REQUEST',
        {},
        {},
        {
            data: {
                errors: {
                    nome: ['O nome nao e valido.'],
                },
            },
            status: 422,
            statusText: 'Unprocessable Content',
            headers: {},
            config: {},
        },
    );
}

describe('B27.10 — respostas tardias em modal fechado', () => {
    let modal;
    let instancia;
    let campo;
    let mensagem;

    beforeEach(() => {
        document.body.innerHTML = `
            <div id="modal-teste" class="modal">
                <div class="modal-dialog">
                    <form id="formulario-teste" novalidate>
                        <label for="nome-teste">Nome</label>

                        <input
                            id="nome-teste"
                            name="nome"
                            aria-describedby="erro-nome-teste"
                        >

                        <div
                            id="erro-nome-teste"
                            class="invalid-feedback"
                            hidden
                        ></div>

                        <button type="submit">
                            Guardar
                        </button>
                    </form>
                </div>
            </div>
        `;

        modal = document.getElementById('modal-teste');
        campo = document.getElementById('nome-teste');

        mensagem = document.getElementById(
            'erro-nome-teste',
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
        'ignora erro 422 apos fecho (reabertura: %s)',
        async (reabrir) => {
            let rejeitarPedido;

            const pedidoPendente = new Promise(
                (_resolver, rejeitar) => {
                    rejeitarPedido = rejeitar;
                },
            );

            const pedido = vi.spyOn(
                axios,
                'post',
            ).mockReturnValueOnce(pedidoPendente);

            instancia.show();

            campo.value = 'Nome anterior';

            const tratador = new TratadorFormularioAjax(
                'formulario-teste',
                '/teste/guardar',
            );

            const submissao = tratador.submeter();

            expect(pedido).toHaveBeenCalledTimes(1);

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
                campo.value = 'Nome da nova abertura';
            }

            expect(
                modal.classList.contains('show'),
            ).toBe(reabrir);

            // A resposta pertence ao ciclo anterior.
            rejeitarPedido(criarErroValidacao());

            await submissao;

            expect(
                campo.hasAttribute('aria-invalid'),
            ).toBe(false);

            expect(mensagem.textContent).toBe('');
            expect(mensagem.hidden).toBe(true);

            if (reabrir) {
                expect(campo.value).toBe(
                    'Nome da nova abertura',
                );
            }

            expect(tratador.emSubmissao).toBe(false);
        },
    );
    it(
        'mantem erro 422 quando fecho e cancelado',
        async () => {
            let rejeitarPedido;

            const pedidoPendente = new Promise(
                (_resolver, rejeitar) => {
                    rejeitarPedido = rejeitar;
                },
            );

            const pedido = vi.spyOn(
                axios,
                'post',
            ).mockReturnValueOnce(pedidoPendente);

            instancia.show();

            const tratador = new TratadorFormularioAjax(
                'formulario-teste',
                '/teste/guardar',
            );

            const submissao = tratador.submeter();

            expect(pedido).toHaveBeenCalledTimes(1);

            let fechoTentado = false;

            modal.addEventListener(
                'hide.bs.modal',
                (evento) => {
                    fechoTentado = true;
                    evento.preventDefault();
                },
                { once: true },
            );

            instancia.hide();

            expect(fechoTentado).toBe(true);

            expect(
                modal.classList.contains('show'),
            ).toBe(true);

            rejeitarPedido(criarErroValidacao());

            await submissao;

            expect(
                campo.getAttribute('aria-invalid'),
            ).toBe('true');

            expect(mensagem.textContent).toBe(
                'O nome nao e valido.',
            );

            expect(mensagem.hidden).toBe(false);

            expect(document.activeElement).toBe(campo);

            expect(tratador.emSubmissao).toBe(false);
        },
    );

});
