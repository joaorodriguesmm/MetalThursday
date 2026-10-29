import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
} from 'vitest';

import InicializadorTomSelect
    from '../../../resources/js/modulos/InicializadorTomSelect.js';

import TratadorFormularioAjax
    from '../../../resources/js/modulos/TratadorFormularioAjax.js';

describe('B27.7 — acessibilidade do Tom Select', () => {
    let campo;
    let controlo;
    let formulario;

    beforeEach(() => {
        document.body.innerHTML = `
            <form id="formulario-acessibilidade">
                <label for="tipo-seccao">
                    Tipo de secção
                </label>

                <select
                    id="tipo-seccao"
                    name="tipo_seccao_id"
                    class="tom-select-unico"
                    aria-describedby="erro-tipo-seccao"
                    required
                >
                    <option value="">
                        Seleciona um tipo
                    </option>

                    <option value="1">
                        Álbum
                    </option>
                </select>

                <div
                    id="erro-tipo-seccao"
                    class="invalid-feedback"
                >
                    Seleciona um tipo de secção.
                </div>
            </form>
        `;

        formulario = document.getElementById(
            'formulario-acessibilidade',
        );

        campo = document.getElementById(
            'tipo-seccao',
        );

        new InicializadorTomSelect();

        controlo = campo.tomselect?.control_input;
    });

    afterEach(() => {
        campo?.tomselect?.destroy();

        document.body.replaceChildren();
    });

    it(
        'associa a mensagem de erro ao controlo acessivel',
        () => {
            expect(controlo).toBeInstanceOf(
                HTMLElement,
            );

            const descricoes = (
                controlo.getAttribute(
                    'aria-describedby',
                ) ?? ''
            )
                .split(/\s+/u)
                .filter(Boolean);

            expect(descricoes).toContain(
                'erro-tipo-seccao',
            );
        },
    );

    it(
        'comunica o estado invalido inicial devolvido pelo servidor',
        () => {
            campo.tomselect.destroy();

            campo.classList.add('is-invalid');

            campo.setAttribute(
                'aria-invalid',
                'true',
            );

            new InicializadorTomSelect();

            controlo = campo.tomselect.control_input;

            expect(
                campo.getAttribute('aria-invalid'),
            ).toBe('true');

            expect(
                controlo.getAttribute('aria-invalid'),
            ).toBe('true');

            expect(
                controlo.getAttribute('aria-describedby'),
            ).toContain('erro-tipo-seccao');
        },
    );

    it(
        'preserva descricoes ao reutilizar uma instancia',
        () => {
            const ajuda = document.createElement('p');

            ajuda.id = 'ajuda-adicional';
            ajuda.textContent = 'Informação adicional.';

            formulario.append(ajuda);

            controlo.setAttribute(
                'aria-describedby',
                'ajuda-adicional',
            );

            new InicializadorTomSelect();

            const descricoes = (
                controlo.getAttribute('aria-describedby') ?? ''
            ).split(/\s+/u).filter(Boolean);

            expect(descricoes).toContain(
                'ajuda-adicional',
            );

            expect(descricoes).toContain(
                'erro-tipo-seccao',
            );

            expect(
                new Set(descricoes).size,
            ).toBe(descricoes.length);
        },
    );

    it(
        'marca o controlo acessivel como invalido no AJAX',
        () => {
            expect(controlo).toBeInstanceOf(
                HTMLElement,
            );

            const tratador = Object.create(
                TratadorFormularioAjax.prototype,
            );

            tratador.formulario = formulario;

            tratador.definirCampoInvalido(
                campo,
            );

            expect(
                campo.getAttribute('aria-invalid'),
            ).toBe('true');

            expect(
                controlo.getAttribute('aria-invalid'),
            ).toBe('true');
        },
    );
    it(
        'limpa o estado invalido do controlo acessivel',
        () => {
            const tratador = Object.create(
                TratadorFormularioAjax.prototype,
            );

            tratador.formulario = formulario;

            tratador.definirCampoInvalido(campo);

            expect(
                controlo.getAttribute('aria-invalid'),
            ).toBe('true');

            tratador.limparErros();

            expect(
                campo.hasAttribute('aria-invalid'),
            ).toBe(false);

            expect(
                controlo.hasAttribute('aria-invalid'),
            ).toBe(false);

            expect(
                campo.tomselect.wrapper.classList.contains(
                    'is-invalid',
                ),
            ).toBe(false);
        },
    );

    it(
        'B27.10 — seletor multiplo de generos mantem acessibilidade',
        () => {
            const grupo = document.createElement('div');

            grupo.innerHTML = [
                '<label for="generos-pai-novo-genero">',
                'Géneros pais',
                '</label>',
                '<select',
                ' id="generos-pai-novo-genero"',
                ' name="generos_pai[]"',
                ' class="tom-select-multiplo"',
                ' aria-describedby="ajuda-generos-pai-novo-genero erro-generos-pai-novo-genero"',
                ' multiple>',
                '<option value="1">Metal</option>',
                '<option value="2">Rock</option>',
                '</select>',
                '<div id="ajuda-generos-pai-novo-genero">',
                'Seleciona os géneros pais.',
                '</div>',
                '<div',
                ' id="erro-generos-pai-novo-genero"',
                ' class="invalid-feedback">',
                '</div>',
            ].join('');

            formulario.append(grupo);

            const selecao = grupo.querySelector(
                'select',
            );

            const rotulo = grupo.querySelector(
                'label',
            );

            try {
                const inicializador =
                    new InicializadorTomSelect();

                const instancia =
                    inicializador.iniciar(selecao);

                expect(instancia).not.toBeNull();

                const entrada = instancia.control_input;

                expect(entrada).toBeInstanceOf(
                    HTMLElement,
                );

                // O rótulo deve identificar o controlo
                // que recebe efetivamente o foco.
                expect(rotulo.control).toBe(entrada);

                const obterDescricoes = () => (
                    entrada.getAttribute(
                        'aria-describedby',
                    ) ?? ''
                ).split(/\s+/u).filter(Boolean);

                expect(obterDescricoes()).toContain(
                    'ajuda-generos-pai-novo-genero',
                );

                expect(obterDescricoes()).toContain(
                    'erro-generos-pai-novo-genero',
                );

                expect(
                    new Set(obterDescricoes()).size,
                ).toBe(obterDescricoes().length);

                instancia.focus();

                expect(document.activeElement).toBe(
                    entrada,
                );

                const tratador = Object.create(
                    TratadorFormularioAjax.prototype,
                );

                tratador.formulario = formulario;

                tratador.definirCampoInvalido(selecao);

                expect(
                    selecao.getAttribute('aria-invalid'),
                ).toBe('true');

                expect(
                    entrada.getAttribute('aria-invalid'),
                ).toBe('true');

                tratador.limparErros();

                expect(
                    selecao.hasAttribute('aria-invalid'),
                ).toBe(false);

                expect(
                    entrada.hasAttribute('aria-invalid'),
                ).toBe(false);

                // A limpeza dos erros não pode retirar
                // a ajuda permanente nem a referência
                // ao elemento de feedback.
                expect(obterDescricoes()).toContain(
                    'ajuda-generos-pai-novo-genero',
                );

                expect(obterDescricoes()).toContain(
                    'erro-generos-pai-novo-genero',
                );
            } finally {
                selecao.tomselect?.destroy();
            }
        },
    );

});
