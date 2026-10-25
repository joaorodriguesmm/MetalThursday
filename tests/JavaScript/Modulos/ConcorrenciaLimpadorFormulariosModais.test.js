import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vitest';

import LimpadorFormulariosModais
    from '../../../resources/js/modulos/LimpadorFormulariosModais.js';

describe('B25.18 — ciclo de vida dos formularios modais', () => {
    let modal;
    let formulario;
    let campo;
    let feedback;
    let listeners;

    beforeEach(() => {
        document.body.innerHTML = `
            <div id="modal-teste-b25-18" class="modal">
                <form id="formulario-teste-b25-18">
                    <textarea
                        name="conteudo"
                        class="is-invalid"
                        aria-invalid="true"
                    >Mensagem original</textarea>

                    <div class="invalid-feedback d-block">
                        Erro de validacao
                    </div>

                    <button type="submit">Enviar</button>
                </form>
            </div>
        `;

        modal = document.getElementById(
            'modal-teste-b25-18',
        );

        formulario = document.getElementById(
            'formulario-teste-b25-18',
        );

        campo = formulario.querySelector(
            '[name="conteudo"]',
        );

        feedback = formulario.querySelector(
            '.invalid-feedback',
        );

        listeners = [];

        const adicionarOriginal =
            document.addEventListener.bind(document);

        vi.spyOn(
            document,
            'addEventListener',
        ).mockImplementation((tipo, listener, opcoes) => {
            if (tipo === 'hidden.bs.modal') {
                listeners.push({ listener, opcoes });
            }

            adicionarOriginal(
                tipo,
                listener,
                opcoes,
            );
        });
    });

    afterEach(() => {
        listeners.forEach(({ listener, opcoes }) => {
            document.removeEventListener(
                'hidden.bs.modal',
                listener,
                opcoes,
            );
        });

        vi.restoreAllMocks();
        document.body.replaceChildren();
    });

    function fecharModal() {
        modal.dispatchEvent(
            new Event(
                'hidden.bs.modal',
                {
                    bubbles: true,
                },
            ),
        );
    }

    it('limpa normalmente um formulario quando o modal fecha', () => {
        new LimpadorFormulariosModais();

        campo.value = 'Mensagem alterada';

        fecharModal();

        expect(campo.value).toBe(
            'Mensagem original',
        );

        expect(
            campo.classList.contains('is-invalid'),
        ).toBe(false);

        expect(
            campo.hasAttribute('aria-invalid'),
        ).toBe(false);

        expect(feedback.textContent).toBe('');

        expect(
            feedback.classList.contains('d-block'),
        ).toBe(false);
    });

    it('respeita a preservacao solicitada pelo modal', () => {
        new LimpadorFormulariosModais();

        campo.value = 'Rascunho a preservar';

        modal.setAttribute(
            'data-preservar-formularios-ao-fechar',
            '',
        );

        fecharModal();

        expect(campo.value).toBe(
            'Rascunho a preservar',
        );

        expect(
            campo.classList.contains('is-invalid'),
        ).toBe(true);

        expect(
            modal.hasAttribute(
                'data-preservar-formularios-ao-fechar',
            ),
        ).toBe(false);
    });

    it('nao executa o reset duas vezes apos inicializacao duplicada', () => {
        const reporFormulario = vi.spyOn(
            formulario,
            'reset',
        );

        new LimpadorFormulariosModais();
        new LimpadorFormulariosModais();

        campo.value = 'Mensagem modificada';

        fecharModal();

        expect(reporFormulario).toHaveBeenCalledTimes(1);

        expect(campo.value).toBe(
            'Mensagem original',
        );
    });

    it('preserva os dados mesmo com duas inicializacoes', () => {
        new LimpadorFormulariosModais();
        new LimpadorFormulariosModais();

        campo.value = 'Rascunho importante';

        modal.setAttribute(
            'data-preservar-formularios-ao-fechar',
            '',
        );

        fecharModal();

        expect(campo.value).toBe(
            'Rascunho importante',
        );

        expect(
            campo.classList.contains('is-invalid'),
        ).toBe(true);
    });

    it('nao remove erros quando o reset e cancelado', () => {
        new LimpadorFormulariosModais();

        campo.value = 'Dados por preservar';

        let resetCancelado = false;

        formulario.addEventListener(
            'reset',
            (evento) => {
                evento.preventDefault();

                resetCancelado =
                    evento.defaultPrevented;
            },
        );

        fecharModal();

        expect(resetCancelado).toBe(true);

        expect(campo.value).toBe(
            'Dados por preservar',
        );

        expect(
            campo.classList.contains('is-invalid'),
        ).toBe(true);

        expect(
            campo.getAttribute('aria-invalid'),
        ).toBe('true');

        expect(
            feedback.classList.contains('d-block'),
        ).toBe(true);

        expect(
            feedback.textContent.trim(),
        ).toBe('Erro de validacao');
    });

    it('limpa apenas uma vez com seletores sobrepostos', () => {
        const repor = vi.spyOn(
            formulario,
            'reset',
        );

        new LimpadorFormulariosModais('.modal');

        new LimpadorFormulariosModais(
            '#modal-teste-b25-18',
        );

        campo.value = 'Mensagem alterada';

        fecharModal();

        expect(repor).toHaveBeenCalledTimes(1);

        expect(campo.value).toBe(
            'Mensagem original',
        );
    });

    it('respeita reset cancelado por listener do modal', () => {
        new LimpadorFormulariosModais();

        campo.value = 'Texto a preservar';

        modal.addEventListener(
            'reset',
            (evento) => {
                evento.preventDefault();
            },
        );

        fecharModal();

        expect(campo.value).toBe(
            'Texto a preservar',
        );

        expect(
            campo.getAttribute('aria-invalid'),
        ).toBe('true');

        expect(
            campo.classList.contains('is-invalid'),
        ).toBe(true);

        expect(
            feedback.textContent.trim(),
        ).toBe('Erro de validacao');
    });

    it(
        'consome preservacao antiga quando o modal ja reabriu',
        () => {
            new LimpadorFormulariosModais();

            // Preservacao associada ao fecho anterior.
            modal.setAttribute(
                'data-preservar-formularios-ao-fechar',
                '',
            );

            // Outro listener reabriu o modal antes
            // de o limpador receber o hidden anterior.
            modal.classList.add('show');

            campo.value = 'Novo rascunho';

            fecharModal();

            expect(campo.value).toBe(
                'Novo rascunho',
            );

            // A preservacao do fecho antigo deve
            // ficar consumida, apesar da reabertura.
            expect(
                modal.hasAttribute(
                    'data-preservar-formularios-ao-fechar',
                ),
            ).toBe(false);

            // Num fecho posterior, sem novo pedido
            // de preservacao, a limpeza deve ocorrer.
            modal.classList.remove('show');

            campo.value = 'Dados do fecho seguinte';

            fecharModal();

            expect(campo.value).toBe(
                'Mensagem original',
            );

            expect(
                campo.classList.contains('is-invalid'),
            ).toBe(false);
        },
    );


    it(
        'respeita uma nova preservacao depois da reabertura',
        () => {
            new LimpadorFormulariosModais();

            modal.setAttribute(
                'data-preservar-formularios-ao-fechar',
                '',
            );

            modal.classList.add('show');

            campo.value = 'Rascunho da reabertura';

            fecharModal();

            expect(campo.value).toBe(
                'Rascunho da reabertura',
            );

            expect(
                modal.hasAttribute(
                    'data-preservar-formularios-ao-fechar',
                ),
            ).toBe(false);

            // Depois do fecho antigo ter sido
            // processado, surge um novo pedido.
            modal.setAttribute(
                'data-preservar-formularios-ao-fechar',
                '',
            );

            modal.classList.remove('show');

            campo.value = 'Novo pedido de preservacao';

            fecharModal();

            expect(campo.value).toBe(
                'Novo pedido de preservacao',
            );

            expect(
                modal.hasAttribute(
                    'data-preservar-formularios-ao-fechar',
                ),
            ).toBe(false);

            // O pedido novo tambem foi consumido:
            // outro fecho deve voltar a limpar.
            campo.value = 'Dados a limpar';

            fecharModal();

            expect(campo.value).toBe(
                'Mensagem original',
            );

            expect(
                campo.classList.contains('is-invalid'),
            ).toBe(false);
        },
    );


    it.each([false, true])(
        'preserva pedido criado durante hidden com atributo anterior: %s',
        (preservacaoAnterior) => {
            new LimpadorFormulariosModais();

            const atributo =
                'data-preservar-formularios-ao-fechar';

            if (preservacaoAnterior) {
                modal.setAttribute(atributo, '');
            }

            campo.value = 'Dados do primeiro fecho';

            modal.addEventListener(
                'hidden.bs.modal',
                () => {
                    // Outro listener iniciou um novo ciclo.
                    modal.classList.add('show');

                    campo.value = 'Novo rascunho';

                    // Este pedido pertence ao NOVO fecho.
                    modal.setAttribute(atributo, '');
                },
                { once: true },
            );

            fecharModal();

            expect(
                modal.classList.contains('show'),
            ).toBe(true);

            expect(campo.value).toBe('Novo rascunho');

            // O limpador nao pode consumir um pedido
            // que foi criado para o ciclo seguinte.
            expect(modal.hasAttribute(atributo))
                .toBe(true);

            // O fecho seguinte deve preservar
            // o rascunho e consumir o pedido novo.
            modal.classList.remove('show');

            fecharModal();

            expect(campo.value).toBe('Novo rascunho');

            expect(modal.hasAttribute(atributo))
                .toBe(false);

            // Um terceiro fecho, sem preservacao,
            // deve executar novamente a limpeza.
            campo.value = 'Dados a limpar';

            fecharModal();

            expect(campo.value).toBe(
                'Mensagem original',
            );
        },
    );

});
