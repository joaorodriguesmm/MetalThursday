import {
    afterEach,
    describe,
    expect,
    it,
} from 'vitest';

import ValidadorFormulario
    from '../../../resources/js/modulos/ValidadorFormulario.js';

function criarFormulario(conteudo) {
    const formulario = document.createElement('form');

    formulario.id = 'formulario-b25-49';
    formulario.innerHTML = conteudo;

    document.body.append(formulario);

    return formulario;
}

describe('B25.49 — atualizacao do validador', () => {
    afterEach(() => {
        document.body.replaceChildren();
    });

    it('atualiza mensagens e remove erros anteriores', () => {
        const formulario = criarFormulario(`
            <input name="conteudo" value="">
            <button type="submit">Enviar</button>
        `);

        const campo = formulario.querySelector(
            '[name="conteudo"]',
        );

        const validador = new ValidadorFormulario(
            formulario,
            {
                regras: {
                    conteudo: ['obrigatorio'],
                },
                mensagens: {
                    conteudo: {
                        obrigatorio: 'Mensagem anterior.',
                    },
                },
            },
        );

        expect(
            validador.validarTudo({
                focarPrimeiroErro: false,
            }),
        ).toBe(false);

        expect(
            validador.obterErros().conteudo,
        ).toEqual(['Mensagem anterior.']);

        const valorAnterior = campo.value;

        expect(
            validador.atualizarConfiguracao({
                regras: {
                    conteudo: ['obrigatorio'],
                },
                mensagens: {
                    conteudo: {
                        obrigatorio: 'Mensagem atualizada.',
                    },
                },
            }),
        ).toBe(true);

        expect(validador.obterErros()).toEqual({});
        expect(campo.value).toBe(valorAnterior);

        expect(
            validador.validarTudo({
                focarPrimeiroErro: false,
            }),
        ).toBe(false);

        expect(
            validador.obterErros().conteudo,
        ).toEqual(['Mensagem atualizada.']);
    });

    it('reconstroi as dependencias e revalida o campo correto', () => {
        const formulario = criarFormulario(`
            <input name="origem_antiga" value="A">
            <input name="origem_nova" value="B">
            <input name="confirmacao" value="A">
            <button type="submit">Enviar</button>
        `);

        const validador = new ValidadorFormulario(
            formulario,
            {
                regras: {
                    confirmacao: [
                        'confirmado:origem_antiga',
                    ],
                },
            },
        );

        expect(
            validador.dependencias
                .get('origem_antiga')
                ?.has('confirmacao'),
        ).toBe(true);

        expect(
            validador.atualizarConfiguracao({
                regras: {
                    confirmacao: [
                        'confirmado:origem_nova',
                    ],
                },
                mensagens: {},
            }),
        ).toBe(true);

        expect(
            validador.dependencias.has('origem_antiga'),
        ).toBe(false);

        expect(
            validador.dependencias
                .get('origem_nova')
                ?.has('confirmacao'),
        ).toBe(true);

        const evento = new Event('submit', {
            bubbles: true,
            cancelable: true,
        });

        formulario.dispatchEvent(evento);

        expect(evento.defaultPrevented).toBe(true);
        expect(validador.tentouSubmeter).toBe(true);

        expect(
            validador.obterErros().confirmacao,
        ).toBeDefined();

        const origemNova = formulario.querySelector(
            '[name="origem_nova"]',
        );

        origemNova.value = 'A';

        origemNova.dispatchEvent(
            new Event('input', {
                bubbles: true,
            }),
        );

        expect(
            validador.obterErros().confirmacao,
        ).toBeUndefined();
    });

    it('mantem os erros e o estado se nada mudou', () => {
        const formulario = criarFormulario(`
            <input name="conteudo" value="">
            <button type="submit">Enviar</button>
        `);

        const configuracao = {
            regras: {
                conteudo: ['obrigatorio'],
            },
            mensagens: {
                conteudo: {
                    obrigatorio: 'Campo obrigatorio.',
                },
            },
        };

        const validador = new ValidadorFormulario(
            formulario,
            configuracao,
        );

        const evento = new Event('submit', {
            bubbles: true,
            cancelable: true,
        });

        formulario.dispatchEvent(evento);

        expect(evento.defaultPrevented).toBe(true);
        expect(validador.tentouSubmeter).toBe(true);

        const errosAnteriores = validador.obterErros();
        const versaoAnterior = validador.versaoValidacao;

        expect(
            validador.atualizarConfiguracao({
                regras: {
                    conteudo: ['obrigatorio'],
                },
                mensagens: {
                    conteudo: {
                        obrigatorio: 'Campo obrigatorio.',
                    },
                },
            }),
        ).toBe(false);

        expect(
            validador.versaoValidacao,
        ).toBe(versaoAnterior);

        expect(validador.tentouSubmeter).toBe(true);

        expect(
            validador.obterErros(),
        ).toEqual(errosAnteriores);
    });
});
