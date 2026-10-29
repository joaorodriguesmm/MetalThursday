import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vitest';

import axios from '../../../resources/js/modulos/ClienteHttp.js';

import InicializadorTomSelect
    from '../../../resources/js/modulos/InicializadorTomSelect.js';

import TratadorFormularioAjax
    from '../../../resources/js/modulos/TratadorFormularioAjax.js';

function criarErroValidacao(erros) {
    return new axios.AxiosError(
        'Os dados enviados são inválidos.',
        'ERR_BAD_REQUEST',
        {},
        {},
        {
            data: {
                errors: erros,
            },
            status: 422,
            statusText: 'Unprocessable Content',
            headers: {},
            config: {},
        },
    );
}

describe('B27.10 — erros consecutivos no modal de géneros', () => {
    let formulario;
    let nome;
    let selecao;
    let mensagemNome;
    let mensagemGeneros;

    beforeEach(() => {
        document.body.innerHTML = `
            <div
                id="modal-criar-genero"
                class="modal show"
            >
                <form
                    id="formulario-criar-genero"
                    novalidate
                >
                    <div data-grupo-campo>
                        <label for="nome-novo-genero">
                            Nome
                        </label>

                        <input
                            id="nome-novo-genero"
                            name="nome"
                            aria-describedby="erro-nome-novo-genero"
                            required
                        >

                        <div
                            id="erro-nome-novo-genero"
                            class="invalid-feedback"
                            data-erro-campo="nome"
                            hidden
                        ></div>
                    </div>

                    <div data-grupo-campo>
                        <label for="generos-pai-novo-genero">
                            Géneros pais
                        </label>

                        <input
                            type="hidden"
                            name="generos_pai[]"
                            value=""
                        >

                        <select
                            id="generos-pai-novo-genero"
                            class="tom-select-multiplo"
                            name="generos_pai[]"
                            aria-describedby="ajuda-generos-pai-novo-genero erro-generos-pai-novo-genero"
                            multiple
                        >
                            <option value="1">Metal</option>
                            <option value="2">Rock</option>
                        </select>

                        <div id="ajuda-generos-pai-novo-genero">
                            Seleciona os géneros pais.
                        </div>

                        <div
                            id="erro-generos-pai-novo-genero"
                            class="invalid-feedback"
                            data-erro-campo="generos_pai"
                            hidden
                        ></div>
                    </div>

                    <button type="submit">
                        Criar género
                    </button>
                </form>
            </div>
        `;

        formulario = document.getElementById(
            'formulario-criar-genero',
        );

        nome = document.getElementById(
            'nome-novo-genero',
        );

        selecao = document.getElementById(
            'generos-pai-novo-genero',
        );

        mensagemNome = document.getElementById(
            'erro-nome-novo-genero',
        );

        mensagemGeneros = document.getElementById(
            'erro-generos-pai-novo-genero',
        );

        new InicializadorTomSelect();
    });

    afterEach(() => {
        selecao?.tomselect?.destroy();

        vi.restoreAllMocks();

        document.body.replaceChildren();
    });

    it(
        'substitui o erro anterior e foca o Tom Select no segundo pedido',
        async () => {
            const pedido = vi.spyOn(
                axios,
                'post',
            )
                .mockRejectedValueOnce(
                    criarErroValidacao({
                        nome: [
                            'O nome indicado não é válido.',
                        ],
                    }),
                )
                .mockRejectedValueOnce(
                    criarErroValidacao({
                        generos_pai: [
                            'O género pai indicado não é válido.',
                        ],
                    }),
                );

            expect(selecao.form).toBe(formulario);

            const tratador = new TratadorFormularioAjax(
                'formulario-criar-genero',
                '/teste/generos',
            );

            nome.value = 'Género original';

            await tratador.submeter();

            expect(pedido).toHaveBeenCalledTimes(1);

            expect(
                nome.getAttribute('aria-invalid'),
            ).toBe('true');

            expect(mensagemNome.textContent).toBe(
                'O nome indicado não é válido.',
            );

            expect(mensagemNome.hidden).toBe(false);

            expect(document.activeElement).toBe(nome);

            // O utilizador corrige o primeiro campo.
            nome.value = 'Género corrigido';

            nome.dispatchEvent(
                new Event('input', {
                    bubbles: true,
                }),
            );

            await tratador.submeter();

            expect(pedido).toHaveBeenCalledTimes(2);

            // O erro da tentativa anterior desaparece.
            expect(
                nome.hasAttribute('aria-invalid'),
            ).toBe(false);

            expect(mensagemNome.textContent).toBe('');
            expect(mensagemNome.hidden).toBe(true);

            // O erro novo fica associado ao seletor.
            expect(
                selecao.getAttribute('aria-invalid'),
            ).toBe('true');

            expect(mensagemGeneros.textContent).toBe(
                'O género pai indicado não é válido.',
            );

            expect(mensagemGeneros.hidden).toBe(false);

            const controlo =
                selecao.tomselect.control_input;

            expect(
                controlo.getAttribute('aria-invalid'),
            ).toBe('true');

            const descricoes = (
                controlo.getAttribute('aria-describedby')
                ?? ''
            ).split(/\s+/u).filter(Boolean);

            expect(descricoes).toContain(
                'ajuda-generos-pai-novo-genero',
            );

            expect(descricoes).toContain(
                'erro-generos-pai-novo-genero',
            );

            expect(document.activeElement).toBe(
                controlo,
            );

            expect(tratador.emSubmissao).toBe(false);
        },
    );
});
