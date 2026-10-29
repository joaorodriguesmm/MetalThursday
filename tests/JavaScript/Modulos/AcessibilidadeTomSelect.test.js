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

});
