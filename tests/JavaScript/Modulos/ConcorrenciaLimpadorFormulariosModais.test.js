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
                listeners.push(listener);
            }

            adicionarOriginal(
                tipo,
                listener,
                opcoes,
            );
        });
    });

    afterEach(() => {
        listeners.forEach((listener) => {
            document.removeEventListener(
                'hidden.bs.modal',
                listener,
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
});
