import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vitest';

import ValidadorFormulario
    from '../../../resources/js/modulos/ValidadorFormulario.js';

describe('B25.17 — concorrencia no reset do validador', () => {
    let formulario;
    let campo;

    beforeEach(() => {
        vi.useFakeTimers();

        document.body.innerHTML = `
            <form id="formulario-reset-b25-17">
                <textarea name="conteudo">Mensagem original</textarea>
                <button type="submit">Enviar</button>
            </form>
        `;

        formulario = document.getElementById(
            'formulario-reset-b25-17',
        );

        campo = formulario.querySelector(
            '[name="conteudo"]',
        );

        new ValidadorFormulario(
            formulario,
            {
                regras: {
                    conteudo: ['obrigatorio'],
                },
                mensagens: {
                    conteudo: {
                        obrigatorio:
                            'O conteúdo é obrigatório.',
                    },
                },
            },
        );
    });

    afterEach(() => {
        vi.useRealTimers();
        document.body.replaceChildren();
    });

    function submeterInvalido() {
        campo.value = '';

        const evento = new SubmitEvent(
            'submit',
            {
                bubbles: true,
                cancelable: true,
            },
        );

        formulario.dispatchEvent(evento);

        expect(evento.defaultPrevented).toBe(true);

        expect(
            campo.getAttribute('aria-invalid'),
        ).toBe('true');

        expect(
            campo.classList.contains('is-invalid'),
        ).toBe(true);
    }

    it('limpa os erros depois de um reset normal', () => {
        submeterInvalido();

        formulario.reset();

        expect(campo.value).toBe(
            'Mensagem original',
        );

        vi.runAllTimers();

        expect(
            campo.hasAttribute('aria-invalid'),
        ).toBe(false);

        expect(
            campo.classList.contains('is-invalid'),
        ).toBe(false);
    });

    it('preserva erros quando o reset e cancelado', () => {
        submeterInvalido();

        formulario.addEventListener(
            'reset',
            (evento) => {
                evento.preventDefault();
            },
        );

        formulario.reset();

        expect(campo.value).toBe('');

        vi.runAllTimers();

        expect(
            campo.getAttribute('aria-invalid'),
        ).toBe('true');

        expect(
            campo.classList.contains('is-invalid'),
        ).toBe(true);
    });

    it('nao apaga erros de uma submissao posterior ao reset', () => {
        submeterInvalido();

        formulario.reset();

        expect(campo.value).toBe(
            'Mensagem original',
        );

        submeterInvalido();

        vi.runAllTimers();

        expect(campo.value).toBe('');

        expect(
            campo.getAttribute('aria-invalid'),
        ).toBe('true');

        expect(
            campo.classList.contains('is-invalid'),
        ).toBe(true);
    });

    it('preserva erros de interacao posterior ao reset', () => {
        submeterInvalido();

        formulario.reset();

        campo.value = '';

        campo.dispatchEvent(
            new Event('input', {
                bubbles: true,
            }),
        );

        expect(
            campo.getAttribute('aria-invalid'),
        ).toBe('true');

        vi.runAllTimers();

        expect(campo.value).toBe('');

        expect(
            campo.getAttribute('aria-invalid'),
        ).toBe('true');

        expect(
            campo.classList.contains('is-invalid'),
        ).toBe(true);
    });
});
